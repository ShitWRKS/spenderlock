@php
    $timestamp = \Carbon\Carbon::parse($activity['timestamp']);
    $isFuture = $timestamp->isFuture();
    
    // Determina l'icona in base al tipo di attività
    $activityIcon = match($activity['activity_type'] ?? 'comment') {
        'comment' => 'heroicon-o-chat-bubble-left-right',
        'contract-start' => 'heroicon-o-play-circle',
        'contract-end' => $isFuture ? 'heroicon-o-stop-circle' : 'heroicon-o-check-circle',
        default => $activity['icon'] ?? 'heroicon-o-information-circle',
    };
    
    $colorClasses = match($activity['status']) {
        'success' => [
            'border' => 'border-success-200 dark:border-success-800',
            'icon' => 'bg-success-500 dark:bg-success-600 text-white',
            'hover' => 'hover:border-success-300 dark:hover:border-success-700 hover:shadow-md',
        ],
        'danger' => [
            'border' => 'border-danger-200 dark:border-danger-800',
            'icon' => 'bg-danger-500 dark:bg-danger-600 text-white',
            'hover' => 'hover:border-danger-300 dark:hover:border-danger-700 hover:shadow-md',
        ],
        'warning' => [
            'border' => 'border-warning-200 dark:border-warning-800',
            'icon' => 'bg-warning-500 dark:bg-warning-600 text-white',
            'hover' => 'hover:border-warning-300 dark:hover:border-warning-700 hover:shadow-md',
        ],
        'info' => [
            'border' => 'border-info-200 dark:border-info-800',
            'icon' => 'bg-info-500 dark:bg-info-600 text-white',
            'hover' => 'hover:border-info-300 dark:hover:border-info-700 hover:shadow-md',
        ],
        'contract-start' => [
            'border' => 'border-success-200 dark:border-success-800',
            'icon' => 'bg-success-500 dark:bg-success-600 text-white',
            'hover' => 'hover:border-success-300 dark:hover:border-success-700 hover:shadow-md',
        ],
        default => [
            'border' => 'border-gray-200 dark:border-gray-700',
            'icon' => 'bg-gray-500 dark:bg-gray-600 text-white',
            'hover' => 'hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-md',
        ],
    };
    
    $hasLink = isset($activity['entity_url']) && $activity['entity_url'];
@endphp

<div class="flex gap-3">
    {{-- Icon --}}
    <div class="flex-shrink-0">
        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $colorClasses['icon'] }} shadow-md transition-transform hover:scale-110">
            <x-filament::icon 
                :icon="$activityIcon" 
                class="w-5 h-5"
            />
        </div>
    </div>
    
    {{-- Content --}}
    <div class="flex-1 min-w-0">
        @if($hasLink)
            <a href="{{ $activity['entity_url'] }}" wire:navigate class="block">
                <div class="bg-white dark:bg-gray-800 border {{ $colorClasses['border'] }} rounded-lg p-4 {{ $colorClasses['hover'] }} transition-all cursor-pointer">
                    @include('filament.components.timeline-activity-content', ['activity' => $activity, 'timestamp' => $timestamp, 'isFuture' => $isFuture])
                </div>
            </a>
        @else
            <div class="bg-white dark:bg-gray-800 border {{ $colorClasses['border'] }} rounded-lg p-4 transition-shadow hover:shadow-md">
                @include('filament.components.timeline-activity-content', ['activity' => $activity, 'timestamp' => $timestamp, 'isFuture' => $isFuture])
            </div>
        @endif
    </div>
</div>
