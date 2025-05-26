<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateImage
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasFile('image')) {
            return response()->json([
                'message' => 'No image uploaded',
            ], 400);
        }

        $file = $request->file('image');

        // Check file size (max 5MB for images)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json([
                'message' => 'Image size exceeds limit of 5MB',
            ], 400);
        }

        // Check file type
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
        ];

        if (! in_array($file->getMimeType(), $allowedMimes)) {
            Log::warning('Invalid image type attempted', [
                'mime' => $file->getMimeType(),
                'ip' => $request->ip(),
                'user_id' => $request->user()->id ?? 'guest',
            ]);

            return response()->json([
                'message' => 'Invalid image type. Only JPEG, PNG and GIF are allowed',
            ], 400);
        }

        // Validate image integrity
        $imageInfo = getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            return response()->json([
                'message' => 'Invalid image file',
            ], 400);
        }

        // Check image dimensions
        [$width, $height] = $imageInfo;
        if ($width > 4096 || $height > 4096) {
            return response()->json([
                'message' => 'Image dimensions exceed maximum allowed size (4096x4096)',
            ], 400);
        }

        // Check for malicious content
        $content = file_get_contents($file->getRealPath());
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            Log::alert('Malicious PHP code detected in image upload', [
                'ip' => $request->ip(),
                'user_id' => $request->user()->id ?? 'guest',
            ]);

            return response()->json([
                'message' => 'Invalid image content',
            ], 400);
        }

        // Check for EXIF data and strip it if present
        if (function_exists('exif_read_data')) {
            try {
                $exif = exif_read_data($file->getRealPath());
                if ($exif !== false) {
                    // Log EXIF data removal
                    Log::info('EXIF data removed from image', [
                        'ip' => $request->ip(),
                        'user_id' => $request->user()->id ?? 'guest',
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Error reading EXIF data', [
                    'error' => $e->getMessage(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()->id ?? 'guest',
                ]);
            }
        }

        return $next($request);
    }
}
