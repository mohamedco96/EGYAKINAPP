<?php

namespace App\Jobs;

use App\Modules\Patients\Models\Patients;
use App\Modules\Questions\Models\Questions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;

class ExportPatientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout

    public $tries = 3;

    protected $filename;

    protected $chunkSize;

    protected $userId;

    public function __construct(string $filename, int $chunkSize = 100, ?int $userId = null)
    {
        $this->filename = $filename;
        $this->chunkSize = $chunkSize;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        try {
            Log::info('Starting patient export job', [
                'filename' => $this->filename,
                'chunk_size' => $this->chunkSize,
                'user_id' => $this->userId,
            ]);

            // Get questions with caching
            $questions = Cache::remember('export_questions_'.md5('all'), 3600, function () {
                return Questions::select(['id', 'question'])->orderBy('id')->get();
            });

            // Create optimized export class
            $export = new class($questions, $this->chunkSize) implements FromCollection, WithHeadings, WithMapping
            {
                private $questions;

                private $chunkSize;

                public function __construct($questions, $chunkSize)
                {
                    $this->questions = $questions;
                    $this->chunkSize = $chunkSize;
                }

                public function collection()
                {
                    // Use chunking for memory efficiency
                    $patients = collect();

                    Patients::with(['answers' => function ($query) {
                        $query->select(['id', 'patient_id', 'question_id', 'answer'])
                            ->orderBy('question_id');
                    }])
                        ->select(['id', 'doctor_id', 'created_at', 'updated_at'])
                        ->chunk($this->chunkSize, function ($chunk) use ($patients) {
                            $patients->push(...$chunk);
                        });

                    return $patients;
                }

                public function headings(): array
                {
                    $headings = [
                        'Patient ID',
                        'Doctor ID',
                        'Registration Date',
                        'Last Updated',
                    ];

                    foreach ($this->questions as $question) {
                        $headings[] = $this->sanitizeColumnName($question->question);
                    }

                    return $headings;
                }

                public function map($patient): array
                {
                    $data = [
                        $patient->id,
                        $patient->doctor_id,
                        $patient->created_at?->format('Y-m-d H:i:s'),
                        $patient->updated_at?->format('Y-m-d H:i:s'),
                    ];

                    // Create a lookup array for faster access
                    $answerLookup = $patient->answers->keyBy('question_id');

                    foreach ($this->questions as $question) {
                        $answer = $answerLookup->get($question->id);

                        if ($answer && $answer->answer) {
                            if (is_array($answer->answer)) {
                                $filteredAnswer = array_filter($answer->answer, function ($value) {
                                    return ! is_null($value) && $value !== '';
                                });
                                $data[] = ! empty($filteredAnswer) ? implode(', ', $filteredAnswer) : '';
                            } else {
                                $data[] = (string) $answer->answer;
                            }
                        } else {
                            $data[] = '';
                        }
                    }

                    return $data;
                }

                private function sanitizeColumnName(string $name): string
                {
                    // Limit column name length and sanitize for Excel
                    return substr(preg_replace('/[^\w\s-]/', '', $name), 0, 100);
                }
            };

            // Generate file with progress tracking
            $this->updateProgress(10, 'Preparing export data...');

            Excel::store($export, 'exports/'.$this->filename, 'public');

            $this->updateProgress(90, 'Finalizing export...');

            // Verify file was created
            if (Storage::disk('public')->exists('exports/'.$this->filename)) {
                $fileSize = Storage::disk('public')->size('exports/'.$this->filename);

                Log::info('Patient export completed successfully', [
                    'filename' => $this->filename,
                    'file_size' => $fileSize,
                    'user_id' => $this->userId,
                ]);

                $this->updateProgress(100, 'Export completed successfully!');

                // Cache the export result for 1 hour
                Cache::put('export_result_'.$this->filename, [
                    'status' => 'completed',
                    'filename' => $this->filename,
                    'file_size' => $fileSize,
                    'download_url' => config('app.url').'/storage/exports/'.$this->filename,
                    'created_at' => now(),
                ], 3600);

            } else {
                throw new \Exception('Export file was not created successfully');
            }

        } catch (\Exception $e) {
            Log::error('Patient export job failed', [
                'filename' => $this->filename,
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);

            $this->updateProgress(0, 'Export failed: '.$e->getMessage());

            Cache::put('export_result_'.$this->filename, [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now(),
            ], 3600);

            throw $e;
        }
    }

    private function updateProgress(int $percentage, string $message): void
    {
        Cache::put('export_progress_'.$this->filename, [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now(),
        ], 3600);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Patient export job failed completely', [
            'filename' => $this->filename,
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
        ]);

        Cache::put('export_result_'.$this->filename, [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'created_at' => now(),
        ], 3600);
    }
}
