<?php

namespace App\Filament\Resources\BudgetResource\Pages;

use App\Filament\Resources\BudgetResource;
use App\Filament\Resources\BudgetResource\Widgets\ContractsPerAnnoWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewBudget extends ViewRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            ContractsPerAnnoWidget::make(['record' => $this->record]),
        ];
    }
}