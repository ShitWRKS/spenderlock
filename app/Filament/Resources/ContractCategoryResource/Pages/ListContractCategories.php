<?php

namespace App\Filament\Resources\ContractCategoryResource\Pages;

use App\Filament\Resources\ContractCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractCategories extends ListRecords
{
    protected static string $resource = ContractCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
