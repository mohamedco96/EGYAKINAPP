<div class="space-y-6">
    {{-- Patient Basic Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <x-heroicon-o-user class="h-5 w-5 text-primary-500" />
            Patient Information
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient ID</label>
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">#{{ $record->id }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Assigned Doctor</label>
                <p class="text-lg text-gray-900 dark:text-gray-100">{{ $record->doctor?->name ?? 'Not Assigned' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $record->hidden ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                    @if($record->hidden)
                        <x-heroicon-o-eye-slash class="h-3 w-3" />
                        Hidden
                    @else
                        <x-heroicon-o-eye class="h-3 w-3" />
                        Active
                    @endif
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Registered</label>
                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->created_at->format('F j, Y \a\t g:i A') }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</label>
                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->updated_at->format('F j, Y \a\t g:i A') }}</p>
            </div>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-500 rounded-lg">
                    <x-heroicon-o-clipboard-document-list class="h-5 w-5 text-white" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $record->answers->count() }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">Total Answers</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-500 rounded-lg">
                    <x-heroicon-o-squares-2x2 class="h-5 w-5 text-white" />
                </div>
                <div>
                    @php
                        $sectionsCount = $record->answers->pluck('question.section_id')->unique()->count();
                    @endphp
                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $sectionsCount }}</p>
                    <p class="text-sm text-green-700 dark:text-green-300">Sections Completed</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-500 rounded-lg">
                    <x-heroicon-o-chart-bar class="h-5 w-5 text-white" />
                </div>
                <div>
                    @php
                        $totalQuestions = \App\Modules\Questions\Models\Questions::count();
                        $completionRate = $totalQuestions > 0 ? round(($record->answers->count() / $totalQuestions) * 100, 1) : 0;
                    @endphp
                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ $completionRate }}%</p>
                    <p class="text-sm text-purple-700 dark:text-purple-300">Completion Rate</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Answers --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <x-heroicon-o-chat-bubble-left-right class="h-5 w-5 text-primary-500" />
            Recent Answers
        </h3>
        
        @if($record->answers->count() > 0)
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @foreach($record->answers->take(10) as $answer)
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                {{ $answer->question->question ?? 'Question not found' }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 break-words">
                                {{ $answer->answer }}
                            </p>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $answer->created_at?->format('M j, g:i A') }}
                        </span>
                    </div>
                @endforeach
                
                @if($record->answers->count() > 10)
                    <div class="text-center pt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Showing 10 of {{ $record->answers->count() }} answers
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-8">
                <x-heroicon-o-chat-bubble-left-right class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No answers available for this patient.</p>
            </div>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="/admin/patients/{{ $record->id }}/edit" 
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <x-heroicon-o-pencil class="h-4 w-4" />
            Edit Patient
        </a>
        
        <button onclick="window.open('/admin/patients/export/{{ $record->id }}', '_blank')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <x-heroicon-o-document-arrow-down class="h-4 w-4" />
            Export Data
        </button>
    </div>
</div>
