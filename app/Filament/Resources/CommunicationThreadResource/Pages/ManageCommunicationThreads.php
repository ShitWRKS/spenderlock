<?php

namespace App\Filament\Resources\CommunicationThreadResource\Pages;

use App\Filament\Resources\CommunicationThreadResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCommunicationThreads extends ManageRecords
{
    protected static string $resource = CommunicationThreadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_timeline')
                ->label('Torna alla Timeline')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => CommunicationThreadResource::getUrl('index')),
        ];
    }
}
