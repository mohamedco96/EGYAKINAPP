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

            $questionsDescription[] = $desc;
        }

        $questionsJson = json_encode($questionsDescription, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a medical data extraction assistant. A doctor has dictated patient information, and your job is to extract structured data from the transcript and map it to the questions below.

CRITICAL RULES — you MUST follow these exactly:
1. Return a JSON object where each key is a question ID (as a string) and the value is the extracted answer.
2. For "select" type: return EXACTLY one string from the "allowed_values" list, or the JSON literal null if not found.
3. For "multiple" type: return an ARRAY of strings, each EXACTLY matching an item from "allowed_values". Return an empty array [] if nothing found.
4. For "string" or "text" type: return the extracted text as a string, or the JSON literal null if not found.
5. For "date" type: return the date as a string in YYYY-MM-DD format (e.g., "2024-03-15"), or the JSON literal null if not found.
6. IMPORTANT: If no information is found for a question, you MUST return the JSON literal null — NOT the string "null", NOT an empty string "".
7. Use your medical knowledge to map abbreviations, acronyms, and synonyms in the transcript to the EXACT strings in the allowed_values list. Examples: "ICU" → "Intensive Care Unit", "HTN" → "Hypertension", "DM" → "Diabetes Mellitus", "CAD" → "Coronary Artery Disease", "CKD" → "Chronic Kidney Disease". Apply this reasoning for any medical abbreviation encountered.
8. Match allowed_values EXACTLY (case-sensitive). Do not rephrase or paraphrase values.
9. Do not invent or guess data that is not present in the transcript.

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
            // This simulates the flat map GPT returns: {question_id => extracted_value}.
            // select   → single string from allowed_values
            // multiple → array of strings from allowed_values
            // string   → raw string value
            return [
                '1'   => 'Ahmed Mohamed',               // string  | Name
                '2'   => 'MUH-14',                      // select  | Hospital
                '3'   => 'Patient himself',             // select  | Collected data from
                '4'   => '29901011234567',              // string  | National ID
                '5'   => '01012345678',                 // string  | Phone
                '6'   => 'ahmed@example.com',           // string  | Email
                '7'   => '64',                          // string  | Age
                '8'   => 'Male',                        // select  | Gender
                '9'   => 'Retired',                     // select  | Occupation
                '10'  => 'Rural',                       // select  | Residency
                '11'  => 'Dakahlia',                    // select  | Governorate
                '12'  => 'Married',                     // select  | Marital status
                '142' => '3',                           // string  | Children
                '13'  => 'Primary school',              // select  | Educational level
                '14'  => ['Cigarette smoker', 'Others'],// multiple| Special habits
                '16'  => 'Yes',                         // select  | DM
                '17'  => '10',                          // string  | DM duration in years
                '18'  => 'Yes',                         // select  | HTN
                '19'  => '5',                           // string  | HTN duration in years
                '20'  => 'Sepsis for ICU admission',    // string  | Other
                '149' => 'No',                          // select  | Black race
                '168' => 'Others',                      // select  | Department — "Others" to test other_field
                '169' => 'Renal Transplant Unit',       // string  | Other department detail
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

            // Post-validate select/multiple: reject values not in allowed_values
            if (in_array($question->type, ['select', 'multiple']) && ! empty($question->values)) {
                $rawAnswer = $this->validateAgainstAllowedValues($question->type, $rawAnswer, $question->values);
            }

            $questionData = [
                'id'           => $question->id,
                'question'     => $question->question,
                'values'       => $question->values,
                'type'         => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory'    => $question->mandatory,
                'hidden'       => $question->hidden,
                'updated_at'   => $question->updated_at,
            ];

            // In mock mode, inject a non-null other_field for select/multiple
            // questions that have "Others" in their allowed_values, so the full
            // answer shape (including other_field) is tested end-to-end.
            // In production, other_field is always null from the AI — the doctor
            // fills it manually after the form is pre-populated.
            $mockOtherField = null;
            if (config('services.ai_form.mock') && in_array($question->type, ['select', 'multiple'])) {
                $hasOthersOption = ! empty($question->values) && in_array('Others', $question->values, true);
                if ($hasOthersOption && in_array('Others', (array) $rawAnswer, true)) {
                    $mockOtherField = 'Mock other field value';
                }
            }

            switch ($question->type) {
                case 'select':
                    $questionData['answer'] = [
                        'answers'     => $rawAnswer,
                        'other_field' => $mockOtherField,
                    ];
                    break;

                case 'multiple':
                    $questionData['answer'] = [
                        'answers'     => is_array($rawAnswer) ? $rawAnswer : [],
                        'other_field' => $mockOtherField,
                    ];
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
     * Validate that GPT-returned values exist in the question's allowed_values.
     * If a value is not found, return null (select) or filter it out (multiple).
     * Prevents garbage data from reaching the frontend.
     */
    private function validateAgainstAllowedValues(string $type, mixed $rawAnswer, array $allowedValues): mixed
    {
        if ($type === 'select') {
            if ($rawAnswer === null || ! in_array($rawAnswer, $allowedValues, true)) {
                return null;
            }

            return $rawAnswer;
        }

        if ($type === 'multiple') {
            if (! is_array($rawAnswer)) {
                return [];
            }

            return array_values(array_filter($rawAnswer, fn ($v) => in_array($v, $allowedValues, true)));
        }

        return $rawAnswer;
    }
}
