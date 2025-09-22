<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractCategoryResource\Pages;
use App\Filament\Resources\ContractCategoryResource\RelationManagers;
use App\Models\ContractCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractCategoryResource extends Resource
{
    protected static ?string $model = ContractCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    protected static ?string $navigationLabel = 'Categorie Contratti';
    protected static ?string $pluralModelLabel = 'Categorie Contratti';
    protected static ?string $modelLabel = 'Categoria Contratto';
    protected static ?string $navigationGroup = 'Anagrafica';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome Categoria')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome Categoria')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('name')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Nome Categoria'),
                    ])
                    ->query(fn($query, $data) => $query->when(
                        $data['value'],
                        fn($query, $value) => $query->where('name', 'like', "%{$value}%"),
                    )),
            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContractCategories::route('/'),
            'create' => Pages\CreateContractCategory::route('/create'),
            'edit' => Pages\EditContractCategory::route('/{record}/edit'),
        ];
    }
}
