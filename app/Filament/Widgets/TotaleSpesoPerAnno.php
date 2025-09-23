<?php

namespace App\Filament\Widgets;

use App\Support\BudgetHelper;
use App\Models\Contract;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Carbon;

class TotaleSpesoPerAnno extends BaseWidget
{

    protected function getStats(): array
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        $currentTotal = $this->getTotalForYear($currentYear);
        $previousTotal = $this->getTotalForYear($previousYear);
        $diff = $currentTotal - $previousTotal;
        $diffFormatted = number_format($diff, 2, ',', '.') . ' €';

        return [
            Stat::make("Totale speso nel $previousYear", number_format($previousTotal, 2, ',', '.') . ' €'),
            Stat::make("Totale speso nel $currentYear", number_format($currentTotal, 2, ',', '.') . ' €'),
            Stat::make('Differenza vs anno precedente', '')
                ->description(($diff >= 0 ? '+ ' : '- ') . $diffFormatted)
                ->color($diff >= 0 ? 'danger' : 'success'),
        ];
    }

    private function getTotalForYear(int $year): float
    {
        return BudgetHelper::getTotaleAllocatoPerAnno($year);
    }
}
