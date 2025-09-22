<?php

namespace App\Filament\Resources\SupplierResource\Widgets;

use App\Models\Contract;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ContractsOverview extends BaseWidget
{
    public ?object $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        // Numero totale di contratti per questo fornitore
        $totalContracts = $this->record->contracts()->count();
        
        // Contratti attivi (non scaduti)
        $activeContracts = $this->record->contracts()
            ->where('end_date', '>=', now())
            ->count();
        
        // Valore totale dei contratti
        $totalValue = $this->record->contracts()->sum('amount_total');
        
        // Contratti in scadenza nei prossimi 30 giorni
        $expiringContracts = $this->record->contracts()
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays(30))
            ->count();
        
        return [
            Stat::make('Contratti Totali', $totalContracts)
                ->description('Associati a questo fornitore')
                ->color('info')
                ->icon('heroicon-o-document-text'),
                
            Stat::make('Contratti Attivi', $activeContracts)
                ->description("Su $totalContracts totali")
                ->color($activeContracts > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-check-circle'),
                
            Stat::make('Valore Totale', number_format($totalValue, 2, ',', '.') . ' €')
                ->description('Somma di tutti i contratti')
                ->color('primary')
                ->icon('heroicon-o-currency-euro'),
                
            Stat::make('In Scadenza', $expiringContracts)
                ->description('Prossimi 30 giorni')
                ->color($expiringContracts > 0 ? 'warning' : 'success')
                ->icon($expiringContracts > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-badge'),
        ];
    }
}
