<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Data\EventData;

class ContrattiCalendarWidget extends FullCalendarWidget
{
    protected static ?string $heading = 'Scadenze Contratti';
    protected static ?int $sort = 9000;


    public function fetchEvents(array $fetchInfo): array
    {
        return Contract::query()
            ->whereDate('end_date', '>=', $fetchInfo['start'])
            ->whereDate('end_date', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn(Contract $contract) => EventData::make()
                    ->id($contract->id)
                    ->title($contract->title)
                    ->start($contract->end_date)
                    ->url(route('filament.admin.resources.contracts.view', ['record' => $contract]))
                //->shouldOpenUrlInNewTab()
            )
            ->toArray();
    }

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'dayGridMonth,dayGridWeek',
                'center' => 'title',
                'right' => 'prev,next today',
            ],
        ];
    }

}