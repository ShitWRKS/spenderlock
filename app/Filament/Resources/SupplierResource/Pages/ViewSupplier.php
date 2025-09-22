<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\SupplierResource\Widgets\ContactsOverview;
use App\Filament\Resources\SupplierResource\Widgets\ContractsOverview;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ContactsOverview::make(['record' => $this->record]),
            ContractsOverview::make(['record' => $this->record]),
        ];
    }
}