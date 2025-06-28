<?php

namespace App\Modules\Comments\Services;

use App\Modules\Comments\Models\Comment;
use App\Modules\Patients\Models\Patients;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Comments\Services\CommentNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentService
{
    protected $commentNotificationService;

    public function __construct(CommentNotificationService $commentNotificationService)
    {
        $this->commentNotificationService = $commentNotificationService;
    }

    /**
     * Get all comments with doctor relationships
     *
     * @return array
     */
    public function getAllComments(): array
    {
        try {
            $comments = Comment::with('doctor:id,name,lname,workingplace')->latest()->get();

            if ($comments->isEmpty()) {
                return [
                    'data' => [
                        'value' => false,
                        'message' => 'No comments were found',
                    ],
                    'status_code' => 404
                ];
            }

            return [
                'data' => [
                    'value' => true,
                    'data' => $comments,
                ],
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving all comments: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new comment
     *
     * @param array $data
     * @return array
     */
    public function createComment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            try {
                $patient = $this->validatePatientExists($data['patient_id']);
                $doctorId = Auth::id();

                $comment = Comment::create([
                    'doctor_id' => $doctorId,
                    'patient_id' => $data['patient_id'],
                    'content' => $data['content'],
                ]);

                // Update patient timestamp
                $this->updatePatientTimestamp($data['patient_id']);

                // Handle notifications
                $this->commentNotificationService->handleCommentNotification(
                    $comment,
                    $patient,
                    $doctorId
                );

                Log::info('New comment created', [
                    'comment_id' => $comment->id,
                    'patient_id' => $data['patient_id'],
                    'doctor_id' => $doctorId,
                ]);

                return [
                    'data' => [
                        'value' => true,
                        'message' => 'Comment created successfully',
                    ],
                    'status_code' => 200
                ];
            } catch (\Exception $e) {
                Log::error('Error creating comment: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Get comments by patient ID
     *
     * @param int $patientId
     * @return array
     */
    public function getCommentsByPatient(int $patientId): array
    {
        try {
            $comments = Comment::where('patient_id', $patientId)
                ->select('id', 'doctor_id', 'content', 'updated_at')
                ->with('doctor:id,name,lname,workingplace,image')
                ->get();

            return [
                'data' => [
                    'value' => true,
                    'data' => $comments,
                ],
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            Log::error("Error retrieving comments for patient ID {$patientId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a comment
     *
     * @param int $commentId
     * @param array $data
     * @return array
     */
    public function updateComment(int $commentId, array $data): array
    {
        try {
            $comment = Comment::find($commentId);

            if (!$comment) {
                return [
                    'data' => [
                        'value' => false,
                        'message' => 'Comment not found',
                    ],
                    'status_code' => 404
                ];
            }

            $comment->update($data);

            Log::info('Comment updated', [
                'comment_id' => $comment->id,
                'patient_id' => $comment->patient_id,
                'doctor_id' => Auth::id(),
            ]);

            return [
                'data' => [
                    'value' => true,
                    'data' => $comment,
                    'message' => 'Comment updated successfully',
                ],
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            Log::error("Error updating comment ID {$commentId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a comment
     *
     * @param int $commentId
     * @return array
     */
    public function deleteComment(int $commentId): array
    {
        try {
            $comment = Comment::find($commentId);

            if (!$comment) {
                return [
                    'data' => [
                        'value' => false,
                        'message' => 'Comment not found',
                    ],
                    'status_code' => 404
                ];
            }

            $commentInfo = [
                'comment_id' => $comment->id,
                'patient_id' => $comment->patient_id,
                'doctor_id' => Auth::id(),
            ];

            $comment->delete();

            Log::info('Comment deleted', $commentInfo);

            return [
                'data' => [
                    'value' => true,
                    'message' => 'Comment deleted successfully',
                ],
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            Log::error("Error deleting comment ID {$commentId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate that patient exists
     *
     * @param int $patientId
     * @return \App\Modules\Patients\Models\Patients
     * @throws \Exception
     */
    private function validatePatientExists(int $patientId): Patients
    {
        $patient = Patients::find($patientId);

        if (!$patient) {
            throw new \Exception('Patient not found', 404);
        }

        return $patient;
    }

    /**
     * Update patient timestamp
     *
     * @param int $patientId
     * @return void
     */
    private function updatePatientTimestamp(int $patientId): void
    {
        Patients::where('id', $patientId)->update(['updated_at' => now()]);
    }
}
