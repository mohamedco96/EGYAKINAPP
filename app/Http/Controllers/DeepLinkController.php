<?php

namespace App\Http\Controllers;

use App\Models\FeedPost;
use App\Models\Group;
use App\Modules\Patients\Models\Patients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeepLinkController extends Controller
{
    /**
     * Handle post deeplink with preview
     */
    public function post(Request $request, $id)
    {
        try {
            $post = FeedPost::with(['doctor:id,name,lname,image,isSyndicateCardRequired'])
                ->find($id);

            if (! $post) {
                return $this->handleNotFound($request, 'post', $id);
            }

            // Check if request is from a mobile device
            $isMobile = $this->isMobileDevice($request);

            if ($isMobile) {
                return redirect()->away("egyakin://post/$id");
            }

            // Return preview page for web/social media crawlers
            $metaData = [
                'title' => $this->formatDoctorName($post->doctor).' - EGYAKIN Post',
                'description' => $this->truncateText($post->content ?? 'Medical post on EGYAKIN', 160),
                'image' => $post->image ?? config('app.url').'/storage/profile_images/profile_image.jpg',
                'url' => url("/post/$id"),
                'type' => 'article',
            ];

            return view('deeplinks.preview', [
                'metaData' => $metaData,
                'content' => [
                    'type' => 'post',
                    'id' => $id,
                    'data' => $post,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling post deeplink', [
                'post_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleNotFound($request, 'post', $id);
        }
    }

    /**
     * Handle patient deeplink with preview
     */
    public function patient(Request $request, $id)
    {
        try {
            $patient = Patients::with([
                'doctor:id,name,lname,image,isSyndicateCardRequired',
                'answers' => function ($query) {
                    $query->whereIn('question_id', [1, 2, 11]); // name, hospital, governorate
                },
            ])->find($id);

            if (! $patient) {
                return $this->handleNotFound($request, 'patient', $id);
            }

            $isMobile = $this->isMobileDevice($request);

            if ($isMobile) {
                return redirect()->away("egyakin://patient/$id");
            }

            // Index answers for O(1) lookups
            $answersIndexed = $patient->answers->keyBy('question_id');

            // Get patient name from answers
            $patientName = $answersIndexed->get(1)?->answer ?? 'Patient';
            $hospital = $answersIndexed->get(2)?->answer ?? '';

            $metaData = [
                'title' => "$patientName - EGYAKIN Patient",
                'description' => 'Medical case by '.$this->formatDoctorName($patient->doctor).
                               ($hospital ? " at $hospital" : '').' on EGYAKIN medical platform',
                'image' => config('app.url').'/storage/profile_images/profile_image.jpg',
                'url' => url("/patient/$id"),
                'type' => 'profile',
            ];

            return view('deeplinks.preview', [
                'metaData' => $metaData,
                'content' => [
                    'type' => 'patient',
                    'id' => $id,
                    'data' => $patient,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling patient deeplink', [
                'patient_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleNotFound($request, 'patient', $id);
        }
    }

    /**
     * Handle group deeplink with preview
     */
    public function group(Request $request, $id)
    {
        try {
            $group = Group::with(['owner:id,name,lname,image,isSyndicateCardRequired'])
                ->find($id);

            if (! $group) {
                return $this->handleNotFound($request, 'group', $id);
            }

            $isMobile = $this->isMobileDevice($request);

            if ($isMobile) {
                return redirect()->away("egyakin://group/$id");
            }

            $metaData = [
                'title' => $group->name.' - EGYAKIN Group',
                'description' => ($group->description ?? 'Medical group on EGYAKIN').
                               ' | Created by '.$this->formatDoctorName($group->owner),
                'image' => $group->image ?? asset('images/egyakin-logo.png'),
                'url' => url("/group/$id"),
                'type' => 'website',
            ];

            return view('deeplinks.preview', [
                'metaData' => $metaData,
                'content' => [
                    'type' => 'group',
                    'id' => $id,
                    'data' => $group,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling group deeplink', [
                'group_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleNotFound($request, 'group', $id);
        }
    }

    /**
     * Handle consultation deeplink with preview
     */
    public function consultation(Request $request, $id)
    {
        try {
            $consultation = \App\Modules\Consultations\Models\Consultation::with([
                'doctor:id,name,lname,image,isSyndicateCardRequired',
                'patient.answers' => function ($query) {
                    $query->where('question_id', 1); // patient name
                },
            ])->find($id);

            if (! $consultation) {
                return $this->handleNotFound($request, 'consultation', $id);
            }

            $isMobile = $this->isMobileDevice($request);

            if ($isMobile) {
                return redirect()->away("egyakin://consultation/$id");
            }

            $patientName = $consultation->patient->answers->first()?->answer ?? 'Patient';

            $metaData = [
                'title' => "Medical Consultation - $patientName - EGYAKIN",
                'description' => 'Medical consultation by '.$this->formatDoctorName($consultation->doctor).
                               " for $patientName on EGYAKIN medical platform",
                'image' => config('app.url').'/storage/profile_images/profile_image.jpg',
                'url' => url("/consultation/$id"),
                'type' => 'article',
            ];

            return view('deeplinks.preview', [
                'metaData' => $metaData,
                'content' => [
                    'type' => 'consultation',
                    'id' => $id,
                    'data' => $consultation,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling consultation deeplink', [
                'consultation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleNotFound($request, 'consultation', $id);
        }
    }

    /**
     * Check if request is from mobile device
     */
    private function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent');

        return preg_match('/(android|iphone|ipad|mobile)/i', $userAgent);
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

    /**
     * Handle not found cases
     */
    private function handleNotFound(Request $request, string $type, $id)
    {
        $isMobile = $this->isMobileDevice($request);

        if ($isMobile) {
            return redirect()->away("egyakin://$type/$id");
        }

        $metaData = [
            'title' => 'EGYAKIN - Medical Platform',
            'description' => 'Join EGYAKIN, the leading medical platform for healthcare professionals in Egypt.',
            'image' => asset('images/egyakin-logo.png'),
            'url' => url('/'),
            'type' => 'website',
        ];

        return view('deeplinks.preview', [
            'metaData' => $metaData,
            'content' => [
                'type' => 'not_found',
                'id' => $id,
                'data' => null,
            ],
        ]);
    }
}
