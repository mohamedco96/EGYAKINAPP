<?php

namespace App\Modules\Patients\Resources\PatientsResource\Pages;

use App\Modules\Patients\Resources\PatientsResource;
use App\Modules\Questions\Models\Questions;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientsResource::class;

    protected static string $view = 'filament.patients.view-patient';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('exportPatient')
                ->label('Export Patient')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return PatientsResource::exportSinglePatient($this->record);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // We can add widgets here later if needed
        ];
    }

    public function getTitle(): string
    {
        return "Patient #{$this->record->id} Details";
    }

    protected function getViewData(): array
    {
        try {
            $patient = $this->record->load(['answers.question', 'doctor']);

            // Group answers by section with safety checks
            $answersBySection = $patient->answers
                ->filter(function ($answer) {
                    // Only include answers that have a valid question
                    return $answer->question !== null;
                })
                ->groupBy(function ($answer) {
                    return $answer->question->section_name ?? 'Uncategorized';
                })
                ->map(function ($sectionAnswers) {
                    return $sectionAnswers->sortBy(function ($answer) {
                        return $answer->question->sort ?? 0;
                    });
                });

            // Get completion statistics
            $totalQuestions = Cache::remember('total_questions_count', 300, function () {
                return Questions::count();
            });

            $completionRate = $totalQuestions > 0
                ? round(($patient->answers->count() / $totalQuestions) * 100, 1)
                : 0;

            return [
                'patient' => $patient,
                'answersBySection' => $answersBySection,
                'totalQuestions' => $totalQuestions,
                'completionRate' => $completionRate,
                'sectionsCount' => $answersBySection->count(),
            ];
        } catch (\Exception $e) {
            // Fallback data if there's an error
            return [
                'patient' => $this->record->load('doctor'),
                'answersBySection' => collect(),
                'totalQuestions' => 0,
                'completionRate' => 0,
                'sectionsCount' => 0,
            ];
        }
    }
}
