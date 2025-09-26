<?php

namespace App\Services;

use App\Models\FeedPost;
use App\Models\Group;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Patients\Models\Patients;
use Illuminate\Support\Facades\Log;

class ShareUrlService
{
    /**
     * Generate shareable URL for a post
     */
    public function generatePostUrl(int $postId): array
    {
        try {
            $post = FeedPost::find($postId);

            if (! $post) {
                throw new \Exception('Post not found');
            }

            return [
                'success' => true,
                'url' => url("/post/$postId"),
                'deeplink' => "egyakin://post/$postId",
                'type' => 'post',
                'id' => $postId,
            ];
        } catch (\Exception $e) {
            Log::error('Error generating post share URL', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate shareable URL for a patient
     */
    public function generatePatientUrl(int $patientId): array
    {
        try {
            $patient = Patients::find($patientId);

            if (! $patient) {
                throw new \Exception('Patient not found');
            }

            return [
                'success' => true,
                'url' => url("/patient/$patientId"),
                'deeplink' => "egyakin://patient/$patientId",
                'type' => 'patient',
                'id' => $patientId,
            ];
        } catch (\Exception $e) {
            Log::error('Error generating patient share URL', [
                'patient_id' => $patientId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate shareable URL for a group
     */
    public function generateGroupUrl(int $groupId): array
    {
        try {
            $group = Group::find($groupId);

            if (! $group) {
                throw new \Exception('Group not found');
            }

            return [
                'success' => true,
                'url' => url("/group/$groupId"),
                'deeplink' => "egyakin://group/$groupId",
                'type' => 'group',
                'id' => $groupId,
            ];
        } catch (\Exception $e) {
            Log::error('Error generating group share URL', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate shareable URL for a consultation
     */
    public function generateConsultationUrl(int $consultationId): array
    {
        try {
            $consultation = Consultation::find($consultationId);

            if (! $consultation) {
                throw new \Exception('Consultation not found');
            }

            return [
                'success' => true,
                'url' => url("/consultation/$consultationId"),
                'deeplink' => "egyakin://consultation/$consultationId",
                'type' => 'consultation',
                'id' => $consultationId,
            ];
        } catch (\Exception $e) {
            Log::error('Error generating consultation share URL', [
                'consultation_id' => $consultationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate multiple share URLs at once
     */
    public function generateBulkUrls(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            $type = $item['type'] ?? null;
            $id = $item['id'] ?? null;

            if (! $type || ! $id) {
                continue;
            }

            switch ($type) {
                case 'post':
                    $results[] = $this->generatePostUrl($id);
                    break;
                case 'patient':
                    $results[] = $this->generatePatientUrl($id);
                    break;
                case 'group':
                    $results[] = $this->generateGroupUrl($id);
                    break;
                case 'consultation':
                    $results[] = $this->generateConsultationUrl($id);
                    break;
                default:
                    $results[] = [
                        'success' => false,
                        'error' => "Unknown content type: $type",
                    ];
            }
        }

        return $results;
    }

    /**
     * Get preview data for sharing
     */
    public function getPreviewData(string $type, int $id): array
    {
        try {
            switch ($type) {
                case 'post':
                    return $this->getPostPreviewData($id);
                case 'patient':
                    return $this->getPatientPreviewData($id);
                case 'group':
                    return $this->getGroupPreviewData($id);
                case 'consultation':
                    return $this->getConsultationPreviewData($id);
                default:
                    throw new \Exception("Unknown content type: $type");
            }
        } catch (\Exception $e) {
            Log::error('Error getting preview data', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get post preview data
     */
    private function getPostPreviewData(int $postId): array
    {
        $post = FeedPost::with(['doctor:id,name,lname,image,isSyndicateCardRequired'])
            ->find($postId);

        if (! $post) {
            throw new \Exception('Post not found');
        }

        return [
            'success' => true,
            'title' => $this->formatDoctorName($post->doctor).' - EGYAKIN Post',
            'description' => $this->truncateText($post->content ?? 'Medical post on EGYAKIN', 160),
            'image' => $post->image ?? 'https://test.egyakin.com/storage/profile_images/profile_image.jpg',
            'url' => url("/post/$postId"),
        ];
    }

    /**
     * Get patient preview data
     */
    private function getPatientPreviewData(int $patientId): array
    {
        $patient = Patients::with([
            'doctor:id,name,lname,image,isSyndicateCardRequired',
            'answers' => function ($query) {
                $query->whereIn('question_id', [1, 2, 11]);
            },
        ])->find($patientId);

        if (! $patient) {
            throw new \Exception('Patient not found');
        }

        $patientName = optional($patient->answers->where('question_id', 1)->first())->answer ?? 'Patient';
        $hospital = optional($patient->answers->where('question_id', 2)->first())->answer ?? '';

        return [
            'success' => true,
            'title' => "$patientName - EGYAKIN Patient",
            'description' => 'Medical case by '.$this->formatDoctorName($patient->doctor).
                           ($hospital ? " at $hospital" : '').' on EGYAKIN medical platform',
            'image' => 'https://test.egyakin.com/storage/profile_images/profile_image.jpg',
            'url' => url("/patient/$patientId"),
        ];
    }

    /**
     * Get group preview data
     */
    private function getGroupPreviewData(int $groupId): array
    {
        $group = Group::with(['owner:id,name,lname,image,isSyndicateCardRequired'])
            ->find($groupId);

        if (! $group) {
            throw new \Exception('Group not found');
        }

        return [
            'success' => true,
            'title' => $group->name.' - EGYAKIN Group',
            'description' => ($group->description ?? 'Medical group on EGYAKIN').
                           ' | Created by '.$this->formatDoctorName($group->owner),
            'image' => $group->image ?? asset('images/egyakin-logo.png'),
            'url' => url("/group/$groupId"),
        ];
    }

    /**
     * Get consultation preview data
     */
    private function getConsultationPreviewData(int $consultationId): array
    {
        $consultation = Consultation::with([
            'doctor:id,name,lname,image,isSyndicateCardRequired',
            'patient.answers' => function ($query) {
                $query->where('question_id', 1);
            },
        ])->find($consultationId);

        if (! $consultation) {
            throw new \Exception('Consultation not found');
        }

        $patientName = optional($consultation->patient->answers->first())->answer ?? 'Patient';

        return [
            'success' => true,
            'title' => "Medical Consultation - $patientName - EGYAKIN",
            'description' => 'Medical consultation by '.$this->formatDoctorName($consultation->doctor).
                           " for $patientName on EGYAKIN medical platform",
            'image' => 'https://test.egyakin.com/storage/profile_images/profile_image.jpg',
            'url' => url("/consultation/$consultationId"),
        ];
    }

    /**
     * Format doctor name with Dr. prefix if verified
     */
    private function formatDoctorName($doctor): string
    {
        if (! $doctor) {
            return 'Doctor';
        }

        $fullName = trim($doctor->name.' '.($doctor->lname ?? ''));

        if ($doctor->isSyndicateCardRequired === 'Verified') {
            return 'Dr. '.$fullName;
        }

        return $fullName;
    }

    /**
     * Truncate text to specified length
     */
    private function truncateText(string $text, int $length = 160): string
    {
        $text = strip_tags($text);

        return strlen($text) > $length ? substr($text, 0, $length).'...' : $text;
    }
}
