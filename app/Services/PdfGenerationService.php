<?php

namespace App\Services;

use App\Services\QuestionService;
use App\Modules\Recommendations\Models\Recommendation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PDF;

class PdfGenerationService
{
    private $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    /**
     * Generate PDF for a patient
     */
    public function generatePatientPdf(int $patientId): array
    {
        try {
            $questionData = $this->questionService->getQuestionsWithAnswersForPatient($patientId);

            // Get recommendations for the patient
            $recommendations = Recommendation::where('patient_id', $patientId)->get();

            $pdfData = [
                'patient_id' => $patientId,
                'questionData' => $questionData,
                'recommendations' => $recommendations,
            ];

            $pdf = PDF::loadView('patient_pdf2', $pdfData);

            // Ensure the 'pdfs' directory exists
            Storage::disk('public')->makeDirectory('pdfs');

            $pdfFileName = "Report_" . date("dmy_His") . '.pdf';
            Storage::disk('public')->put('pdfs/' . $pdfFileName, $pdf->output());

            $pdfUrl = config('app.url') . '/storage/pdfs/' . $pdfFileName;

            Log::info('PDF generated successfully', [
                'patient_id' => $patientId,
                'pdf_file' => $pdfFileName
            ]);

            return [
                'success' => true,
                'pdf_url' => $pdfUrl,
                'data' => $pdfData
            ];
        } catch (\Exception $e) {
            Log::error("Error generating PDF for patient {$patientId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
}
