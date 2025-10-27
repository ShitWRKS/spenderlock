@props([
    'activities' => [],
    'showTimestamp' => true,
    'showAttachments' => true,
    'animate' => true,
])

{{--
Componente Timeline Verticale MIGLIORATA
Con linee di connessione visibili e collassamento eventi futuri/passati
--}}

@php
    $now = now();
    $futureActivities = collect($activities)->filter(fn($a) => 
        isset($a['timestamp']) && \Carbon\Carbon::parse($a['timestamp'])->isFuture()
    );
    $pastActivities = collect($activities)->filter(fn($a) => 
        !isset($a['timestamp']) || \Carbon\Carbon::parse($a['timestamp'])->isPast()
    );
    $recentPast = $pastActivities->take(5);
    $olderPast = $pastActivities->skip(5);
@endphp

<div class="space-y-0" x-data="{ 
    showAllFuture: false, 
    showAllPast: false,
    animateIn: {{ $animate ? 'true' : 'false' }} 
}">
    @if ($futureActivities->isNotEmpty())
        {{-- Eventi Futuri Header --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1 h-px bg-gradient-to-r from-transparent via-warning-300 to-transparent dark:via-warning-700"></div>
            <button @click="showAllFuture = !showAllFuture" 
                    class="flex items-center gap-2 px-4 py-2 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 text-warning-700 dark:text-warning-300 rounded-lg text-sm font-semibold hover:bg-warning-100 dark:hover:bg-warning-900/30 transition-colors">
                <x-filament::icon 
                    x-bind:icon="showAllFuture ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'" 
                    class="w-4 h-4"
                />
                <span>Eventi Futuri</span>
                <x-filament::badge color="warning" size="sm">
                    {{ $futureActivities->count() }}
                </x-filament::badge>
            </button>
            <div class="flex-1 h-px bg-gradient-to-l from-transparent via-warning-300 to-transparent dark:via-warning-700"></div>
        </div>

        {{-- Eventi Futuri Collassabili --}}
        <div x-show="showAllFuture" 
             x-collapse
             class="space-y-0">
            @foreach ($futureActivities as $index => $activity)
                <x-timeline-activity-item 
                    :activity="$activity"
                    :index="$index"
                    :isLast="$loop->last && $pastActivities->isEmpty()"
                    :isFuture="true"
                    :showTimestamp="$showTimestamp"
                    :showAttachments="$showAttachments"
                />
            @endforeach
        </div>
    @endif

    {{-- Separatore OGGI --}}
    @if ($futureActivities->isNotEmpty() && $pastActivities->isNotEmpty())
        <div class="relative py-6">
            <div class="absolute left-5 top-0 bottom-0 w-px bg-gradient-to-b from-warning-400 via-primary-500 to-success-400 dark:from-warning-600 dark:via-primary-700 dark:to-success-600"></div>
            <div class="flex items-center gap-3">
                <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full bg-primary-500 border-4 border-white dark:border-gray-900 shadow-lg">
                    <x-filament::icon icon="heroicon-o-calendar" class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1 h-px bg-primary-500 dark:bg-primary-600"></div>
                <div class="flex items-center gap-2">
                    <x-filament::badge color="primary">
                        OGGI
                    </x-filament::badge>
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ $now->format('d/m/Y') }}
                    </span>
                </div>
                <div class="flex-1 h-px bg-primary-500 dark:bg-primary-600"></div>
            </div>
        </div>
    @endif

    {{-- Eventi Passati Recenti (sempre visibili) --}}
    @foreach ($recentPast as $index => $activity)
        <x-timeline-activity-item 
            :activity="$activity"
            :index="$index"
            :isLast="$olderPast->isEmpty() && $loop->last"
            :isFuture="false"
            :showTimestamp="$showTimestamp"
            :showAttachments="$showAttachments"
        />
    @endforeach

    {{-- Eventi Passati Vecchi (collassabili) --}}
    @if ($olderPast->isNotEmpty())
        <div class="flex items-center gap-3 my-4">
            <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent dark:via-gray-700"></div>
            <button @click="showAllPast = !showAllPast" 
                    class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <x-filament::icon 
                    x-bind:icon="showAllPast ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'" 
                    class="w-4 h-4"
                />
                <span>Eventi Precedenti</span>
                <x-filament::badge color="gray" size="sm">
                    {{ $olderPast->count() }}
                </x-filament::badge>
            </button>
            <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent dark:via-gray-700"></div>
        </div>

        <div x-show="showAllPast" 
             x-collapse
             class="space-y-0">
            @foreach ($olderPast as $index => $activity)
                <x-timeline-activity-item 
                    :activity="$activity"
                    :index="$index + $recentPast->count()"
                    :isLast="$loop->last"
                    :isFuture="false"
                    :showTimestamp="$showTimestamp"
                    :showAttachments="$showAttachments"
                />
            @endforeach
        </div>
    @endif

    {{-- Empty State --}}
    @if (collect($activities)->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <x-filament::icon 
                icon="heroicon-o-inbox" 
                class="w-12 h-12 text-gray-400 dark:text-gray-600 mb-3"
            />
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Nessuna attività da mostrare</p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Le attività future e passate appariranno qui</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
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
    });
</script>
@endpush
