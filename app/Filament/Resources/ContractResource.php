<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Contratti';
    protected static ?string $pluralModelLabel = 'Contratti';
    protected static ?string $modelLabel = 'Contratto';
    protected static ?string $navigationGroup = 'Area Amministrativa';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('Anagrafica contratto')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->label('Fornitore')
                            ->searchable()
                            ->required()
                            ->preload(),
                        Forms\Components\TextInput::make('title')
                            ->label('Titolo')
                            ->required(),
                        Forms\Components\Select::make('contract_category_id')
                            ->label('Categoria')
                            ->options(\App\Models\ContractCategory::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Forms\Components\Fieldset::make('Dettagli economici e scadenze')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data Inizio')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Data Fine')
                            ->required(),
                        Forms\Components\Select::make('renewal_mode')
                            ->label('Rinnovo')
                            ->options([
                                'manual' => 'Manuale',
                                'automatic' => 'Automatico',
                            ])
                            ->required()
                            ->preload(),
                        Forms\Components\TextInput::make('amount_total')
                            ->label('Importo Totale')
                            ->prefix('€')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('amount_recurring')
                            ->label('Importo Ricorrente')
                            ->prefix('€')
                            ->numeric(),
                        Forms\Components\TextInput::make('frequency_months')
                            ->label('Frequenza (mesi)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                        Forms\Components\Select::make('payment_type')
                            ->label('Tipo Pagamento')
                            ->options([
                                'bonifico' => 'Bonifico',
                                'carta' => 'Carta di credito',
                                'contanti' => 'Contanti',
                                'altro' => 'Altro',
                            ])
                            ->nullable()
                            ->preload(),
                    ]),

                Forms\Components\Fieldset::make('Note e allegati')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Note')
                            ->columnSpanFull(),
                        Forms\Components\Tabs::make('Allegati')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Contratto')
                                    ->schema([
                                        Forms\Components\FileUpload::make('attachments_contract')
                                            ->label('File Contratto')
                                            ->multiple()
                                            ->reorderable()
                                            ->directory('contracts/contratto')
                                            ->downloadable()
                                            ->previewable(true)
                                            ->imagePreviewHeight('100')
                                            ->openable()
                                            ->preserveFilenames(),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Ordini')
                                    ->schema([
                                        Forms\Components\FileUpload::make('attachments_orders')
                                            ->label('Ordini Allegati')
                                            ->multiple()
                                            ->reorderable()
                                            ->directory('contracts/ordini')
                                            ->downloadable()
                                            ->previewable(true)
                                            ->imagePreviewHeight('100')
                                            ->openable()
                                            ->preserveFilenames(),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Documenti')
                                    ->schema([
                                        Forms\Components\FileUpload::make('attachments_documents')
                                            ->label('Altri Documenti')
                                            ->multiple()
                                            ->reorderable()
                                            ->directory('contracts/documenti')
                                            ->downloadable()
                                            ->previewable(true)
                                            ->imagePreviewHeight('100')
                                            ->openable()
                                            ->preserveFilenames(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inizio')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fine')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('renewal_mode')
                    ->label('Rinnovo')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('frequency_months')
                    ->label('Frequenza (mesi)')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Pagamento')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('secondary'),
                Tables\Columns\TextColumn::make('amount_total')
                    ->label('Totale')
                    ->sortable()
                    ->toggleable()
                    ->money('EUR')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('importi_annuali')
                    ->label('Spesa per anno')
                    ->formatStateUsing(function ($state, Contract $record) {
                        $start = \Carbon\Carbon::parse($record->start_date);
                        $end = \Carbon\Carbon::parse($record->end_date);
                        $years = range($start->year, $end->year);
                        $totalMonths = $start->diffInMonths($end) + 1;
                        $monthlyRate = $totalMonths > 0 ? $record->amount_total / $totalMonths : 0;

                        $badges = [];

                        foreach ($years as $year) {
                            $yearStart = \Carbon\Carbon::create($year, 1, 1);
                            $yearEnd = \Carbon\Carbon::create($year, 12, 31);
                            $effectiveStart = $start->gt($yearStart) ? $start : $yearStart;
                            $effectiveEnd = $end->lt($yearEnd) ? $end : $yearEnd;

                            if ($effectiveStart->gt($effectiveEnd)) {
                                continue;
                            }

                            $months = $effectiveStart->diffInMonths($effectiveEnd) + 1;

                            if ($months > 0) {
                                $importo = $monthlyRate * $months;
                                $label = "{$year}: " . number_format($importo, 2, ',', '.') . ' €';
                                $badges[] = "<span class='inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 ring-1 ring-inset ring-primary-600/10'>$label</span>";
                            }
                        }

                        return implode('&nbsp;', $badges);
                    })
                    ->html()
                    ->toggleable(),
            ])
            ->recordUrl(fn(Contract $record) => route('filament.admin.resources.contracts.view', ['record' => $record]))
            ->filters([
                Tables\Filters\Filter::make('title')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Titolo'),
                    ])
                    ->query(fn($query, $data) => $query->when(
                        $data['value'],
                        fn($query, $value) => $query->where('title', 'like', "%{$value}%"),
                    )),

                Tables\Filters\SelectFilter::make('renewal_mode')
                    ->options([
                        'manual' => 'Manuale',
                        'automatic' => 'Automatico',
                    ])
                    ->label('Rinnovo'),

                Tables\Filters\SelectFilter::make('payment_type')
                    ->options([
                        'bonifico' => 'Bonifico',
                        'carta' => 'Carta di credito',
                        'contanti' => 'Contanti',
                        'altro' => 'Altro',
                    ])
                    ->label('Tipo Pagamento'),

                Tables\Filters\SelectFilter::make('contract_category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Fornitore')
                    ->relationship('supplier', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
            'view' => Pages\ViewContract::route('/{record}'),
        ];
    }
}
