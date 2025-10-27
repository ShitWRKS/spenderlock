@php
    $steps = $getState();
    $currentStep = collect($steps)->firstWhere('status', 'warning')['id'] ?? 
                   collect($steps)->firstWhere('status', 'danger')['id'] ?? 
                   collect($steps)->last()['id'];
@endphp

<div class="overflow-x-auto">
    <div class="flex items-center justify-between gap-4 min-w-max py-4">
        @foreach($steps as $index => $step)
            @php
                $isCurrent = $step['id'] === $currentStep;
                $isCompleted = $step['completed'] ?? false;
                $status = $step['status'] ?? 'pending';
                
                $iconColor = match(true) {
                    $isCompleted => 'text-success-600 dark:text-success-400',
                    $isCurrent => 'text-primary-600 dark:text-primary-400',
                    $status === 'warning' => 'text-warning-600 dark:text-warning-400',
                    $status === 'danger' => 'text-danger-600 dark:text-danger-400',
                    default => 'text-gray-400 dark:text-gray-600',
                };
                
                $bgColor = match(true) {
                    $isCompleted => 'bg-success-500 border-success-600',
                    $isCurrent => 'bg-primary-500 border-primary-600 ring-4 ring-primary-100 dark:ring-primary-900',
                    $status === 'warning' => 'bg-warning-500 border-warning-600',
                    $status === 'danger' => 'bg-danger-500 border-danger-600',
                    default => 'bg-gray-300 border-gray-400 dark:bg-gray-700 dark:border-gray-600',
                };
                
                $lineColor = $isCompleted || $isCurrent ? 'bg-success-500' : 'bg-gray-300 dark:bg-gray-600';
            @endphp
            
            <div class="flex flex-col items-center gap-2 flex-1 min-w-[120px] relative group">
                {{-- Icon Circle --}}
                <div class="relative z-10 flex items-center justify-center w-14 h-14 rounded-full border-4 {{ $bgColor }} shadow-lg transition-transform hover:scale-110">
                    <x-filament::icon 
                        :icon="$step['icon']" 
                        class="w-7 h-7 text-white"
                    />
                </div>
                
                {{-- Connector Line --}}
                @if(!$loop->last)
                    <div class="absolute top-7 left-1/2 w-full h-1 {{ $lineColor }} -z-0"></div>
                @endif
                
                {{-- Info --}}
                <div class="text-center">
                    <h3 class="text-sm font-semibold {{ $iconColor }}">
                        {{ $step['title'] }}
                    </h3>
                    
                    @if(isset($step['date']))
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ \Carbon\Carbon::parse($step['date'])->format('d/m/y') }}
                        </p>
                    @endif
                    
                    <div class="mt-1.5">
                        @if($isCompleted)
                            <x-filament::badge color="success" size="xs">
                                Completato
                            </x-filament::badge>
                        @elseif($isCurrent)
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
                
                {{-- Tooltip --}}
                @if(isset($step['tooltip']))
                    <div class="absolute bottom-full mb-2 hidden group-hover:block z-50">
                        <div class="bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs rounded-lg px-3 py-2 shadow-xl whitespace-nowrap">
                            {{ $step['tooltip'] }}
                            <div class="absolute top-full left-1/2 -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-100"></div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
