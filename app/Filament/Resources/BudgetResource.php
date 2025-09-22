<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Filament\Resources\BudgetResource\RelationManagers;
use App\Models\Budget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    protected static ?string $navigationLabel = 'Budget';
    protected static ?string $pluralModelLabel = 'Budget';
    protected static ?string $modelLabel = 'Budget';
    protected static ?string $navigationGroup = 'Area Amministrativa';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('year')
                    ->label('Anno')
                    ->numeric()
                    ->required(),

                Forms\Components\Select::make('category')
                    ->label('Categoria')
                    ->relationship('contractCategory', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('allocated')
                    ->label('Budget Allocato (€)')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('Anno')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contractCategory.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('allocated')
                    ->label('Allocato')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('residuo')
                    ->label('Residuo')
                    ->state(function ($record) {
                        $speso = \App\Support\BudgetHelper::getTotaleAllocatoPerAnno($record->year);
                        return number_format($record->allocated - $speso, 2, ',', '.') . ' €';
                    })
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color(fn($state) => str_contains($state, '-') ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\Filter::make('year')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Anno'),
                    ])
                    ->query(fn($query, $data) => $query->when(
                        $data['value'],
                        fn($query, $value) => $query->where('year', 'like', "%{$value}%"),
                    ))
                    ->default(now()->year),

                Tables\Filters\Filter::make('category')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Categoria'),
                    ])
                    ->query(fn($query, $data) => $query->when(
                        $data['value'],
                        fn($query, $value) => $query->where('category', 'like', "%{$value}%"),
                    )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->modalHeading('Dettagli budget'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
            'view' => Pages\ViewBudget::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\BudgetResource\Widgets\ContractsPerAnnoWidget::class,
        ];
    }
}