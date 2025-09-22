<?php

namespace App\Filament\Resources\BudgetResource\Widgets;

use App\Models\Contract;
use App\Support\BudgetHelper;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Carbon;

class ContractsPerAnnoWidget extends BaseWidget
{
    public ?object $record = null;

    protected function getStats(): array
    {
        $year = $this->record?->year ?? now()->year;
        
        // Numero totale di contratti attivi nell'anno
        $contractsCount = $this->getContractsCountForYear($year);
        
        // Totale speso nell'anno
        $totalSpent = BudgetHelper::getTotaleAllocatoPerAnno($year);
        
        // Budget allocato per l'anno (se esiste un record)
        $budgetAllocated = $this->record?->allocated ?? 0;
        
        // Calcolo del residuo
        $residual = $budgetAllocated - $totalSpent;
        
        return [
            Stat::make('Contratti Attivi', $contractsCount)
                ->description("Nell'anno {$year}")
                ->color('info')
                ->icon('heroicon-o-document-text'),
                
            Stat::make('Totale Speso', number_format($totalSpent, 2, ',', '.') . ' €')
                ->description("Nell'anno {$year}")
                ->color('warning')
                ->icon('heroicon-o-currency-euro'),
                
            Stat::make('Residuo Budget', number_format($residual, 2, ',', '.') . ' €')
                ->description($residual >= 0 ? 'Budget disponibile' : 'Budget superato')
                ->color($residual >= 0 ? 'success' : 'danger')
                ->icon($residual >= 0 ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle'),
        ];
    }

    private function getContractsCountForYear(int $year): int
    {
        return Contract::where(function ($query) use ($year) {
            $yearStart = Carbon::create($year, 1, 1)->startOfYear();
            $yearEnd = Carbon::create($year, 12, 31)->endOfYear();
            
            $query->where(function ($q) use ($yearStart, $yearEnd) {
                // Contratti che iniziano nell'anno
                $q->whereBetween('start_date', [$yearStart, $yearEnd])
                  // O contratti che finiscono nell'anno
                  ->orWhereBetween('end_date', [$yearStart, $yearEnd])
                  // O contratti che attraversano tutto l'anno
                  ->orWhere(function ($subQ) use ($yearStart, $yearEnd) {
                      $subQ->where('start_date', '<=', $yearStart)
                           ->where('end_date', '>=', $yearEnd);
                  });
            });
        })->count();
    }
}
