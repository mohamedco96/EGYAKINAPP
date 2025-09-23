<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Patient Header Card --}}
        <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl p-6 border border-primary-200 dark:border-primary-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary-500 rounded-full">
                        <x-heroicon-o-user class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-primary-900 dark:text-primary-100">
                            Patient #{{ $patient->id }}
                        </h1>
                        <p class="text-primary-700 dark:text-primary-300 mt-1">
                            Assigned to: {{ $patient->doctor?->name ?? 'No Doctor Assigned' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium {{ $patient->hidden ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                        @if($patient->hidden)
                            <x-heroicon-o-eye-slash class="h-4 w-4" />
                            Hidden
                        @else
                            <x-heroicon-o-eye class="h-4 w-4" />
                            Active
                        @endif
                    </span>
                    <p class="text-sm text-primary-600 dark:text-primary-400 mt-1">
                        Registered: {{ $patient->created_at->format('M j, Y') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-blue-500 rounded-lg">
                        <x-heroicon-o-clipboard-document-list class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ $patient->answers->count() }}</p>
                        <p class="text-sm text-blue-700 dark:text-blue-300">Total Answers</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-green-500 rounded-lg">
                        <x-heroicon-o-squares-2x2 class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-green-900 dark:text-green-100">{{ $sectionsCount }}</p>
                        <p class="text-sm text-green-700 dark:text-green-300">Sections Completed</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-purple-500 rounded-lg">
                        <x-heroicon-o-chart-bar class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">{{ $completionRate }}%</p>
                        <p class="text-sm text-purple-700 dark:text-purple-300">Completion Rate</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-lg p-6 border border-amber-200 dark:border-amber-700">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-amber-500 rounded-lg">
                        <x-heroicon-o-question-mark-circle class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-amber-900 dark:text-amber-100">{{ $totalQuestions }}</p>
                        <p class="text-sm text-amber-700 dark:text-amber-300">Total Questions</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Patient Information --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <x-heroicon-o-information-circle class="h-5 w-5 text-primary-500" />
                Patient Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient ID</label>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">#{{ $patient->id }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Doctor</label>
                    <p class="text-lg text-gray-900 dark:text-gray-100">{{ $patient->doctor?->name ?? 'Not Assigned' }}</p>
                    @if($patient->doctor?->email)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $patient->doctor->email }}</p>
                    @endif
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration Date</label>
                    <p class="text-lg text-gray-900 dark:text-gray-100">{{ $patient->created_at->format('F j, Y') }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $patient->created_at->format('g:i A') }}</p>
                </div>
            </div>
        </div>

        {{-- Answers by Section --}}
        @if($answersBySection->count() > 0)
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5 text-primary-500" />
                    Patient Answers by Section
                </h2>

                @foreach($answersBySection as $sectionName => $answers)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <x-heroicon-o-folder class="h-5 w-5 text-primary-500" />
                                {{ $sectionName }}
                                <span class="ml-auto bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 px-2 py-1 rounded-full text-xs font-medium">
                                    {{ $answers->count() }} answers
                                </span>
                            </h3>
                        </div>

                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($answers as $answer)
                                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-primary-600 dark:text-primary-400">Q</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                                {{ $answer->question->question ?? 'Question not found' }}
                                            </p>
                                            <div class="bg-gray-100 dark:bg-gray-600 rounded-lg p-3">
                                                <div class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words">
                                                    @php
                                                        $displayAnswer = 'No answer provided';
                                                        if ($answer->answer) {
                                                            if (is_array($answer->answer)) {
                                                                $filteredAnswer = array_filter($answer->answer, function($value) {
                                                                    return !is_null($value) && $value !== '';
                                                                });
                                                                $displayAnswer = !empty($filteredAnswer) ? implode(', ', $filteredAnswer) : 'No answer provided';
                                                            } elseif (is_string($answer->answer) || is_numeric($answer->answer)) {
                                                                $displayAnswer = (string) $answer->answer;
                                                            } else {
                                                                $displayAnswer = json_encode($answer->answer);
                                                            }
                                                        }
                                                    @endphp
                                                    {{ $displayAnswer }}
                                                </div>
                                            </div>
                                            @if($answer->created_at)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                    Answered on {{ $answer->created_at->format('M j, Y \a\t g:i A') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12">
                <div class="text-center">
                    <x-heroicon-o-clipboard-document-list class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Answers Available</h3>
                    <p class="text-gray-500 dark:text-gray-400">This patient hasn't provided any answers yet.</p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
