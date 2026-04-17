<?php

namespace App\Services;

use App\Modules\Questions\Models\Questions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIFormService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    // -------------------------------------------------------------------------
    // Public: Input handlers
    // -------------------------------------------------------------------------

    /**
     * Transcribe an audio file using OpenAI Whisper.
     * Returns the raw transcript string.
     *
     * Set AI_FORM_MOCK=true in .env to bypass the API call during local testing.
     */
    public function transcribeAudio(UploadedFile $audioFile, string $language = 'en'): string
    {
        if (config('services.ai_form.mock')) {
            Log::info('AIFormService: mock transcription active (AI_FORM_MOCK=true)');

            return 'Male patient, aged 64 years old, from Agha, Daqahliya, admitted to Mansoura University Hospital with serum creatinine 2.5, the primary cause is sepsis for ICU admission.';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->timeout(120)->attach(
            'file',
            fopen($audioFile->getRealPath(), 'r'),
            $audioFile->getClientOriginalName()
        )->post('https://api.openai.com/v1/audio/transcriptions', [
            'model'           => 'whisper-1',
            'language'        => $language,
            'response_format' => 'text',
        ]);

        if (! $response->successful()) {
            $body = $response->body();
            Log::error('Whisper API Error', array_filter([
                'status'         => $response->status(),
                'response_bytes' => strlen($body),
                'response_hash'  => substr(hash('sha256', $body), 0, 12),
                'request_id'     => $response->header('x-request-id'),
                'body'           => config('app.debug') ? $body : null,
            ]));
            throw new \Exception('Failed to transcribe audio.');
        }

        return trim($response->body());
    }

    /**
     * Analyze an image file using GPT-4 Vision and return extracted text.
     * (Future: lab reports, radiology results, etc.)
     *
     * Implementation steps when ready:
     *   1. Base64-encode the image
     *   2. POST to GPT-4o with vision content (image_url type = base64)
     *   3. Return the model's text description
     *   Then pass that text to processSection() — same pipeline as voice.
     */
    public function analyzeImage(UploadedFile $imageFile): string
    {
        throw new \Exception('Image analysis is not yet implemented.');
    }

    // -------------------------------------------------------------------------
    // Public: Core extraction pipeline (input-agnostic)
    // -------------------------------------------------------------------------

    /**
     * Extract medical data from plain text and map it to section questions.
     *
     * This is the input-agnostic core — it doesn't care whether $text came
     * from voice (Whisper), image (GPT Vision), or anywhere else.
     *
     * Returns ['data' => [...]] where data matches the exact same structure
     * returned by SectionManagementService::getQuestionsAndAnswers().
     */
    public function processSection(string $text, int $sectionId): array
    {
        $questions = $this->getFilteredQuestions($sectionId);

        if ($questions->isEmpty()) {
            return ['data' => [], 'prompt' => ''];
        }

        $prompt        = $this->buildExtractionPrompt($questions, $text);
        $extractedData = $this->extractData($prompt, $sectionId);
        $data          = $this->formatResponse($questions, $extractedData);

        return [
            'data'   => $data,
            'prompt' => $prompt,
        ];
    }

    // -------------------------------------------------------------------------
    // Private: Pipeline steps
    // -------------------------------------------------------------------------

    /**
     * Fetch questions for the section, filtering out skip/hidden/files types.
     * Mirrors the filtering logic in SectionManagementService::getQuestionsAndAnswers().
     */
    private function getFilteredQuestions(int $sectionId): Collection
    {
        return Questions::where('section_id', $sectionId)
            ->orderBy('sort')
            ->get()
            ->filter(function ($question) {
                // Skip questions flagged with 'skip'
                if ($question->skip) {
                    return false;
                }
                // Skip hidden questions (no pre-existing answer to reveal them)
                if ($question->hidden) {
                    return false;
                }
                // Skip file-upload questions — can't extract files from text
                if ($question->type === 'files') {
                    return false;
                }

                return true;
            });
    }

    /**
     * Build a dynamic extraction prompt for GPT-4o-mini.
     * Lists each question with its ID, text, type, and allowed values.
     */
    private function buildExtractionPrompt(Collection $questions, string $text): string
    {
        $questionsDescription = [];
        $catchAllId           = null;

        foreach ($questions as $question) {
            $desc = [
                'id'       => $question->id,
                'question' => $question->question,
                'type'     => $question->type,
            ];

            if (in_array($question->type, ['select', 'multiple']) && ! empty($question->values)) {
                $desc['allowed_values'] = $question->values;
            }

            if ($question->type === 'date') {
                $desc['note'] = 'Extract as a date string in YYYY-MM-DD format, or null if not found.';
            }

            // Detect a generic catch-all "Other" free-text field:
            // type=string AND question name is exactly "Other" (or "Other Causes", etc.)
            // with no values list — used to capture leftover info from the transcript.
            if (
                $question->type === 'string' &&
                empty($question->values) &&
                preg_match('/^other(s)?(\s+causes)?$/i', trim($question->question))
            ) {
                $catchAllId            = (string) $question->id;
                $desc['is_catch_all']  = true;
                $desc['note']          = 'Catch-all field. Put here any medically relevant information from the transcript that does not clearly belong to any other question above.';
            }

            $questionsDescription[] = $desc;
        }

        $questionsJson = json_encode($questionsDescription, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $catchAllRule = $catchAllId
            ? "14. CATCH-ALL RULE: One question is marked with \"is_catch_all\": true (ID {$catchAllId}). After filling all other questions, collect any medically relevant details from the transcript that were NOT captured by any other question, and write them as a concise summary string in this field. Examples of catch-all content: reason for admission, chief complaint, serum creatinine value, diagnosis, ICU admission reason, or any other clinical detail mentioned but not covered by a specific question. If everything was already captured, return null."
            : '';

        return <<<PROMPT
You are a medical data extraction assistant. A doctor has dictated patient information, and your job is to extract structured data from the transcript and map it to the questions below.

CRITICAL RULES — you MUST follow these exactly:
1. Return a JSON object where each key is a question ID (as a string) and the value is the extracted answer.
2. For "select" type:
   a. First, use your medical knowledge to resolve any abbreviations, synonyms, or phonetic transcription errors in the transcript (e.g., "debits" → "Diabetes Mellitus", "DM" → "Diabetes Mellitus", "hypertension" → "Hypertension", "HTN" → "Hypertension", "Takahliya/Dakahlia/Dakahliya" → "Dakahlia", similar phonetic variants → correct spelling).
   b. If the resolved value EXACTLY matches one of the "allowed_values" → return that string.
   c. If relevant information IS present in the transcript but does NOT match any "allowed_values" (even after synonym resolution) AND the list contains "Others", "Other", or "others" (any casing) → return {"value": "<the original text from transcript>", "is_other": true}. This is mandatory — do NOT return null when the information exists but doesn't match.
   d. If no relevant information is found at all → return the JSON literal null.
3. For "multiple" type:
   a. First, apply the same abbreviation/synonym/phonetic resolution as rule 2a.
   b. For each resolved value, if it EXACTLY matches an item in "allowed_values" → include it in the answers array.
   c. IMPORTANT: Before treating a value as unmatched, check ALL allowed_values carefully for synonyms. Example: transcript says "shisha" → check if "Shisha smoker" exists in allowed_values → it does → use "Shisha smoker", do NOT put it in others_text.
   d. Only if a resolved value truly does NOT match any "allowed_values" BUT the list contains "Others", "Other", or "others" (any casing) → include that exact entry in the answers array AND return the format: {"answers": ["matched1", "<the_others_entry>"], "others_text": "<unmatched text from transcript>"}.
   e. If nothing is found → return an empty array [].
4. For "string" or "text" type: return the extracted text as a string, or the JSON literal null if not found.
5. For "date" type: return the date as a string in YYYY-MM-DD format (e.g., "2024-03-15"), or the JSON literal null if not found.
6. IMPORTANT: If no information is found for a question, you MUST return the JSON literal null — NOT the string "null", NOT an empty string "".
7. IMPORTANT: For "select" type, if information IS present in the transcript but does not match any allowed_values, you MUST use the is_other format (rule 2c). Never discard information by returning null when the information exists. The value in {"value": "...", "is_other": true} must be the actual extracted text (e.g., "Egypt", "Mansoura University Hospital") — NEVER put "Others"/"Other" as the value. Example: transcript says "Mansoura University Hospital", allowed_values has codes like "MUH-14" plus "Others" → return {"value": "Mansoura University Hospital", "is_other": true}.
8. For "string" questions whose text starts with "If the answer is other" or "If the response to the previous question is others" or similar companion phrasing: fill this field with the clarifying detail from the transcript that corresponds to the "other" answer in the preceding question. Return null if the preceding answer was not "other/others".
9. Medical synonym & phonetic correction examples (apply broadly, not limited to these):
   - "ICU" or "intensive care" → "Intensive Care Unit"
   - "HTN" or "hypertension" → "Hypertension"
   - "DM" or "diabetes" or "debits" or "diabetics" → "Diabetes Mellitus"
   - "CAD" or "coronary artery" → "Coronary Artery Disease"
   - "CKD" or "chronic kidney" → "Chronic Kidney Disease"
   - "Takahliya", "Dakhliya", "Dakahlia", "Daqahliya", "Dakahliya" → "Dakahlia"
   - Any phonetic transcription error → infer the most likely intended medical term
10. Match allowed_values EXACTLY (case-sensitive) after synonym resolution.
11. Do not invent or guess data that is not present in the transcript.
12. For numeric string fields (National ID, phone number, age, duration in years, or any sequence of digits): return digits only with NO dashes, spaces, dots, or any other formatting characters. Examples: "290-1011-234567" → "29901011234567", "010-123-45678" → "01012345678".
13. For email fields: convert spoken "at" to "@" and "dot" to ".". Example: "ahmed at example dot com" → "ahmed@example.com".
{$catchAllRule}

QUESTIONS:
{$questionsJson}

TRANSCRIPT:
{$text}

Respond ONLY with the JSON object. No explanation, no markdown, no code blocks.
PROMPT;
    }

    /**
     * Send the prompt to GPT-4o-mini with JSON mode enforced.
     * Returns the decoded array of {question_id => extracted_value}.
     *
     * Set AI_FORM_MOCK=true in .env to bypass the API call during local testing.
     * The mock returns null for every question so the full formatting pipeline
     * still runs against your real DB questions — validating the structure is correct.
     */
    private function extractData(string $prompt, int $sectionId = 0): array
    {
        if (config('services.ai_form.mock')) {
            Log::info('AIFormService: mock extraction active (AI_FORM_MOCK=true)', ['section_id' => $sectionId]);

            // Section 1 gets a rich mock covering all answer shapes for testing:
            //   string                                        → plain value
            //   select matched                                → plain string from allowed_values
            //   select unmatched (has Others)                 → {"value": "...", "is_other": true}
            //   select null                                   → null
            //   multiple all matched                          → plain array
            //   multiple with unmatched (has Others)          → {"answers": [...], "others_text": "..."}
            if ($sectionId === 1) {
                return [
                    '1'   => 'Ahmed Mohamed',                                           // string  | Name
                    '2'   => 'MUH-14',                                                  // select  | Hospital — matched
                    '3'   => 'Patient himself',                                         // select  | Collected data from — matched
                    '4'   => '29901011234567',                                          // string  | National ID
                    '5'   => '01012345678',                                             // string  | Phone
                    '6'   => 'ahmed@example.com',                                       // string  | Email
                    '7'   => '64',                                                      // string  | Age
                    '8'   => 'Male',                                                    // select  | Gender — matched
                    '9'   => null,                                                      // select  | Occupation — not mentioned
                    '10'  => 'Rural',                                                   // select  | Residency — matched
                    '11'  => 'Dakahlia',                                                // select  | Governorate — matched
                    '12'  => ['value' => 'Partnered', 'is_other' => true],             // select  | Marital status — unmatched → other_field
                    '142' => '3',                                                       // string  | Children
                    '13'  => 'Primary school',                                          // select  | Educational level — matched
                    '14'  => ['answers' => ['Cigarette smoker', 'Others'], 'others_text' => 'Shisha occasionally'], // multiple | unmatched → others_text
                    '16'  => 'Yes',                                                     // select  | DM — matched
                    '17'  => '10',                                                      // string  | DM duration
                    '18'  => 'Yes',                                                     // select  | HTN — matched
                    '19'  => '5',                                                       // string  | HTN duration
                    '20'  => 'Serum creatinine 2.5, admitted for sepsis (ICU admission)',  // string  | Other — catch-all
                    '149' => 'No',                                                      // select  | Black race — matched
                    '168' => ['value' => 'Renal Transplant Unit', 'is_other' => true], // select  | Department — unmatched → other_field
                    '169' => 'Renal Transplant Unit',                                   // string  | Other department detail
                ];
            }

            // Section 2: Complaint — date, multiple (with Others), select
            if ($sectionId === 2) {
                return [
                    '21'  => 'ER',                                                                 // select  | Where seen first — matched
                    '23'  => '2024-03-15',                                                         // date    | Date of admission
                    '24'  => ['answers' => ['OliguriaAnuria', 'Fatiguetiredness', 'Others'], 'others_text' => 'severe lower limb swelling'], // multiple | main complaint — with others_text
                    '162' => 'Anuria/oliguria',                                                     // select  | Urine output — matched
                    '166' => ['AKI', 'AKI on top of CKD'],                                         // multiple | Provisional diagnosis — all matched
                ];
            }

            // Section 3: Cause of AKI — select, multiple, catch-all "Other Causes"
            if ($sectionId === 3) {
                return [
                    '26'  => 'Intrinsic renal',                                                    // select  | Cause of AKI — matched
                    '27'  => [],                                                                    // multiple | Pre-renal causes — none
                    '29'  => ['Tubular injury due to ischemic ATN', 'Tubular injury due to toxic ATN'], // multiple | Intrinsic causes — matched
                    '31'  => [],                                                                    // multiple | Post-renal causes — none
                    '33'  => 'Patient had recent NSAIDs use and contrast exposure prior to admission', // string  | Other Causes — catch-all
                ];
            }

            // Section 4: Risk factors — mostly Yes/No selects, multiple for drugs
            if ($sectionId === 4) {
                return [
                    '34'  => 'No',                                                                  // select  | History of CKD
                    '35'  => 'No',                                                                  // select  | Past history of AKI
                    '36'  => 'No',                                                                  // select  | History of cardiac failure
                    '37'  => 'No',                                                                  // select  | History of LCF
                    '38'  => 'No',                                                                  // select  | History of neurological impairment
                    '39'  => 'Yes',                                                                 // select  | History of sepsis
                    '40'  => 'No',                                                                  // select  | Recent iodinated contrast
                    '41'  => 'Yes',                                                                 // select  | Nephrotoxic drugs
                    '42'  => ['answers' => ['NSAIDs', 'Aminoglycosides', 'Others'], 'others_text' => 'herbal remedy'], // multiple | Drugs — with others_text
                    '43'  => 'Yes',                                                                 // select  | History of hypovolemia
                    '44'  => 'No',                                                                  // select  | History of malignancy
                    '45'  => 'No',                                                                  // select  | History of trauma
                    '46'  => 'No',                                                                  // select  | History of autoimmune disease
                    '47'  => null,                                                                  // string  | Other risk factors
                ];
            }

            // Section 5: Assessment — string vitals, multiple examinations
            if ($sectionId === 5) {
                return [
                    '48'  => '98',                                                                  // string  | Heart rate/minute
                    '49'  => '22',                                                                  // string  | Respiratory rate/minute
                    '50'  => '110',                                                                 // string  | SBP
                    '51'  => '70',                                                                  // string  | DBP
                    '53'  => '96',                                                                  // string  | Oxygen saturation
                    '54'  => '37.8',                                                                // string  | Temperature
                    '52'  => '14',                                                                  // string  | GCS
                    '56'  => 'Alert',                                                               // select  | AVPU — matched
                    '140' => '172',                                                                 // string  | Height
                    '141' => '80',                                                                  // string  | Weight
                    '55'  => '15',                                                                  // string  | UOP ml/hour
                    '159' => '90',                                                                  // string  | UOP first 6h
                    '160' => '350',                                                                 // string  | UOP first 24h
                    '57'  => ['Normal'],                                                            // multiple | Skin exam
                    '59'  => ['Pallor'],                                                            // multiple | Eye exam
                    '61'  => ['Normal'],                                                            // multiple | Ear exam
                    '63'  => ['Normal'],                                                            // multiple | Cardiac exam
                    '65'  => 'Non-congested',                                                       // select  | IJV
                    '66'  => ['Fine crepitations'],                                                 // multiple | Chest exam
                    '68'  => ['Loin Pain'],                                                         // multiple | Abdominal exam
                    '70'  => null,                                                                  // string  | Other important findings
                ];
            }

            // Section 7: Medical decision — multiple with Others, dialysis details
            if ($sectionId === 7) {
                return [
                    '77'  => ['Admission'],                                                         // multiple | Medical decision — matched
                    '86'  => 'Yes',                                                                 // select  | Received dialysis
                    '87'  => ['HD'],                                                                // multiple | Modality of dialysis
                    '88'  => ['Life-threatening hyperkalemia', 'Pulmonary edema'],                  // multiple | Indication of dialysis
                    '89'  => '3',                                                                   // string   | Number of sessions
                    '90'  => ['A temporary renal dialysis catheter'],                               // multiple | Vascular access
                    '232' => ['Jugular'],                                                           // multiple | Site of access
                    '91'  => ['answers' => ['Antibiotics', 'Fluid resuscitation', 'Others'], 'others_text' => 'vasopressors'], // multiple | Other management — with others_text
                    '156' => 'No',                                                                  // select  | Immunosuppressive drugs
                    '233' => [],                                                                    // multiple | Immunosuppressant types — none
                    '270' => [],                                                                    // multiple | HRS classification — none
                ];
            }

            // Section 8: Outcome — select with Others, multiple with Others, catch-all "Other", lab values
            if ($sectionId === 8) {
                return [
                    '79'  => 'Survivor',                                                            // select  | Outcome — matched
                    '80'  => '1.2',                                                                 // string  | Creatinine on discharge
                    '131' => '45',                                                                  // string  | Urea mg/dl
                    '132' => '21',                                                                  // string  | BUN mg/dl
                    '81'  => '7',                                                                   // string  | Duration of admission/days
                    '82'  => ['Partial improvement'],                                               // multiple | Final status — matched
                    '83'  => 'Patient required nutritional support and physiotherapy', // string | Other — catch-all
                    '116' => '7.35',                                                                // string  | pH
                    '117' => '22',                                                                  // string  | HCO3
                    '118' => '38',                                                                  // string  | pCO2
                    '119' => '4.8',                                                                 // string  | K mg/dl
                    '120' => '35',                                                                  // string  | SGOT
                    '121' => '28',                                                                  // string  | SGPT
                    '282' => '0.9',                                                                 // string  | Bilirubin
                    '122' => '3.2',                                                                 // string  | Albumin
                    '126' => '10.5',                                                                // string  | Hemoglobin
                    '127' => '12000',                                                               // string  | WBCs count
                    '280' => null,                                                                  // string  | Monocytes
                    '128' => '180000',                                                              // string  | Platelets
                    '129' => '8500',                                                                // string  | Neutrophil
                    '281' => null,                                                                  // string  | Basophil
                    '130' => '2500',                                                                // string  | Lymphocytes
                    '283' => null,                                                                  // string  | Eosinophil
                    '133' => '1.015',                                                               // string  | Specific gravity urine
                    '134' => 'Turbid',                                                              // string  | Clarity urine
                    '135' => 'Few',                                                                 // string  | Epithelial cells urine
                    '136' => null,                                                                  // string  | Crystal types
                    '137' => 'Granular casts',                                                      // string  | Casts
                    '138' => '10',                                                                  // string  | WBCs urine
                    '139' => '5',                                                                   // string  | RBCs urine
                    '144' => '48',                                                                  // string  | CRP
                    '158' => '+',                                                                   // select  | Proteinuria — matched
                    '161' => '40',                                                                  // string  | UOP last 6h
                    '205' => '55',                                                                  // string  | EF on discharge
                    '206' => null,                                                                  // string  | ECHO summary
                    '207' => '138',                                                                 // string  | Serum Na
                ];
            }

            // Section 10: CTS_Patients — select with "Other" (singular), "others" (lowercase), many numeric strings
            if ($sectionId === 10) {
                return [
                    '171' => ['value' => 'Double valve replacement with tricuspid repair', 'is_other' => true], // select | Type of surgery — unmatched → is_other
                    '173' => 'Double valve replacement with tricuspid repair',                      // string  | Other surgery detail
                    '174' => 'IHD',                                                                // select  | Type of cardiac disease — matched
                    '175' => null,                                                                  // string  | Other cardiac disease detail
                    '176' => '135',                                                                 // string  | Preop SBP
                    '177' => '85',                                                                  // string  | Preop DBP
                    '178' => '9500',                                                                // string  | Preop WBCs
                    '179' => '12.5',                                                                // string  | Preop HB
                    '180' => '210000',                                                              // string  | Preop platelets
                    '181' => '1.1',                                                                 // string  | Preop creatinine
                    '182' => '2',                                                                   // string  | Preop urine pus cells
                    '183' => '0',                                                                   // string  | Preop RBCs urine
                    '216' => 'Nil',                                                                 // select  | Preop proteinuria — matched
                    '184' => 'None',                                                                // select  | Preop urine cast — matched
                    '185' => null,                                                                  // string  | Other cast detail
                    '186' => '1.1',                                                                 // string  | Preop INR
                    '187' => '4.0',                                                                 // string  | Preop albumin
                    '188' => '0.7',                                                                 // string  | Preop bilirubin
                    '189' => '25',                                                                  // string  | Preop ALT
                    '190' => '22',                                                                  // string  | Preop AST
                    '191' => '0.02',                                                                // string  | Preop troponin
                    '192' => '45',                                                                  // string  | Preop EF
                    '193' => 'Mildly dilated LV with global hypokinesia',                          // string  | Preop Echo summary
                    '194' => '90',                                                                  // string  | CPB duration
                    '195' => '60',                                                                  // string  | Cross clamping time
                    '196' => '28',                                                                  // string  | Core temp lowest
                    '224' => '37',                                                                  // string  | Core temp highest
                    '271' => '4.5',                                                                 // string  | Min flow
                    '272' => '5.5',                                                                 // string  | Max flow
                    '273' => '85',                                                                  // string  | Min PO2
                    '274' => '200',                                                                 // string  | Max PO2
                    '275' => '55',                                                                  // string  | Min pressure
                    '276' => '75',                                                                  // string  | Max pressure
                    '197' => '2.1',                                                                 // string  | Serum lactate during surgery
                    '198' => '1.4',                                                                 // string  | Serum lactate after surgery
                    '199' => 'No',                                                                  // select  | Abnormal events during surgery
                    '200' => null,                                                                  // string  | Abnormal events detail
                    '201' => 'Blood',                                                               // string  | Cardioplegia 1
                    '202' => null,                                                                  // string  | Cardioplegia 2
                    '203' => null,                                                                  // string  | Cardioplegia 3
                    '204' => null,                                                                  // string  | Cardioplegia 4
                    '208' => '7.38',                                                                // string  | Preop pH
                    '209' => '24',                                                                  // string  | Preop HCO3
                    '210' => '40',                                                                  // string  | Preop pCO2
                    '211' => '20',                                                                  // string  | Postop HCO3
                    '212' => '7.32',                                                                // string  | Postop pH
                    '213' => '44',                                                                  // string  | Postop pCO2
                    '214' => '120',                                                                 // string  | Immediate postop SBP
                    '215' => '75',                                                                  // string  | Immediate postop DBP
                    '225' => ['Yes'],                                                               // multiple | Blood transfusion — matched
                    '226' => ['answers' => ['RBCs', 'Plasma', 'Others'], 'others_text' => 'albumin infusion'], // multiple | Blood components — with others_text
                    '227' => '2',                                                                   // string  | Units of RBCs
                    '228' => '1',                                                                   // string  | Units of plasma
                    '229' => null,                                                                  // string  | Units of platelets
                    '230' => null,                                                                  // string  | Units of whole blood
                ];
            }

            // Section 11: GO-Patients — date, multiple (no Others), select Yes/No
            if ($sectionId === 11) {
                return [
                    '234' => '2024-06-10',                                                          // date    | Date on presentation
                    '235' => '3',                                                                   // string  | Gravidity
                    '236' => '2',                                                                   // string  | Parity
                    '237' => ['Post_Partum'],                                                       // multiple | State at presentation — matched
                    '238' => ['Hospital (Inpatient Ward)'],                                         // multiple | Where received medical care — matched
                    '239' => ['Yes'],                                                               // multiple | Antenatal care — matched
                    '240' => ['Yes (Postpartum)'],                                                  // multiple | Preeclampsia/eclampsia — matched
                    '241' => ['No'],                                                                // multiple | Past history preeclampsia — matched
                    '242' => ['Yes (postpartum)'],                                                  // multiple | Obstetric hemorrhages — matched
                    '243' => 'Yes',                                                                 // select  | Other organ failure — matched
                    '244' => 'Acute liver injury',                                                  // string  | Other organ failure detail
                    '245' => ['No'],                                                                // multiple | Past history similar attacks — matched
                    '246' => 'Yes',                                                                 // select  | Cesarean section — matched
                    '247' => 'Yes',                                                                 // select  | Oliguric at presentation — matched
                    '249' => '850',                                                                 // string  | 24h urinary protein
                    '250' => ['++'],                                                                // multiple | Protein dipstick — matched
                    '253' => ['Alive'],                                                             // multiple | Maternal outcome — matched
                    '254' => ['Live preterm'],                                                      // multiple | Fetal outcome — matched
                    '255' => ['Yes'],                                                               // multiple | Neonatal ICU available — matched
                ];
            }

            // All other sections: return empty map so formatResponse produces all-null answers.
            // This validates the formatting pipeline runs correctly for any section_id.
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model'           => 'gpt-4o-mini',
            'messages'        => [
                [
                    'role'    => 'system',
                    'content' => 'You are a medical data extraction assistant. Always respond with valid JSON only.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature'     => 0.1,
        ]);

        if (! $response->successful()) {
            $body = $response->body();
            Log::error('GPT-4o-mini API Error', array_filter([
                'status'         => $response->status(),
                'response_bytes' => strlen($body),
                'response_hash'  => substr(hash('sha256', $body), 0, 12),
                'request_id'     => $response->header('x-request-id'),
                'body'           => config('app.debug') ? $body : null,
            ]));
            throw new \Exception('Failed to extract medical data from transcript.');
        }

        $content = $response->json('choices.0.message.content');
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('GPT response JSON parse error', array_filter([
                'json_error'     => json_last_error_msg(),
                'content_bytes'  => strlen((string) $content),
                'content_hash'   => substr(hash('sha256', (string) $content), 0, 12),
                'content'        => config('app.debug') ? $content : null,
            ]));
            throw new \Exception('Failed to parse AI response.');
        }

        return $decoded;
    }

    /**
     * Format the extracted data into the exact same structure returned by
     * SectionManagementService::getQuestionsAndAnswers() / formatAnswerByType().
     *
     * - select  → ['answers' => 'Value',         'other_field' => null]
     * - multiple → ['answers' => ['V1', 'V2'],    'other_field' => null]
     * - others  → raw value string or null
     */
    private function formatResponse(Collection $questions, array $extractedData): array
    {
        $data = [];

        foreach ($questions as $question) {
            $rawAnswer = $extractedData[(string) $question->id] ?? null;

            $questionData = [
                'id'            => $question->id,
                'question'      => $question->question,
                'values'        => $question->values,
                'type'          => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory'     => $question->mandatory,
                'hidden'        => $question->hidden,
                'updated_at'    => $question->updated_at,
            ];

            switch ($question->type) {
                case 'select':
                    $questionData['answer'] = $this->formatSelectAnswer($rawAnswer, $question->values ?? []);
                    break;

                case 'multiple':
                    $questionData['answer'] = $this->formatMultipleAnswer($rawAnswer, $question->values ?? []);
                    break;

                default: // string, text, date → raw value or null
                    // Cast to string if GPT returned a number (e.g. age as integer 64 → "64")
                    $questionData['answer'] = is_int($rawAnswer) || is_float($rawAnswer)
                        ? (string) $rawAnswer
                        : $rawAnswer;
                    break;
            }

            $data[] = $questionData;
        }

        return $data;
    }

    /**
     * Format a select answer.
     *
     * GPT returns one of:
     *   "Exact Value"                          → matched allowed value
     *   {"value": "raw text", "is_other": true} → unmatched, question has "Others"
     *   null                                    → not found
     *
     * Output always matches production format:
     *   {"answers": "Value",  "other_field": null}
     *   {"answers": "Others", "other_field": "raw text"}
     *   {"answers": null,     "other_field": null}
     */
    private function formatSelectAnswer(mixed $rawAnswer, array $allowedValues): array
    {
        // Normalize: find the actual "other" entry regardless of casing ("Others", "Other", "others")
        $othersValue = null;
        foreach ($allowedValues as $v) {
            if (strcasecmp($v, 'others') === 0 || strcasecmp($v, 'other') === 0) {
                $othersValue = $v;
                break;
            }
        }
        $hasOthers = $othersValue !== null;

        // GPT signalled an unmatched value → put in other_field
        if (is_array($rawAnswer) && isset($rawAnswer['is_other']) && $rawAnswer['is_other'] === true) {
            if ($hasOthers) {
                return [
                    'answers'     => $othersValue,
                    'other_field' => $rawAnswer['value'] ?? null,
                ];
            }
            // Question has no "Others" option — discard
            return ['answers' => null, 'other_field' => null];
        }

        // Plain string — validate it exists in allowed_values
        if (is_string($rawAnswer) && in_array($rawAnswer, $allowedValues, true)) {
            return ['answers' => $rawAnswer, 'other_field' => null];
        }

        // Null or unrecognised shape
        return ['answers' => null, 'other_field' => null];
    }

    /**
     * Format a multiple answer.
     *
     * GPT returns one of:
     *   ["Val1", "Val2"]                                        → all matched
     *   {"answers": ["Val1", "Others"], "others_text": "raw"}  → some unmatched
     *   []                                                      → nothing found
     *
     * Output always matches production format:
     *   {"answers": ["Val1", "Val2"], "other_field": null}
     *   {"answers": ["Val1", "Others"], "other_field": "raw text"}
     *   {"answers": [],               "other_field": null}
     */
    private function formatMultipleAnswer(mixed $rawAnswer, array $allowedValues): array
    {
        // Normalize: find the actual "other" entry regardless of casing ("Others", "Other", "others")
        $othersValue = null;
        foreach ($allowedValues as $v) {
            if (strcasecmp($v, 'others') === 0 || strcasecmp($v, 'other') === 0) {
                $othersValue = $v;
                break;
            }
        }
        $hasOthers = $othersValue !== null;

        // GPT returned the {answers, others_text} structure
        if (is_array($rawAnswer) && array_key_exists('answers', $rawAnswer)) {
            $answers    = is_array($rawAnswer['answers']) ? $rawAnswer['answers'] : [];
            $othersText = $rawAnswer['others_text'] ?? null;

            // Filter answers array to only valid allowed_values
            $validAnswers = array_values(array_filter($answers, fn ($v) => in_array($v, $allowedValues, true)));

            // Ensure the actual others value is in the list if there's an others_text
            if ($othersText !== null && $hasOthers && ! in_array($othersValue, $validAnswers, true)) {
                $validAnswers[] = $othersValue;
            }

            return [
                'answers'     => $validAnswers,
                'other_field' => ($hasOthers && $othersText !== null) ? $othersText : null,
            ];
        }

        // Plain array — filter to valid allowed_values only
        if (is_array($rawAnswer)) {
            $validAnswers = array_values(array_filter($rawAnswer, fn ($v) => in_array($v, $allowedValues, true)));
            return ['answers' => $validAnswers, 'other_field' => null];
        }

        // Null or unrecognised shape
        return ['answers' => [], 'other_field' => null];
    }
}
