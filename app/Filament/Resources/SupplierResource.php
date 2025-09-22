<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Fornitori';
    protected static ?string $pluralModelLabel = 'Fornitori';
    protected static ?string $modelLabel = 'Fornitore';
    protected static ?string $navigationGroup = 'Anagrafica';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required(),
                Forms\Components\TextInput::make('vat_number')->label('P. IVA'),
                Forms\Components\TextInput::make('fiscal_code')->label('Codice Fiscale'),
                Forms\Components\TextInput::make('email')->label('Email')->email(),
                Forms\Components\TextInput::make('phone')->label('Telefono'),
                Forms\Components\TextInput::make('address')->label('Indirizzo'),
                Forms\Components\Textarea::make('notes')->label('Note')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefono')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('name')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Nome'),
                    ])
                    ->query(fn($query, $data) => $query->when(
                        $data['value'],
                        fn($query, $value) => $query->where('name', 'like', "%{$value}%"),
                    )),

                Tables\Filters\Filter::make('email')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Email'),
                    ])
                    ->query(fn($query, $data) => $query->when(
                        $data['value'],
                        fn($query, $value) => $query->where('email', 'like', "%{$value}%"),
                    )),

                Tables\Filters\Filter::make('phone')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Telefono'),
                    ])
                    ->query(fn($query, $data) => $query->when(
                        $data['value'],
                        fn($query, $value) => $query->where('phone', 'like', "%{$value}%"),
                    )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->modalHeading('Dettagli fornitore'),
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
            'view' => Pages\ViewSupplier::route('/{record}'),
        ];
    }
}
