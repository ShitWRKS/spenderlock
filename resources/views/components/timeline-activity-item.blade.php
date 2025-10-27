@props([
    'activity',
    'index' => 0,
    'isLast' => false,
    'isFuture' => false,
    'showTimestamp' => true,
    'showAttachments' => true,
])

@php
    $status = $activity['status'] ?? 'info';
    $icon = $activity['icon'] ?? 'heroicon-o-chat-bubble-left-right';
    $timestamp = isset($activity['timestamp']) ? \Carbon\Carbon::parse($activity['timestamp']) : now();
    
    // Determine colors based on status
    $colors = match($status) {
        'success', 'completed' => [
            'icon' => 'bg-success-500 border-success-600',
            'line' => 'bg-success-300 dark:bg-success-700',
            'card' => 'border-success-200 dark:border-success-800/50',
            'badge' => 'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300',
        ],
        'warning', 'pending' => [
            'icon' => 'bg-warning-500 border-warning-600',
            'line' => 'bg-warning-300 dark:bg-warning-700',
            'card' => 'border-warning-200 dark:border-warning-800/50',
            'badge' => 'bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300',
        ],
        'danger', 'failed', 'error' => [
            'icon' => 'bg-danger-500 border-danger-600',
            'line' => 'bg-danger-300 dark:bg-danger-700',
            'card' => 'border-danger-200 dark:border-danger-800/50',
            'badge' => 'bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-300',
        ],
        'info' => [
            'icon' => 'bg-info-500 border-info-600',
            'line' => 'bg-info-300 dark:bg-info-700',
            'card' => 'border-info-200 dark:border-info-800/50',
            'badge' => 'bg-info-100 text-info-800 dark:bg-info-900/30 dark:text-info-300',
        ],
        'contract-start' => [
            'icon' => 'bg-success-500 border-success-600',
            'line' => 'bg-success-300 dark:bg-success-700',
            'card' => 'border-success-200 dark:border-success-800/50',
            'badge' => 'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300',
        ],
        default => [
            'icon' => 'bg-gray-400 border-gray-500 dark:bg-gray-600 dark:border-gray-500',
            'line' => 'bg-gray-300 dark:bg-gray-700',
            'card' => 'border-gray-200 dark:border-gray-700',
            'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
        ],
    };
@endphp

<!-- Timeline Activity Item -->
<div class="relative flex gap-4 pb-6" 
     x-data="{ expanded: false }"
     x-init="setTimeout(() => $el.classList.add('opacity-100', 'translate-x-0'), {{ $index * 50 }})"
     class="opacity-0 translate-x-4 transition-all duration-300">
    
    <!-- Timeline Line & Icon -->
    <div class="relative flex flex-col items-center">
        <!-- Icon Circle -->
        <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full border-2 {{ $colors['icon'] }} shadow-md">
            <x-filament::icon 
                :icon="$icon" 
                class="w-5 h-5 text-white"
            />
        </div>

        <!-- Vertical Connecting Line -->
        @if (!$isLast)
            <div class="w-0.5 flex-1 min-h-[60px] mt-2 {{ $colors['line'] }} transition-colors"></div>
        @endif
    </div>

    <!-- Activity Card -->
    <div class="flex-1 -mt-1">
        @if(isset($activity['entity_url']) && $activity['entity_url'])
            <a href="{{ $activity['entity_url'] }}" wire:navigate class="block">
                <div class="bg-white dark:bg-gray-800 rounded-lg border {{ $colors['card'] }} shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200 cursor-pointer">
                    <!-- Card Header -->
                    <div class="px-4 py-3">
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg border {{ $colors['card'] }} shadow-sm hover:shadow-md transition-all duration-200">
                <!-- Card Header -->
                <div class="px-4 py-3">
        @endif
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex-1">
                        <!-- Title -->
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {!! $activity['title'] ?? 'Attività' !!}
                        </h3>
                    </div>

                    <!-- Timestamp -->
                    @if ($showTimestamp)
                        <div class="flex flex-col items-end text-xs">
                            <span class="text-gray-600 dark:text-gray-400">{{ $timestamp->format('d/m/y') }}</span>
                            <span class="text-gray-500 dark:text-gray-500">{{ $timestamp->format('H:i') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Status Badge & Relative Time -->
                <div class="flex items-center gap-2 flex-wrap">
                    @if (isset($activity['status_label']))
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $colors['badge'] }}">
                            {{ $activity['status_label'] }}
                        </span>
                    @endif
                    
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $timestamp->diffForHumans() }}
                    </span>
                    
                    @if ($isFuture)
                        <x-filament::badge color="warning" size="sm">
                            Futuro
                        </x-filament::badge>
                    @endif
                </div>
            </div>

            <!-- Description -->
            @if (isset($activity['description']))
                <div class="px-4 pb-3">
                    <div class="text-sm text-gray-600 dark:text-gray-400 prose prose-sm dark:prose-invert max-w-none">
                        {!! $activity['description'] !!}
                    </div>
                </div>
            @endif

            <!-- Additional Info -->
            @if (isset($activity['info']) && !empty($activity['info']))
                <div class="px-4 pb-3">
                    <div class="grid grid-cols-2 gap-2 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        @foreach ($activity['info'] as $key => $value)
                            <div class="text-xs">
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ $key }}:</span>
                                <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Attachments -->
            @if ($showAttachments && isset($activity['attachments']) && !empty($activity['attachments']))
                <div class="px-4 pb-3">
                    <div class="flex items-center gap-2 mb-2">
                        <x-filament::icon icon="heroicon-o-paper-clip" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Allegati:</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($activity['attachments'] as $attachment)
                            <a href="{{ $attachment['url'] ?? '#' }}" 
                               target="_blank"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-xs text-gray-700 dark:text-gray-300 transition-colors">
                                <x-filament::icon 
                                    icon="heroicon-o-paper-clip" 
                                    class="w-3.5 h-3.5"
                                />
                                <span>{{ $attachment['name'] ?? 'File' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Expandable Details -->
            @if (isset($activity['details']) && !empty($activity['details']))
                <div class="border-t border-gray-100 dark:border-gray-700">
                    <button @click="expanded = !expanded" 
                            type="button"
                            class="w-full px-4 py-2 flex items-center justify-between text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                        <span x-text="expanded ? 'Nascondi dettagli' : 'Mostra dettagli'"></span>
                        <x-filament::icon 
                            x-bind:icon="expanded ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'" 
                            class="w-4 h-4"
                        />
                    </button>
                    
                    <div x-show="expanded" 
                         x-collapse
                         class="px-4 pb-3">
                        <div class="text-xs text-gray-600 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700">
                            {!! $activity['details'] !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Footer -->
            @if (isset($activity['footer']))
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 rounded-b-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {!! $activity['footer'] !!}
                    </div>
                </div>
            @endif
                </div> {{-- Close px-4 py-3 --}}
            </div> {{-- Close card div --}}
        @if(isset($activity['entity_url']) && $activity['entity_url'])
            </a> {{-- Close link wrapper --}}
        @endif
    </div> {{-- Close flex-1 -mt-1 --}}
</div>
