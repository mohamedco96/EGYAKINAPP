<?php

namespace App\Http\Controllers;

use App\Jobs\ExportPatientsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    public function startPatientsExport(Request $request): JsonResponse
    {
        try {
            $timestamp = time().'_'.uniqid();
            $filename = "patients_export_{$timestamp}.xlsx";
            $userId = auth()->id();

            // Initialize progress
            Cache::put('export_progress_'.$filename, [
                'percentage' => 0,
                'message' => 'Starting export...',
                'updated_at' => now(),
            ], 3600);

            // Dispatch the job
            ExportPatientsJob::dispatch($filename, 100, $userId);

            Log::info('Patient export job dispatched', [
                'filename' => $filename,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Export started successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start patient export', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start export: '.$e->getMessage(),
            ], 500);
        }
    }

    public function checkExportProgress(string $filename)
    {
        // If it's an API request, return JSON
        if (request()->expectsJson() || request()->is('api/*')) {
            return $this->getExportProgressJson($filename);
        }

        // Otherwise, return the progress page
        return view('export-progress', compact('filename'));
    }

    public function getExportProgressJson(string $filename): JsonResponse
    {
        try {
            $progress = Cache::get('export_progress_'.$filename);
            $result = Cache::get('export_result_'.$filename);

            if ($result) {
                return response()->json([
                    'status' => $result['status'],
                    'filename' => $filename,
                    'download_url' => $result['download_url'] ?? null,
                    'error' => $result['error'] ?? null,
                    'file_size' => $result['file_size'] ?? null,
                    'created_at' => $result['created_at'] ?? null,
                ]);
            }

            if ($progress) {
                return response()->json([
                    'status' => 'processing',
                    'percentage' => $progress['percentage'],
                    'message' => $progress['message'],
                    'updated_at' => $progress['updated_at'],
                ]);
            }

            return response()->json([
                'status' => 'not_found',
                'message' => 'Export not found',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check progress: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadExport(string $filename)
    {
        try {
            $filePath = storage_path('app/public/exports/'.$filename);

            if (! file_exists($filePath)) {
                abort(404, 'Export file not found');
            }

            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to download export', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Failed to download export file');
        }
    }
}
