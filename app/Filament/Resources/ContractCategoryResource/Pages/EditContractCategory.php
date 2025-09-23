<?php

namespace App\Filament\Resources\ContractCategoryResource\Pages;

use App\Filament\Resources\ContractCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractCategory extends EditRecord
{
    protected static string $resource = ContractCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
