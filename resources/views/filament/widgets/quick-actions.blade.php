<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bolt class="h-5 w-5 text-primary-500" />
                Quick Actions
            </div>
        </x-slot>

        <div class="grid gap-3">
            @foreach ($actions as $action)
                <a 
                    href="{{ $action['url'] }}" 
                    class="group relative flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 text-sm font-medium text-gray-900 shadow-sm transition-all duration-200 hover:border-{{ $action['color'] }}-300 hover:bg-{{ $action['color'] }}-50 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-{{ $action['color'] }}-600 dark:hover:bg-{{ $action['color'] }}-900/20"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-{{ $action['color'] }}-100 text-{{ $action['color'] }}-600 transition-colors group-hover:bg-{{ $action['color'] }}-200 dark:bg-{{ $action['color'] }}-900/30 dark:text-{{ $action['color'] }}-400">
                        @svg($action['icon'], 'h-4 w-4')
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $action['label'] }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $action['description'] }}
                        </div>
                    </div>
                    
                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400 transition-transform group-hover:translate-x-1 group-hover:text-{{ $action['color'] }}-500" />
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
