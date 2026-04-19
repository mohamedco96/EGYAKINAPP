<?php

namespace App\Console\Commands;

use App\Services\AIFormService;
use Dompdf\Dompdf;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

class TestAIImageCommand extends Command
{
    protected $signature = 'ai:test-image
                            {--section=6 : Section ID to process}
                            {--mock : Force mock mode (skip real API calls)}
                            {--real : Force real API calls (requires OPENAI_API_KEY)}
                            {--format=jpg : Sample format: jpg or pdf}
                            {--file=* : Path to custom image/PDF file(s) to use instead of generated sample}';

    protected $description = 'Test the AI image analysis pipeline with a generated sample lab report or custom files';

    public function handle(AIFormService $service): int
    {
        $sectionId = (int) $this->option('section');
        $customFiles = $this->option('file');

        // Set mock mode
        if ($this->option('mock')) {
            config(['services.ai_form.mock' => true]);
            $this->info('Mode: MOCK (no API calls)');
        } elseif ($this->option('real')) {
            config(['services.ai_form.mock' => false]);
            $apiKey = config('services.openai.api_key');
            if (empty($apiKey)) {
                $this->error('OPENAI_API_KEY is not set in .env');
                return 1;
            }
            $this->info('Mode: REAL API (using OpenAI)');
        } else {
            $mode = config('services.ai_form.mock') ? 'MOCK' : 'REAL API';
            $this->info("Mode: {$mode} (from .env AI_FORM_MOCK)");
        }

        // Build files array
        if (!empty($customFiles)) {
            $files = [];
            foreach ($customFiles as $path) {
                if (!file_exists($path)) {
                    $this->error("File not found: {$path}");
                    return 1;
                }
                $files[] = new UploadedFile($path, basename($path), mime_content_type($path), null, true);
                $this->info("Using custom file: {$path}");
            }
        } else {
            $format = strtolower($this->option('format'));
            if ($format === 'pdf') {
                $this->info('Generating sample lab report PDF...');
                $samplePath = $this->generateSampleLabReportPdf();
                $files = [new UploadedFile($samplePath, 'sample_lab_report.pdf', 'application/pdf', null, true)];
            } else {
                $this->info('Generating sample lab report image...');
                $samplePath = $this->generateSampleLabReport();
                $files = [new UploadedFile($samplePath, 'sample_lab_report.jpg', 'image/jpeg', null, true)];
            }
            $this->info("Generated: {$samplePath}");
        }

        $this->newLine();
        $this->info("Processing {$sectionId} with " . count($files) . " file(s)...");
        $this->newLine();

        // Step 1: Analyze images
        $this->info('--- Step 1: Image Analysis (GPT-4o Vision) ---');
        try {
            $extractedText = $service->analyzeImage($files);
            $this->line($extractedText);
        } catch (\Exception $e) {
            $this->error('Image analysis failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Step 2: Process section
        $this->info("--- Step 2: Extract structured data (section {$sectionId}) ---");
        try {
            $result = $service->processSection($extractedText, $sectionId);
        } catch (\Exception $e) {
            $this->error('Section processing failed: ' . $e->getMessage());
            return 1;
        }

        if (empty($result['data'])) {
            $this->warn('No questions found for section ' . $sectionId);
            return 0;
        }

        // Display results as table
        $rows = [];
        foreach ($result['data'] as $item) {
            $answer = $item['answer'] ?? null;
            if (is_array($answer)) {
                $answer = json_encode($answer, JSON_UNESCAPED_UNICODE);
            }
            $rows[] = [
                $item['id'],
                mb_substr($item['question'], 0, 45),
                $item['type'],
                mb_substr((string) $answer, 0, 50) ?: 'null',
            ];
        }

        $this->table(['ID', 'Question', 'Type', 'Answer'], $rows);

        $filled = count(array_filter($result['data'], function ($item) {
            $a = $item['answer'] ?? null;
            if ($a === null) return false;
            if (is_array($a) && isset($a['answers']) && $a['answers'] === null) return false;
            if (is_array($a) && isset($a['answers']) && empty($a['answers'])) return false;
            return true;
        }));

        $this->newLine();
        $this->info("Total questions: " . count($result['data']));
        $this->info("Filled answers: {$filled}");
        $this->info("Empty/null: " . (count($result['data']) - $filled));

        // Cleanup generated sample
        if (empty($customFiles) && isset($samplePath)) {
            @unlink($samplePath);
        }

        return 0;
    }

    /**
     * Generate a realistic-looking lab report image using PHP GD.
     */
    private function generateSampleLabReport(): string
    {
        $width  = 800;
        $height = 1100;
        $img    = imagecreatetruecolor($width, $height);

        // Colors
        $white     = imagecolorallocate($img, 255, 255, 255);
        $black     = imagecolorallocate($img, 0, 0, 0);
        $darkGray  = imagecolorallocate($img, 60, 60, 60);
        $lightGray = imagecolorallocate($img, 200, 200, 200);
        $blue      = imagecolorallocate($img, 0, 70, 140);
        $red       = imagecolorallocate($img, 180, 0, 0);

        imagefill($img, 0, 0, $white);

        // Header
        imagefilledrectangle($img, 0, 0, $width, 60, $blue);
        imagestring($img, 5, 250, 10, 'LABORATORY REPORT', $white);
        imagestring($img, 3, 260, 35, 'Mansoura University Hospital', $white);

        // Patient info
        $y = 80;
        imagestring($img, 4, 30, $y, 'Patient: Ahmed Mohamed Ali', $black);
        imagestring($img, 4, 450, $y, 'Date: 2024-03-15', $black);
        $y += 25;
        imagestring($img, 4, 30, $y, 'ID: 29901011234567', $darkGray);
        imagestring($img, 4, 450, $y, 'Age: 64  Gender: Male', $darkGray);

        // Separator
        $y += 30;
        imageline($img, 20, $y, $width - 20, $y, $lightGray);

        // Column headers
        $y += 15;
        imagestring($img, 4, 30, $y, 'TEST', $blue);
        imagestring($img, 4, 350, $y, 'RESULT', $blue);
        imagestring($img, 4, 500, $y, 'UNIT', $blue);
        imagestring($img, 4, 620, $y, 'REF RANGE', $blue);

        $y += 10;
        imageline($img, 20, $y + 15, $width - 20, $y + 15, $lightGray);

        // Lab values
        $labs = [
            ['KIDNEY FUNCTION', '', '', '', true],
            ['Creatinine (admission)', '3.2', 'mg/dl', '0.7-1.3', true],
            ['Basal Creatinine', '1.1', 'mg/dl', '0.7-1.3', false],
            ['Creatinine Day 2', '2.8', 'mg/dl', '0.7-1.3', true],
            ['Creatinine Day 3', '2.1', 'mg/dl', '0.7-1.3', true],
            ['Urea', '80', 'mg/dl', '15-45', true],
            ['BUN', '37', 'mg/dl', '7-20', true],
            ['', '', '', '', false],
            ['BLOOD GASES & ELECTROLYTES', '', '', '', true],
            ['pH', '7.30', '', '7.35-7.45', true],
            ['HCO3', '18', 'mmHg', '22-26', true],
            ['pCO2', '35', 'mmHg', '35-45', false],
            ['Serum Na', '138', 'mEq/L', '136-145', false],
            ['K', '5.2', 'mg/dl', '3.5-5.0', true],
            ['Calcium', '8.5', 'mg/dl', '8.5-10.5', false],
            ['Phosphorus', '4.8', 'mg/dl', '2.5-4.5', true],
            ['', '', '', '', false],
            ['LIVER FUNCTION', '', '', '', true],
            ['SGOT', '45', 'u/l', '10-40', true],
            ['SGPT', '38', 'u/l', '7-56', false],
            ['Total Bilirubin', '1.2', 'mg/dl', '0.1-1.2', false],
            ['Direct Bilirubin', '0.4', 'mg/dl', '0-0.3', true],
            ['Albumin', '3.0', 'gm/dl', '3.5-5.0', true],
            ['', '', '', '', false],
            ['COAGULATION', '', '', '', true],
            ['PT', '14', 'seconds', '11-13.5', true],
            ['PTT', '32', 'seconds', '25-35', false],
            ['INR', '1.2', '', '0.8-1.1', true],
            ['', '', '', '', false],
            ['SEROLOGY', '', '', '', false],
            ['HCV Ab', 'Negative', '', '', false],
            ['HBs Ag', 'Negative', '', '', false],
            ['HIV Ab', 'Negative', '', '', false],
            ['', '', '', '', false],
            ['CBC', '', '', '', true],
            ['Hemoglobin', '9.5', 'gm/dl', '13-17', true],
            ['WBCs', '11000', '/uL', '4000-11000', false],
            ['Platelets', '180000', '/uL', '150000-400000', false],
            ['Neutrophils', '7500', '/uL', '2000-7000', true],
            ['Lymphocytes', '2500', '/uL', '1000-3000', false],
            ['Monocytes', '800', '/uL', '200-800', false],
            ['Eosinophils', '200', '/uL', '100-500', false],
            ['Basophils', '50', '/uL', '0-100', false],
        ];

        $y += 25;
        foreach ($labs as $row) {
            if (empty($row[0])) {
                $y += 5;
                continue;
            }

            // Section headers
            if (empty($row[1]) && !empty($row[0])) {
                $y += 5;
                imagestring($img, 4, 30, $y, $row[0], $blue);
                $y += 20;
                continue;
            }

            $color = $row[4] ? $red : $darkGray; // abnormal values in red
            imagestring($img, 3, 30, $y, $row[0], $darkGray);
            imagestring($img, 3, 350, $y, $row[1], $color);
            imagestring($img, 3, 500, $y, $row[2], $darkGray);
            imagestring($img, 3, 620, $y, $row[3], $lightGray);
            $y += 18;
        }

        // Second section - Urine Analysis
        $y += 15;
        imageline($img, 20, $y, $width - 20, $y, $lightGray);
        $y += 10;
        imagestring($img, 4, 30, $y, 'URINE ANALYSIS', $blue);
        $y += 25;

        $urine = [
            ['Specific Gravity', '1.018'],
            ['Clarity', 'Turbid'],
            ['Epithelial Cells', 'Few'],
            ['Casts', 'Granular casts'],
            ['WBCs', '8 /HPF'],
            ['RBCs', '4 /HPF'],
            ['Proteinuria', '++'],
            ['CRP', '48 mg/l'],
        ];

        foreach ($urine as $row) {
            imagestring($img, 3, 30, $y, $row[0], $darkGray);
            imagestring($img, 3, 350, $y, $row[1], $darkGray);
            $y += 18;
        }

        // Save
        $path = sys_get_temp_dir() . '/sample_lab_report_' . uniqid() . '.jpg';
        imagejpeg($img, $path, 95);
        imagedestroy($img);

        return $path;
    }

    /**
     * Generate a realistic-looking lab report PDF using Dompdf.
     */
    private function generateSampleLabReportPdf(): string
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
    h1 { background: #00468C; color: #fff; padding: 10px 15px; font-size: 16px; margin: 0; text-align: center; }
    h2 { color: #00468C; font-size: 13px; margin: 15px 0 5px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
    .patient-info { margin: 10px 0; }
    .patient-info span { display: inline-block; width: 48%; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th { text-align: left; color: #00468C; border-bottom: 2px solid #ccc; padding: 4px 6px; font-size: 11px; }
    td { padding: 3px 6px; border-bottom: 1px solid #eee; font-size: 11px; }
    .abnormal { color: #B40000; font-weight: bold; }
</style>
</head>
<body>
<h1>LABORATORY REPORT<br><small style="font-size:11px;font-weight:normal;">Mansoura University Hospital</small></h1>

<div class="patient-info">
    <span><strong>Patient:</strong> Ahmed Mohamed Ali</span>
    <span><strong>Date:</strong> 2024-03-15</span><br>
    <span><strong>ID:</strong> 29901011234567</span>
    <span><strong>Age:</strong> 64 &nbsp; <strong>Gender:</strong> Male</span>
</div>

<h2>KIDNEY FUNCTION</h2>
<table>
<tr><th>Test</th><th>Result</th><th>Unit</th><th>Ref Range</th></tr>
<tr><td>Creatinine (admission)</td><td class="abnormal">3.2</td><td>mg/dl</td><td>0.7-1.3</td></tr>
<tr><td>Basal Creatinine</td><td>1.1</td><td>mg/dl</td><td>0.7-1.3</td></tr>
<tr><td>Creatinine Day 2</td><td class="abnormal">2.8</td><td>mg/dl</td><td>0.7-1.3</td></tr>
<tr><td>Creatinine Day 3</td><td class="abnormal">2.1</td><td>mg/dl</td><td>0.7-1.3</td></tr>
<tr><td>Urea</td><td class="abnormal">80</td><td>mg/dl</td><td>15-45</td></tr>
<tr><td>BUN</td><td class="abnormal">37</td><td>mg/dl</td><td>7-20</td></tr>
</table>

<h2>BLOOD GASES &amp; ELECTROLYTES</h2>
<table>
<tr><th>Test</th><th>Result</th><th>Unit</th><th>Ref Range</th></tr>
<tr><td>pH</td><td class="abnormal">7.30</td><td></td><td>7.35-7.45</td></tr>
<tr><td>HCO3</td><td class="abnormal">18</td><td>mmHg</td><td>22-26</td></tr>
<tr><td>pCO2</td><td>35</td><td>mmHg</td><td>35-45</td></tr>
<tr><td>Serum Na</td><td>138</td><td>mEq/L</td><td>136-145</td></tr>
<tr><td>K</td><td class="abnormal">5.2</td><td>mg/dl</td><td>3.5-5.0</td></tr>
<tr><td>Calcium</td><td>8.5</td><td>mg/dl</td><td>8.5-10.5</td></tr>
<tr><td>Phosphorus</td><td class="abnormal">4.8</td><td>mg/dl</td><td>2.5-4.5</td></tr>
</table>

<h2>LIVER FUNCTION</h2>
<table>
<tr><th>Test</th><th>Result</th><th>Unit</th><th>Ref Range</th></tr>
<tr><td>SGOT</td><td class="abnormal">45</td><td>u/l</td><td>10-40</td></tr>
<tr><td>SGPT</td><td>38</td><td>u/l</td><td>7-56</td></tr>
<tr><td>Total Bilirubin</td><td>1.2</td><td>mg/dl</td><td>0.1-1.2</td></tr>
<tr><td>Direct Bilirubin</td><td class="abnormal">0.4</td><td>mg/dl</td><td>0-0.3</td></tr>
<tr><td>Albumin</td><td class="abnormal">3.0</td><td>gm/dl</td><td>3.5-5.0</td></tr>
</table>

<h2>COAGULATION</h2>
<table>
<tr><th>Test</th><th>Result</th><th>Unit</th><th>Ref Range</th></tr>
<tr><td>PT</td><td class="abnormal">14</td><td>seconds</td><td>11-13.5</td></tr>
<tr><td>PTT</td><td>32</td><td>seconds</td><td>25-35</td></tr>
<tr><td>INR</td><td class="abnormal">1.2</td><td></td><td>0.8-1.1</td></tr>
</table>

<h2>SEROLOGY</h2>
<table>
<tr><th>Test</th><th>Result</th></tr>
<tr><td>HCV Ab</td><td>Negative</td></tr>
<tr><td>HBs Ag</td><td>Negative</td></tr>
<tr><td>HIV Ab</td><td>Negative</td></tr>
</table>

<h2>CBC</h2>
<table>
<tr><th>Test</th><th>Result</th><th>Unit</th><th>Ref Range</th></tr>
<tr><td>Hemoglobin</td><td class="abnormal">9.5</td><td>gm/dl</td><td>13-17</td></tr>
<tr><td>WBCs</td><td>11000</td><td>/uL</td><td>4000-11000</td></tr>
<tr><td>Platelets</td><td>180000</td><td>/uL</td><td>150000-400000</td></tr>
<tr><td>Neutrophils</td><td class="abnormal">7500</td><td>/uL</td><td>2000-7000</td></tr>
<tr><td>Lymphocytes</td><td>2500</td><td>/uL</td><td>1000-3000</td></tr>
<tr><td>Monocytes</td><td>800</td><td>/uL</td><td>200-800</td></tr>
<tr><td>Eosinophils</td><td>200</td><td>/uL</td><td>100-500</td></tr>
<tr><td>Basophils</td><td>50</td><td>/uL</td><td>0-100</td></tr>
</table>

<h2>URINE ANALYSIS</h2>
<table>
<tr><th>Test</th><th>Result</th></tr>
<tr><td>Specific Gravity</td><td>1.018</td></tr>
<tr><td>Clarity</td><td>Turbid</td></tr>
<tr><td>Epithelial Cells</td><td>Few</td></tr>
<tr><td>Casts</td><td>Granular casts</td></tr>
<tr><td>WBCs</td><td>8 /HPF</td></tr>
<tr><td>RBCs</td><td>4 /HPF</td></tr>
<tr><td>Proteinuria</td><td>++</td></tr>
<tr><td>CRP</td><td>48 mg/l</td></tr>
</table>
</body>
</html>
HTML;

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $path = sys_get_temp_dir() . '/sample_lab_report_' . uniqid() . '.pdf';
        file_put_contents($path, $dompdf->output());

        return $path;
    }
}
