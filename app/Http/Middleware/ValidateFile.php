<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateFile
{
    protected $maxFileSize = 10 * 1024 * 1024; // 10MB

    protected $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasFile('file')) {
            return response()->json([
                'message' => 'No file uploaded',
            ], 400);
        }

        $file = $request->file('file');

        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            Log::warning('File size exceeded', [
                'size' => $file->getSize(),
                'max_size' => $this->maxFileSize,
                'ip' => $request->ip(),
                'user_id' => $request->user()->id ?? 'guest',
            ]);

            return response()->json([
                'message' => 'File size exceeds limit of 10MB',
            ], 400);
        }

        // Check file type
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, $this->allowedMimes)) {
            Log::warning('Invalid file type attempted', [
                'mime' => $mimeType,
                'ip' => $request->ip(),
                'user_id' => $request->user()->id ?? 'guest',
            ]);

            return response()->json([
                'message' => 'Invalid file type',
            ], 400);
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        if (! in_array($extension, $allowedExtensions)) {
            Log::warning('Invalid file extension attempted', [
                'extension' => $extension,
                'ip' => $request->ip(),
                'user_id' => $request->user()->id ?? 'guest',
            ]);

            return response()->json([
                'message' => 'Invalid file extension',
            ], 400);
        }

        // Check for malicious content
        $content = file_get_contents($file->getRealPath());

        // Check for PHP code
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            Log::alert('Malicious PHP code detected in file upload', [
                'ip' => $request->ip(),
                'user_id' => $request->user()->id ?? 'guest',
            ]);

            return response()->json([
                'message' => 'Invalid file content',
            ], 400);
        }

        // Check for null bytes
        if (strpos($content, "\0") !== false) {
            Log::alert('Null byte detected in file upload', [
                'ip' => $request->ip(),
                'user_id' => $request->user()->id ?? 'guest',
            ]);

            return response()->json([
                'message' => 'Invalid file content',
            ], 400);
        }

        // Check for image-specific threats
        if (str_starts_with($mimeType, 'image/')) {
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
        }

        // Check for document-specific threats
        if (str_starts_with($mimeType, 'application/')) {
            // Check for macro content in Office documents
            if (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
                if (strpos($content, 'VBA') !== false || strpos($content, 'macro') !== false) {
                    Log::alert('Macro content detected in document upload', [
                        'ip' => $request->ip(),
                        'user_id' => $request->user()->id ?? 'guest',
                    ]);

                    return response()->json([
                        'message' => 'Documents with macros are not allowed',
                    ], 400);
                }
            }
        }

        // Generate secure filename
        $secureFilename = $this->generateSecureFilename($file);
        $request->merge(['secure_filename' => $secureFilename]);

        return $next($request);
    }

    protected function generateSecureFilename($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return bin2hex(random_bytes(16)).'_'.time().'.'.$extension;
    }
}
