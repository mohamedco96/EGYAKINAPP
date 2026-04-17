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
            Log::error('Whisper API Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
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
            return ['data' => []];
        }

        $prompt        = $this->buildExtractionPrompt($questions, $text);
        $extractedData = $this->extractData($prompt);
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
            ? "11. CATCH-ALL RULE: One question is marked with \"is_catch_all\": true (ID {$catchAllId}). After filling all other questions, collect any medically relevant details from the transcript that were NOT captured by any other question, and write them as a concise summary string in this field. Examples of catch-all content: reason for admission, chief complaint, serum creatinine value, diagnosis, ICU admission reason, or any other clinical detail mentioned but not covered by a specific question. If everything was already captured, return null."
            : '';

        return <<<PROMPT
You are a medical data extraction assistant. A doctor has dictated patient information, and your job is to extract structured data from the transcript and map it to the questions below.

CRITICAL RULES — you MUST follow these exactly:
1. Return a JSON object where each key is a question ID (as a string) and the value is the extracted answer.
2. For "select" type:
   a. First, use your medical knowledge to resolve any abbreviations, synonyms, or phonetic transcription errors in the transcript (e.g., "debits" → "Diabetes Mellitus", "DM" → "Diabetes Mellitus", "hypertension" → "Hypertension", "HTN" → "Hypertension", "Takahliya/Dakahlia/Dakahliya" → "Dakahlia", similar phonetic variants → correct spelling).
   b. If the resolved value EXACTLY matches one of the "allowed_values" → return that string.
   c. If relevant information IS present in the transcript but does NOT match any "allowed_values" (even after synonym resolution) AND "Others" exists in the list → return {"value": "<the original text from transcript>", "is_other": true}. This is mandatory — do NOT return null when the information exists but doesn't match.
   d. If no relevant information is found at all → return the JSON literal null.
3. For "multiple" type:
   a. First, apply the same abbreviation/synonym/phonetic resolution as rule 2a.
   b. For each resolved value, if it EXACTLY matches an item in "allowed_values" → include it in the answers array.
   c. If a resolved value does NOT match any "allowed_values" BUT "Others" exists in the list → include "Others" in the answers array AND return the format: {"answers": ["matched1", "Others"], "others_text": "<unmatched text from transcript>"}.
   d. If nothing is found → return an empty array [].
4. For "string" or "text" type: return the extracted text as a string, or the JSON literal null if not found.
5. For "date" type: return the date as a string in YYYY-MM-DD format (e.g., "2024-03-15"), or the JSON literal null if not found.
6. IMPORTANT: If no information is found for a question, you MUST return the JSON literal null — NOT the string "null", NOT an empty string "".
7. IMPORTANT: For "select" type, if information IS present in the transcript but does not match any allowed_values, you MUST use the is_other format (rule 2c). Never discard information by returning null when the information exists. Example: if transcript says "Mansoura University Hospital" and allowed_values has hospital codes like "MUH-14", since it does not match exactly but "Others" exists, return {"value": "Mansoura University Hospital", "is_other": true}.
8. Medical synonym & phonetic correction examples (apply broadly, not limited to these):
   - "ICU" or "intensive care" → "Intensive Care Unit"
   - "HTN" or "hypertension" → "Hypertension"
   - "DM" or "diabetes" or "debits" or "diabetics" → "Diabetes Mellitus"
   - "CAD" or "coronary artery" → "Coronary Artery Disease"
   - "CKD" or "chronic kidney" → "Chronic Kidney Disease"
   - "Takahliya", "Dakahlia", "Daqahliya", "Dakahliya" → try matching to the closest allowed_value for that governorate
   - Any phonetic transcription error → infer the most likely intended medical term
9. Match allowed_values EXACTLY (case-sensitive) after synonym resolution.
10. Do not invent or guess data that is not present in the transcript.
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
    private function extractData(string $prompt): array
    {
        if (config('services.ai_form.mock')) {
            Log::info('AIFormService: mock extraction active (AI_FORM_MOCK=true)');

            // Full mock for section 1 — every question has a filled value.
            // Simulates the exact map GPT would return, including all answer shapes:
            //   string                                        → plain value
            //   select matched                                → plain string from allowed_values
            //   select unmatched (has Others)                 → {"value": "...", "is_other": true}
            //   select null                                   → null
            //   multiple all matched                          → plain array
            //   multiple with unmatched (has Others)          → {"answers": [...], "others_text": "..."}
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
            Log::error('GPT-4o-mini API Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception('Failed to extract medical data from transcript.');
        }

        $content = $response->json('choices.0.message.content');
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('GPT response JSON parse error', ['content' => $content]);
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
                    $questionData['answer'] = $rawAnswer;
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
        $hasOthers = in_array('Others', $allowedValues, true);

        // GPT signalled an unmatched value → put in other_field
        if (is_array($rawAnswer) && isset($rawAnswer['is_other']) && $rawAnswer['is_other'] === true) {
            if ($hasOthers) {
                return [
                    'answers'     => 'Others',
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
        $hasOthers = in_array('Others', $allowedValues, true);

        // GPT returned the {answers, others_text} structure
        if (is_array($rawAnswer) && array_key_exists('answers', $rawAnswer)) {
            $answers    = is_array($rawAnswer['answers']) ? $rawAnswer['answers'] : [];
            $othersText = $rawAnswer['others_text'] ?? null;

            // Filter answers array to only valid allowed_values
            $validAnswers = array_values(array_filter($answers, fn ($v) => in_array($v, $allowedValues, true)));

            // Ensure "Others" is in the list if there's an others_text
            if ($othersText !== null && $hasOthers && ! in_array('Others', $validAnswers, true)) {
                $validAnswers[] = 'Others';
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
