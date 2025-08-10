<?php

namespace App\Modules\Questions\Services;

use App\Models\Assessment;
use App\Models\Cause;
use App\Models\Complaint;
use App\Models\Decision;
use App\Models\Examination;
use App\Models\Outcome;
use App\Models\PatientHistory;
use App\Models\Risk;
use App\Models\SectionFieldMapping;
use App\Modules\Questions\Models\Questions;
use Illuminate\Support\Facades\Log;

class QuestionService
{
    /**
     * Get all questions.
     */
    public function getAllQuestions(): array
    {
        $questions = Questions::all();

        if ($questions->isEmpty()) {
            Log::warning('No questions found.');

            return [
                'data' => [
                    'value' => false,
                    'message' => 'No questions found.',
                ],
                'status_code' => 404,
            ];
        }

        return [
            'data' => [
                'value' => true,
                'data' => $questions,
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Store a new question.
     */
    public function storeQuestion(array $data): array
    {
        try {
            // Ensure required fields have default values if not provided
            $data = array_merge([
                'mandatory' => false,
                'hidden' => false,
                'skip' => false,
                'sort' => 0,
            ], $data);

            // Convert values to JSON string if it's an array
            if (isset($data['values']) && is_array($data['values'])) {
                $data['values'] = json_encode($data['values']);
            }

            $question = Questions::create($data);

            Log::info("Question stored successfully. ID: {$question->id}");

            return [
                'data' => [
                    'value' => true,
                    'data' => $question,
                ],
                'status_code' => 201,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to store question: '.$e->getMessage(), [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'data' => [
                    'value' => false,
                    'message' => 'Failed to create question: '.$e->getMessage(),
                ],
                'status_code' => 500,
            ];
        }
    }

    /**
     * Get questions by section ID.
     */
    public function getQuestionsBySection(int $sectionId): array
    {
        // Fetch questions dynamically based on section_id
        $questions = Questions::where('section_id', $sectionId)
            ->where('hidden', false)
            ->orderBy('sort')
            ->get();

        // Check if questions are found
        if ($questions->isEmpty()) {
            Log::warning("No questions found for section ID: {$sectionId}");

            return [
                'data' => [
                    'value' => false,
                    'message' => 'No questions found for the given section ID.',
                ],
                'status_code' => 404,
            ];
        }

        $data = [];
        foreach ($questions as $question) {
            if ($question->skip) {
                Log::info("Question with ID {$question->id} skipped as per skip flag.");

                continue;
            }

            // Construct question data
            $questionData = [
                'id' => $question->id,
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'updated_at' => $question->updated_at,
            ];

            // Add question data to the response data
            $data[] = $questionData;
        }

        Log::info("Questions retrieved successfully for section ID: {$sectionId}");

        return [
            'data' => [
                'value' => true,
                'data' => $data,
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Get questions and answers for a specific section and patient.
     */
    public function getQuestionsWithAnswers(int $sectionId, int $patientId): array
    {
        $data = [];

        // Fetch questions dynamically based on section_id
        $questions = Questions::where('section_id', $sectionId)
            ->orderBy('sort')
            ->get();

        foreach ($questions as $question) {
            // Skip questions based on the skip flag
            if ($question->skip) {
                Log::info("Question with ID {$question->id} skipped as per skip flag.");

                continue;
            }

            // Get the answers model for the section
            $answersModel = $this->getAnswersModel($sectionId);
            if (! $answersModel) {
                Log::warning("No answer model found for section ID {$sectionId}.");

                continue;
            }

            // Adjust patient_id column based on section_id
            $patientIdColumn = $sectionId == 1 ? 'id' : 'patient_id';
            $answers = $answersModel::where($patientIdColumn, $patientId)->first();

            // Get the main answer column name dynamically
            $mainAnswerColumnName = $this->getAnswerColumnName($question->id);

            // Construct the other field column name by appending '_other_field' to the main answer column name
            $otherFieldColumnName = $mainAnswerColumnName.'_other_field';

            // Check if the question is hidden and handle accordingly
            $hasAnswer = ! empty($answers->$mainAnswerColumnName) ||
                ! empty($answers->$otherFieldColumnName);

            if ($question->hidden && ! $hasAnswer) {
                // Skip the question if it's hidden and has no answer
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

            // Check for 'Others' in values and type is 'select'
            // Existing logic for multiple choice and select questions
            if ($question->type === 'multiple' || $question->type === 'select') {
                $questionData['answer'] = [
                    'answers' => $answers->$mainAnswerColumnName ?? null,
                    'other_field' => $answers->$otherFieldColumnName ?? null,
                ];
            } else {
                // For other question types, assign the main answer directly
                $questionData['answer'] = $answers->$mainAnswerColumnName ?? null;
            }

            $data[] = $questionData;
        }

        Log::info("Questions and answers retrieved successfully for section ID {$sectionId} and patient ID {$patientId}.");

        return [
            'data' => [
                'value' => true,
                'data' => $data,
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Update a question.
     */
    public function updateQuestion(int $id, array $data): array
    {
        try {
            $question = Questions::find($id);

            if (! $question) {
                Log::warning("No questions found for update. ID: {$id}");

                return [
                    'data' => [
                        'value' => false,
                        'message' => 'No questions found.',
                    ],
                    'status_code' => 404,
                ];
            }

            // Convert values to JSON string if it's an array
            if (isset($data['values']) && is_array($data['values'])) {
                $data['values'] = json_encode($data['values']);
            }

            $question->update($data);

            Log::info("Questions updated successfully. ID: {$id}");

            return [
                'data' => [
                    'value' => true,
                    'data' => $question,
                    'message' => 'Questions updated successfully.',
                ],
                'status_code' => 200,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update question: '.$e->getMessage(), [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'data' => [
                    'value' => false,
                    'message' => 'Failed to update question: '.$e->getMessage(),
                ],
                'status_code' => 500,
            ];
        }
    }

    /**
     * Delete a question.
     */
    public function deleteQuestion(int $id): array
    {
        $question = Questions::find($id);

        if (! $question) {
            Log::warning("No questions found for deletion. ID: {$id}");

            return [
                'data' => [
                    'value' => false,
                    'message' => 'No questions found.',
                ],
                'status_code' => 404,
            ];
        }

        $question->delete();

        Log::info("Questions deleted successfully. ID: {$id}");

        return [
            'data' => [
                'value' => true,
                'message' => 'Questions deleted successfully.',
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Get the model class for answers based on section ID.
     */
    private function getAnswersModel(int $sectionId): ?string
    {
        switch ($sectionId) {
            case 1:
                return PatientHistory::class;
            case 2:
                return Complaint::class;
            case 3:
                return Cause::class;
            case 4:
                return Risk::class;
            case 5:
                return Assessment::class;
            case 6:
                return Examination::class;
            case 7:
                return Decision::class;
            case 8:
                return Outcome::class;
            default:
                return null;
        }
    }

    /**
     * Get answer column name for a question ID.
     */
    private function getAnswerColumnName(int $questionId): string
    {
        // Fetch column name from SectionFieldMapping model
        $columnName = SectionFieldMapping::where('field_name', $questionId)
            ->value('column_name');

        return $columnName ? $columnName : 'column_'.$questionId;
    }
}
