<?php

namespace App\Services;

use App\Modules\Questions\Models\Questions;
use Illuminate\Support\Facades\Log;

class QuestionService
{
    /**
     * Get filter conditions for patient filtering
     */
    public function getFilterConditions(): array
    {
        $questions = Questions::whereIn('id', [1, 2, 4, 8, 168, 162, 26, 86, 156, 79, 82])
            ->where('skip', false)
            ->orderBy('id')
            ->get();

        $data = [];

        foreach ($questions as $question) {
            $data[] = [
                'id' => $question->id,
                'condition' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
            ];
        }

        // Add static questions
        $staticQuestions = [
            [
                "id" => 9901,
                "condition" => "Final submit",
                "values" => ["Yes", "No"],
                "type" => "checkbox",
                "keyboard_type" => null,
            ],
            [
                "id" => 9902,
                "condition" => "Outcome",
                "values" => ["Yes", "No"],
                "type" => "checkbox",
                "keyboard_type" => null,
            ]
        ];

        return array_merge($data, $staticQuestions);
    }

    /**
     * Get all questions with their answers for a specific patient
     */
    public function getQuestionsWithAnswersForPatient(int $patientId): array
    {
        $questions = Questions::orderBy('section_id')->orderBy('sort')->get();
        $answers = \App\Models\Answers::where('patient_id', $patientId)
            ->whereIn('question_id', $questions->pluck('id'))
            ->get();

        $data = [];

        foreach ($questions as $question) {
            if ($question->skip) {
                Log::info("Question with ID {$question->id} skipped as per skip flag.");
                continue;
            }

            $answer = $answers->where('question_id', $question->id)->first();

            if ($question->hidden && !$answer) {
                Log::info("Hidden question with ID {$question->id} skipped due to no answer.");
                continue;
            }

            $questionData = [
                'id' => $question->id,
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'hidden' => $question->hidden,
                'updated_at' => $question->updated_at,
            ];

            $questionData['answer'] = $this->formatAnswerByType($question, $answers, $question->id);
            $data[] = $questionData;
        }

        return $data;
    }

    /**
     * Format answer based on question type
     */
    private function formatAnswerByType($question, $answers, int $questionId)
    {
        $questionAnswers = $answers->where('question_id', $questionId);

        switch ($question->type) {
            case 'select':
            case 'multiple':
                $answerData = [
                    'answers' => null,
                    'other_field' => null,
                ];

                foreach ($questionAnswers as $ans) {
                    if ($ans->type !== 'other') {
                        // Check if the answer is already an array
                        if (is_array($ans->answer)) {
                            $answerData['answers'] = $ans->answer;
                        } else {
                            // Check if the answer is JSON encoded
                            $decodedAnswer = json_decode($ans->answer, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedAnswer)) {
                                // Answer is JSON encoded, use the decoded array
                                $answerData['answers'] = $decodedAnswer;
                            } else {
                                // Answer is a plain string, use it directly
                                $answerData['answers'] = $ans->answer;
                            }
                        }
                    }
                    if ($ans->type === 'other') {
                        $answerData['other_field'] = $ans->answer;
                    }
                }

                return $answerData;

            case 'files':
                $answer = $questionAnswers->first();
                if (!$answer) {
                    Log::info("PDF Debug - No answer found for file question $questionId");
                    return [];
                }

                Log::info("PDF Debug - Raw file answer for question $questionId: " . print_r($answer->answer, true));
                Log::info("PDF Debug - Raw file answer type for question $questionId: " . gettype($answer->answer));
                
                // Check if the answer is already a comma-separated string
                if (is_string($answer->answer) && strpos($answer->answer, ',') !== false) {
                    Log::info("PDF Debug - Answer appears to be comma-separated string, splitting it");
                    $filePaths = array_map('trim', explode(',', $answer->answer));
                } else {
                    $filePaths = json_decode($answer->answer);
                }
                
                Log::info("PDF Debug - Decoded file paths for question $questionId: " . print_r($filePaths, true));
                
                if (!is_array($filePaths)) {
                    Log::info("PDF Debug - File paths is not array for question $questionId, type: " . gettype($filePaths));
                    return [];
                }

                $fileUrls = [];
                foreach ($filePaths as $filePath) {
                    $fileUrls[] = \Illuminate\Support\Facades\Storage::disk('public')->url($filePath);
                }
                
                Log::info("PDF Debug - Final file URLs for question $questionId: " . print_r($fileUrls, true));
                return $fileUrls;

            default:
                $answer = $questionAnswers->first();
                return $answer ? $answer->answer : null;
        }
    }
}
