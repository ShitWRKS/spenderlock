@props([
    'steps' => [],
    'currentStep' => null,
    'showLabels' => true,
])

{{--
Componente Timeline Orizzontale - Linea Temporale Riepilogativa
Mostra step in linea orizzontale con connettori
--}}

<div class="w-full py-2">
    <!-- Timeline Orizzontale -->
    <div class="relative overflow-x-auto px-4">
        <!-- Steps Container -->
        <div class="relative flex items-start justify-between gap-4" style="min-width: {{ count($steps) * 150 }}px">
            <!-- Linea di base -->
            <div class="absolute top-8 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700"></div>
            @foreach ($steps as $index => $step)
                @php
                    $isCurrent = isset($currentStep) && $step['id'] === $currentStep;
                    $isCompleted = $step['completed'] ?? false;
                    $status = $step['status'] ?? 'pending';
                    $icon = $step['icon'] ?? 'heroicon-o-circle';
                    
                    // Determine styles
                    if ($isCompleted) {
                        $dotClass = 'bg-success-500 border-success-600';
                        $lineClass = 'bg-success-500';
                        $textClass = 'text-success-700 dark:text-success-400';
                    } elseif ($isCurrent) {
                        $dotClass = 'bg-primary-500 border-primary-600 ring-4 ring-primary-200 dark:ring-primary-900 animate-pulse';
                        $lineClass = 'bg-gradient-to-r from-success-500 to-gray-300';
                        $textClass = 'text-primary-700 dark:text-primary-400 font-bold';
                    } elseif ($status === 'warning') {
                        $dotClass = 'bg-warning-500 border-warning-600';
                        $lineClass = 'bg-gray-300 dark:bg-gray-600';
                        $textClass = 'text-warning-700 dark:text-warning-400';
                    } elseif ($status === 'danger') {
                        $dotClass = 'bg-danger-500 border-danger-600';
                        $lineClass = 'bg-gray-300 dark:bg-gray-600';
                        $textClass = 'text-danger-700 dark:text-danger-400';
                    } else {
                        $dotClass = 'bg-gray-300 border-gray-400 dark:bg-gray-600 dark:border-gray-500';
                        $lineClass = 'bg-gray-300 dark:bg-gray-600';
                        $textClass = 'text-gray-500 dark:text-gray-400';
                    }
                @endphp

                <div class="flex-1 flex flex-col items-center relative" x-data="{ showTooltip: false }">
                    <!-- Step Dot -->
                    <div class="relative z-10 flex items-center justify-center w-16 h-16 rounded-full border-4 {{ $dotClass }} bg-white dark:bg-gray-900 shadow-lg transition-all hover:scale-110"
                         @mouseenter="showTooltip = true"
                         @mouseleave="showTooltip = false">
                        <x-filament::icon 
                            :icon="$icon" 
                            class="w-8 h-8 {{ $isCompleted || $isCurrent ? 'text-white' : 'text-gray-400' }}"
                            style="{{ $isCompleted || $isCurrent ? 'filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));' : '' }}"
                        />
                    </div>

                    <!-- Connector Line (except last) -->
                    @if (!$loop->last)
                        <div class="absolute top-8 left-1/2 w-full h-1 {{ $isCompleted ? $lineClass : 'bg-gray-300 dark:bg-gray-600' }} transition-all duration-500"
                             style="transform: translateY(-50%);"></div>
                    @endif

                    <!-- Step Info -->
                    <div class="mt-4 text-center max-w-[140px]">
                        <h3 class="text-sm font-semibold mb-1 {{ $textClass }}">
                            {{ $step['title'] }}
                        </h3>
                        
                        @if (isset($step['date']))
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($step['date'])->format('d/m/y') }}
                            </p>
                        @endif

                        <!-- Status Badge -->
                        <div class="mt-2">
                            @if ($isCompleted)
                                <x-filament::badge color="success" size="xs">
                                    Completato
                                </x-filament::badge>
                            @elseif ($isCurrent)
                                <x-filament::badge color="primary" size="xs">
                                    In Corso
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="gray" size="xs">
                                    In Attesa
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>

                    <!-- Tooltip -->
                    @if (isset($step['tooltip']))
                        <div x-show="showTooltip"
                             x-transition
                             class="absolute top-full mt-2 z-50 px-3 py-2 text-xs text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-lg whitespace-nowrap"
                             style="display: none;">
                            {{ $step['tooltip'] }}
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-0 border-4 border-transparent border-b-gray-900 dark:border-b-gray-700"></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
