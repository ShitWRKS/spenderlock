<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Contact;
use Illuminate\Support\HtmlString;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Contatti';
    protected static ?string $pluralModelLabel = 'Contatti';
    protected static ?string $modelLabel = 'Contatto';
    protected static ?string $navigationGroup = 'Anagrafica';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Fornitore')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required(),

                Forms\Components\TextInput::make('role')
                    ->label('Ruolo'),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email(),

                Forms\Components\TextInput::make('phone')
                    ->label('Telefono'),

                Forms\Components\Textarea::make('notes')
                    ->label('Note')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Ruolo')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fornitore')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('name')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Nome'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value) => $query->where('name', 'like', "%{$value}%"),
                        );
                    }),

                Filter::make('role')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Ruolo'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value) => $query->where('role', 'like', "%{$value}%"),
                        );
                    }),

                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Fornitore')
                    ->relationship('supplier', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->label('')
                    ->url(fn($record) => $record->email ? 'mailto:' . $record->email : null, true)
                    ->tooltip('Invia email'),

                Tables\Actions\Action::make('phone')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->label('')
                    ->url(fn($record) => $record->phone ? 'tel:' . $record->phone : null, true)
                    ->tooltip('Chiama'),

                Tables\Actions\ViewAction::make()->modalHeading('Dettagli contatto'),
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
            'view' => Pages\ViewContact::route('/{record}'),
        ];
    }
}
