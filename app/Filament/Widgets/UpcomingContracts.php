<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class UpcomingContracts extends BaseWidget
{
    protected static ?int $sort = 1000;
    protected static ?string $heading = 'Contratti in scadenza';

    protected function getFormSchema(): array
    {
        $currentYear = now()->year;
        $years = range($currentYear, $currentYear + 5);

        return [
            Forms\Components\Select::make('year')
                ->label('Anno')
                ->options(array_combine($years, $years))
                ->default($currentYear)
                ->required(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $year = now()->year;
                return Contract::query()
                    ->whereDate('start_date', '<=', now()->endOfYear())
                    ->whereDate('end_date', '>=', now()->startOfYear())
                    ->orderBy('end_date');
            })
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fornitore')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Scade il')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('giorni')
                    ->label('Giorni rimanenti')
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->state(function ($record) {
                        $days = now()->diffInDays(Carbon::parse($record->end_date), false);
                        return $days < 0 ? 'Scaduto' : intval($days) . ' giorni';
                    })
                    ->color(function ($record) {
                        $days = now()->diffInDays(Carbon::parse($record->end_date), false);
                        if ($days < 0)
                            return 'danger';
                        if ($days <= 15)
                            return 'danger';
                        if ($days <= 30)
                            return 'warning';
                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('importo_annuale')
                    ->label('Importo Anno')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('primary')
                    ->state(function ($record) {
                        return \App\Support\BudgetHelper::getTotaleAllocatoPerAnno(now()->year);
                    })
            ])
            ->recordUrl(fn($record) => route('filament.admin.resources.contracts.view', ['record' => $record]));
    }

    public function getColumnSpan(): int|string
    {
        return 'full';
    }
}