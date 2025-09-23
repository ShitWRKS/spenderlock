<?php

namespace App\Filament\Resources\ContractResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
