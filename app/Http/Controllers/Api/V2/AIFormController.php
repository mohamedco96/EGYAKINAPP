<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessAISectionRequest;
use App\Services\AIFormService;
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
     * Accepts either an `audio` file (voice) or an `image` file (lab/radiology).
     *
     * POST /api/v2/ai-form/process-section
     *   form-data:
     *     audio      → mp3/wav/m4a/ogg/webm  (required if no image)
     *     image      → jpg/jpeg/png/webp/pdf  (required if no audio)
     *     section_id → integer
     *     language   → 2-letter ISO code, optional, default 'en'
     *
     * The controller resolves the input type and converts it to text,
     * then delegates to the same input-agnostic processSection() pipeline.
     *
     * Response always contains:
     *   value, extracted_text (transcript or image analysis), data[]
     */
    public function processSection(ProcessAISectionRequest $request)
    {
        try {
            $sectionId = (int) $request->input('section_id');
            $language  = $request->input('language', 'en');

            // Resolve input type and convert to plain text
            if ($request->hasFile('audio')) {
                $extractedText = $this->aiFormService->transcribeAudio(
                    $request->file('audio'),
                    $language
                );
                $inputType = 'audio';
            } else {
                // image — analyzeImage() will be implemented in AIFormService when needed
                $extractedText = $this->aiFormService->analyzeImage(
                    $request->file('image')
                );
                $inputType = 'image';
            }

            // Run the input-agnostic extraction pipeline
            $result = $this->aiFormService->processSection($extractedText, $sectionId);

            Log::info('AI form extraction completed', [
                'input_type'          => $inputType,
                'section_id'          => $sectionId,
                'doctor_id'           => Auth::id(),
                'questions_processed' => count($result['data']),
            ]);

            return response()->json([
                'value'          => true,
                'input_type'     => $inputType,
                'extracted_text' => $extractedText,
                'debug_prompt'   => $result['prompt'],
                'data'           => $result['data'],
            ], 200);

        } catch (\Exception $e) {
            Log::error('AI form extraction error', [
                'section_id' => $request->input('section_id'),
                'doctor_id'  => Auth::id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'value'   => false,
                'message' => 'An error occurred while processing your request. Please try again later.',
            ], 500);
        }
    }
}
