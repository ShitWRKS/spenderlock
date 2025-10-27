{{-- Header --}}
<div class="flex items-start justify-between gap-3 mb-2">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex-1">
        {!! $activity['title'] !!}
    </h3>
    
    <div class="flex flex-col items-end text-xs text-gray-500 dark:text-gray-400">
        <span>{{ $timestamp->format('d/m/y') }}</span>
        <span>{{ $timestamp->format('H:i') }}</span>
    </div>
</div>

{{-- Badges --}}
<div class="flex items-center gap-2 flex-wrap mb-3">
    {{-- Activity type and entity badges --}}
    @if(isset($activity['badges']) && is_array($activity['badges']))
        @foreach($activity['badges'] as $badge)
            <x-filament::badge 
                :color="$badge['color']" 
                size="sm"
                :icon="$badge['icon'] ?? null"
            >
                {{ $badge['label'] }}
            </x-filament::badge>
        @endforeach
    @endif
    
    {{-- Future badge --}}
    @if($isFuture)
        <x-filament::badge color="warning" size="sm">
            Futuro
        </x-filament::badge>
    @endif
    
    {{-- Relative time --}}
    <span class="text-xs text-gray-500 dark:text-gray-400">
        {{ $timestamp->diffForHumans() }}
    </span>
</div>

{{-- Description --}}
@if(isset($activity['description']) && $activity['description'])
    <div class="text-sm text-gray-600 dark:text-gray-400 prose prose-sm dark:prose-invert max-w-none">
        {!! $activity['description'] !!}
    </div>
@endif
