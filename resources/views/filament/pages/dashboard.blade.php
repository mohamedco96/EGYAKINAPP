<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Core Stats Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @livewire(\App\Filament\Widgets\CoreMedicalOverview::class)
        </div>

        {{-- Charts and Tables Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Consultation Trends Chart --}}
            <div class="lg:col-span-2">
                @livewire(\App\Filament\Widgets\ConsultationTrendsChart::class)
            </div>
            
            {{-- Quick Actions --}}
            <div class="lg:col-span-1">
                @livewire(\App\Filament\Widgets\QuickActionsWidget::class)
            </div>
        </div>

        {{-- Recent Activity Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @livewire(\App\Filament\Widgets\RecentActivityTable::class)
            @livewire(\App\Filament\Widgets\RecentConsultationsTable::class)
        </div>

        {{-- Footer Info --}}
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Dashboard automatically refreshes every 30 seconds to show the latest data.
                <span class="inline-flex items-center gap-1 ml-2">
                    <x-heroicon-o-arrow-path class="h-3 w-3 animate-spin" />
                    Live updates enabled
                </span>
            </p>
        </div>
    </div>
</x-filament-panels::page>
