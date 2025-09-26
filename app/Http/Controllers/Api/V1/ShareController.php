<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ShareUrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShareController extends Controller
{
    protected $shareUrlService;

    public function __construct(ShareUrlService $shareUrlService)
    {
        $this->shareUrlService = $shareUrlService;
    }

    /**
     * Generate share URL for content
     */
    public function generateUrl(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:post,patient,group,consultation',
                'id' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'value' => false,
                    'message' => 'Invalid request data',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $type = $request->input('type');
            $id = $request->input('id');

            $result = match ($type) {
                'post' => $this->shareUrlService->generatePostUrl($id),
                'patient' => $this->shareUrlService->generatePatientUrl($id),
                'group' => $this->shareUrlService->generateGroupUrl($id),
                'consultation' => $this->shareUrlService->generateConsultationUrl($id),
            };

            if (! $result['success']) {
                return response()->json([
                    'value' => false,
                    'message' => $result['error'],
                ], 404);
            }

            return response()->json([
                'value' => true,
                'message' => 'Share URL generated successfully',
                'data' => [
                    'share_url' => $result['url'],
                    'deeplink' => $result['deeplink'],
                    'type' => $result['type'],
                    'id' => $result['id'],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error generating share URL', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to generate share URL',
            ], 500);
        }
    }

    /**
     * Generate multiple share URLs
     */
    public function generateBulkUrls(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1|max:10',
                'items.*.type' => 'required|in:post,patient,group,consultation',
                'items.*.id' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'value' => false,
                    'message' => 'Invalid request data',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $items = $request->input('items');
            $results = $this->shareUrlService->generateBulkUrls($items);

            $successCount = count(array_filter($results, fn ($r) => $r['success']));
            $totalCount = count($results);

            return response()->json([
                'value' => true,
                'message' => "Generated $successCount out of $totalCount share URLs",
                'data' => array_map(function ($result) {
                    if ($result['success']) {
                        return [
                            'share_url' => $result['url'],
                            'deeplink' => $result['deeplink'],
                            'type' => $result['type'],
                            'id' => $result['id'],
                        ];
                    } else {
                        return [
                            'error' => $result['error'],
                        ];
                    }
                }, $results),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error generating bulk share URLs', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to generate share URLs',
            ], 500);
        }
    }

    /**
     * Get preview data for sharing
     */
    public function getPreview(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:post,patient,group,consultation',
                'id' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'value' => false,
                    'message' => 'Invalid request data',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $type = $request->input('type');
            $id = $request->input('id');

            $result = $this->shareUrlService->getPreviewData($type, $id);

            if (! $result['success']) {
                return response()->json([
                    'value' => false,
                    'message' => $result['error'],
                ], 404);
            }

            return response()->json([
                'value' => true,
                'message' => 'Preview data retrieved successfully',
                'data' => [
                    'title' => $result['title'],
                    'description' => $result['description'],
                    'image' => $result['image'],
                    'url' => $result['url'],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting preview data', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to get preview data',
            ], 500);
        }
    }
}
