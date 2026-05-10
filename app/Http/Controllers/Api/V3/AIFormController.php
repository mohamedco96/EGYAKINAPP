<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessAISectionRequest;
use App\Models\PatientSectionAiLog;
use App\Services\AIFormService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AIFormController extends Controller
{
    protected AIFormService $aiFormService;

    public function __construct(AIFormService $aiFormService)
    {
        $this->aiFormService = $aiFormService;
    }

    /**
     * Generic AI-to-form endpoint for a single section.
     * Accepts either an `audio` file (voice) or image/PDF files (lab/radiology).
     *
     * POST /api/v3/ai-form/process-section
     *   form-data:
     *     audio      → mp3/wav/m4a/ogg/webm            (required if no images/files)
     *     images[]   → jpg/jpeg/png/webp ×1–10         (required if no audio)
     *     files[]    → pdf ×1–10                       (optional, combined with images)
     *     section_id → integer
     *
     * images[] and files[] can be sent together (combined max 10 files).
     *
     * The controller resolves the input type and converts it to text,
     * then delegates to the same input-agnostic processSection() pipeline.
     *
     * Response always contains:
     *   value, input_type, image_count (for image mode), extracted_text, data[]
     */
    public function processSection(ProcessAISectionRequest $request)
    {
        try {
            $sectionId = (int) $request->input('section_id');

            // Resolve input type and convert to plain text
            if ($request->hasFile('audio')) {
                $extractedText = $this->aiFormService->transcribeAudio(
                    $request->file('audio')
                );
                $inputType = 'audio';
                $imageCount = null;
            } else {
                $imageFiles = array_merge(
                    (array) $request->file('images'),
                    (array) $request->file('files')
                );
                $extractedText = $this->aiFormService->analyzeImage($imageFiles);
                $inputType = 'image';
                $imageCount = count($imageFiles);
            }

            // Run the input-agnostic extraction pipeline
            $result = $this->aiFormService->processSection($extractedText, $sectionId);

            PatientSectionAiLog::create([
                'patient_id' => $request->input('patient_id') ?: null,
                'section_id' => $sectionId,
                'doctor_id' => Auth::id(),
                'input_type' => $inputType,
                'extracted_text' => $extractedText,
                'prompt' => $result['prompt'],
                'response' => $result['data'],
            ]);

            Log::info('AI form extraction completed', array_filter([
                'input_type' => $inputType,
                'image_count' => $imageCount,
                'section_id' => $sectionId,
                'doctor_id' => Auth::id(),
                'questions_processed' => count($result['data']),
            ]));

            $response = array_filter([
                'value' => true,
                'input_type' => $inputType,
                'image_count' => $imageCount,
                'extracted_text' => $extractedText,
                'data' => $result['data'],
            ], fn ($v) => $v !== null);
            $response['data'] = $result['data']; // ensure data key always present even if empty

            if (config('app.debug')) {
                $response['debug_prompt'] = $result['prompt'];
            }

            return response()->json($response, 200);

        } catch (Exception $e) {
            Log::error('AI form extraction error', [
                'section_id' => $request->input('section_id'),
                'doctor_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while processing your request. Please try again later.',
            ], 500);
        }
    }
}
