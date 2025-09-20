<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl p-6 border border-primary-200 dark:border-primary-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-primary-900 dark:text-primary-100">
                        {{ $this->getHeading() }}
                    </h1>
                    <p class="text-primary-700 dark:text-primary-300 mt-1">
                        {{ $this->getSubheading() }}
                    </p>
                </div>
                <div class="hidden sm:block">
                    <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-calendar-days class="h-5 w-5" />
                        <span class="text-sm font-medium">{{ now()->format('F j, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

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

        {{-- Recent Activity Table --}}
        <div class="grid grid-cols-1 gap-6">
            @livewire(\App\Filament\Widgets\RecentActivityTable::class)
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
