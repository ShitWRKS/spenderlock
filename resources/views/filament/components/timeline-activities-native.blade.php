@php
    $activities = collect($getState());
    $now = \Carbon\Carbon::now();
    
    $futureActivities = $activities->filter(fn($a) => \Carbon\Carbon::parse($a['timestamp'])->isFuture());
    $pastActivities = $activities->filter(fn($a) => \Carbon\Carbon::parse($a['timestamp'])->isPast());
    $recentPast = $pastActivities->take(5);
    $olderPast = $pastActivities->skip(5);
@endphp

<div x-data="{ showAllFuture: false, showAllPast: false }" class="space-y-4">
    {{-- Future Events --}}
    @if($futureActivities->isNotEmpty())
        <div class="border-t border-warning-200 dark:border-warning-800 pt-4">
            <button 
                @click="showAllFuture = !showAllFuture"
                class="flex items-center gap-2 w-full px-4 py-2 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg hover:bg-warning-100 dark:hover:bg-warning-900/30 transition-colors"
            >
                <x-filament::icon 
                    x-bind:icon="showAllFuture ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'"
                    class="w-4 h-4"
                />
                <span class="font-semibold text-warning-700 dark:text-warning-300">Eventi Futuri</span>
                <x-filament::badge color="warning" size="sm">
                    {{ $futureActivities->count() }}
                </x-filament::badge>
            </button>
            
            <div x-show="showAllFuture" x-collapse class="mt-4 space-y-3">
                @foreach($futureActivities as $activity)
                    @include('filament.components.timeline-activity-card', ['activity' => $activity])
                @endforeach
            </div>
        </div>
    @endif
    
    {{-- TODAY Marker --}}
    @if($futureActivities->isNotEmpty() && $pastActivities->isNotEmpty())
        <div class="flex items-center gap-3 py-4">
            <div class="flex-1 h-px bg-gradient-to-r from-transparent via-primary-300 dark:via-primary-700 to-transparent"></div>
            <x-filament::badge color="primary" icon="heroicon-o-calendar">
                OGGI - {{ $now->format('d/m/Y') }}
            </x-filament::badge>
            <div class="flex-1 h-px bg-gradient-to-l from-transparent via-primary-300 dark:via-primary-700 to-transparent"></div>
        </div>
    @endif
    
    {{-- Recent Past --}}
    @if($recentPast->isNotEmpty())
        <div class="space-y-3">
            @foreach($recentPast as $activity)
                @include('filament.components.timeline-activity-card', ['activity' => $activity])
            @endforeach
        </div>
    @endif
    
    {{-- Older Past (Collapsible) --}}
    @if($olderPast->isNotEmpty())
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <button 
                @click="showAllPast = !showAllPast"
                class="flex items-center gap-2 w-full px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
                <x-filament::icon 
                    x-bind:icon="showAllPast ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'"
                    class="w-4 h-4"
                />
                <span class="font-semibold text-gray-700 dark:text-gray-300">Eventi Precedenti</span>
                <x-filament::badge color="gray" size="sm">
                    {{ $olderPast->count() }}
                </x-filament::badge>
            </button>
            
            <div x-show="showAllPast" x-collapse class="mt-4 space-y-3">
                @foreach($olderPast as $activity)
                    @include('filament.components.timeline-activity-card', ['activity' => $activity])
                @endforeach
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    if (!Alpine.directive('collapse')) {
        Alpine.directive('collapse', (el, { expression }, { effect, evaluateLater }) => {
            let duration = 300;
            let isOpen = evaluateLater(expression);
            
            effect(() => {
                isOpen(value => {
                    if (value) {
                        el.style.display = 'block';
                        el.style.overflow = 'hidden';
                        let height = el.scrollHeight + 'px';
                        el.style.height = '0px';
                        el.style.opacity = '0';
                        requestAnimationFrame(() => {
                            el.style.transition = `height ${duration}ms ease, opacity ${duration}ms ease`;
                            el.style.height = height;
                            el.style.opacity = '1';
                        });
                        setTimeout(() => {
                            el.style.height = 'auto';
                            el.style.overflow = 'visible';
                        }, duration);
                    } else {
                        el.style.overflow = 'hidden';
                        el.style.height = el.scrollHeight + 'px';
                        requestAnimationFrame(() => {
                            el.style.transition = `height ${duration}ms ease, opacity ${duration}ms ease`;
                            el.style.height = '0px';
                            el.style.opacity = '0';
                        });
                        setTimeout(() => {
                            el.style.display = 'none';
                        }, duration);
                    }
                });
            });
        });
    }
});
</script>
@endpush
