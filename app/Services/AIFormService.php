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
        ])->attach(
            'file',
            file_get_contents($audioFile->getRealPath()),
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
            throw new \Exception('Failed to transcribe audio: ' . $response->body());
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

        return ['data' => $data];
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

            if ($question->type === 'number') {
                $desc['note'] = 'Extract as numeric value only (digits, no units).';
            }

            $questionsDescription[] = $desc;
        }

        $questionsJson = json_encode($questionsDescription, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a medical data extraction assistant. A doctor has dictated patient information, and your job is to extract structured data from the transcript and map it to the questions below.

CRITICAL RULES — you MUST follow these exactly:
1. Return a JSON object where each key is a question ID (as a string) and the value is the extracted answer.
2. For "select" type: return EXACTLY one string from the "allowed_values" list, or the JSON literal null (not the string "null") if not found.
3. For "multiple" type: return an ARRAY of strings, each EXACTLY matching an item from "allowed_values". Return an empty array [] if nothing found.
4. For "string" or "text" type: return the extracted text as a string, or the JSON literal null if not found.
5. For "number" type: return only the numeric value as a string (e.g., "64"), or the JSON literal null if not found.
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

            // Realistic mock based on the hardcoded transcript:
            // "Male patient, aged 64, from Daqahliya, admitted to MUH, ICU, sepsis."
            //
            // Rules followed exactly as real GPT would:
            //   - select  → single string from allowed_values, or null
            //   - multiple → array of strings from allowed_values, or []
            //   - string  → raw string value, or null (non-mandatory left null)
            //
            // Section 1 IDs derived from DB questions for section_id=1.
            // Non-mandatory questions with no info in transcript → null.
            return [
                '1'   => 'Test Patient',            // string  | Name (mandatory)
                '2'   => 'MUH-14',                  // select  | Hospital — "Mansoura University Hospital" → MUH-14
                '3'   => 'Patient himself',          // select  | Collected data from
                '4'   => null,                       // string  | National ID (mandatory but not in transcript)
                '5'   => null,                       // string  | Phone (mandatory but not in transcript)
                '6'   => null,                       // string  | Email (not mandatory)
                '7'   => '64',                       // string  | Age (mandatory) — "aged 64"
                '8'   => 'Male',                     // select  | Gender — "Male patient"
                '9'   => null,                       // select  | Occupation (mandatory but not in transcript)
                '10'  => 'Rural',                    // select  | Residency — "Agha" is a rural area
                '11'  => 'Dakahlia',                 // select  | Governorate — "Daqahliya" → "Dakahlia"
                '12'  => null,                       // select  | Marital status (mandatory but not in transcript)
                '142' => null,                       // string  | Children (not mandatory)
                '13'  => null,                       // select  | Educational level (mandatory but not in transcript)
                '14'  => ['NO'],                     // multiple| Special habits — none mentioned → ['NO']
                '16'  => null,                       // select  | DM (mandatory but not in transcript)
                '17'  => null,                       // string  | DM duration (not mandatory)
                '18'  => null,                       // select  | HTN (mandatory but not in transcript)
                '19'  => null,                       // string  | HTN duration (not mandatory)
                '20'  => 'Sepsis for ICU admission', // string  | Other — free text from transcript
                '149' => null,                       // select  | Black race (mandatory but not in transcript)
                '168' => 'Critical Unit',            // select  | Department — "ICU" → "Critical Unit"
                '169' => null,                       // string  | If other department (not mandatory)
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
            throw new \Exception('Failed to extract medical data from transcript: ' . $response->body());
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

            switch ($question->type) {
                case 'select':
                    $questionData['answer'] = [
                        'answers'     => $rawAnswer,
                        'other_field' => null,
                    ];
                    break;

                case 'multiple':
                    $questionData['answer'] = [
                        'answers'     => is_array($rawAnswer) ? $rawAnswer : [],
                        'other_field' => null,
                    ];
                    break;

                default: // string, number, text
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
