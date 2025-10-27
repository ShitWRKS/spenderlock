<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommunicationThreadResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommunicationThreadResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationLabel = 'Conversazioni';
    
    protected static ?string $modelLabel = 'Commento';
    
    protected static ?string $pluralModelLabel = 'Commenti';
    
    protected static ?string $navigationGroup = 'Fornitori & Contatti';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\BadgeColumn::make('subject_type')
                    ->label('Tipo Entità')
                    ->formatStateUsing(fn (string $state): string => match(class_basename($state)) {
                        'Supplier' => 'Fornitore',
                        'Contact' => 'Contatto',
                        'Contract' => 'Contratto',
                        default => class_basename($state),
                    })
                    ->colors([
                        'success' => 'Supplier',
                        'info' => 'Contact',
                        'warning' => 'Contract',
                    ]),
                    
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Entità')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->whereHas('subject', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
                                ->orWhere('title', 'like', "%{$search}%"));
                    })
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('comment')
                    ->label('Commento')
                    ->limit(80)
                    ->html()
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\BadgeColumn::make('metadata.source')
                    ->label('Fonte')
                    ->formatStateUsing(fn ($state): string => match($state) {
                        'gmail' => 'Gmail',
                        'web' => 'Web',
                        'api' => 'API',
                        'system' => 'Sistema',
                        default => ucfirst($state ?? 'N/A'),
                    })
                    ->colors([
                        'danger' => 'gmail',
                        'primary' => 'web',
                        'warning' => 'api',
                        'secondary' => 'system',
                    ])
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('metadata.is_internal')
                    ->label('Riservato')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Tipo Entità')
                    ->options([
                        'App\Models\Supplier' => 'Fornitore',
                        'App\Models\Contact' => 'Contatto',
                        'App\Models\Contract' => 'Contratto',
                    ]),
                    
                Tables\Filters\SelectFilter::make('source')
                    ->label('Fonte')
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            return $query->whereHas('metadata', fn ($q) => $q->where('source', $data['value']));
                        }
                        return $query;
                    })
                    ->options([
                        'web' => 'Web',
                        'gmail' => 'Gmail',
                        'api' => 'API',
                        'system' => 'Sistema',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_internal')
                    ->label('Riservato')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('metadata', fn ($q) => $q->where('is_internal', true)),
                        false: fn (Builder $query) => $query->whereHas('metadata', fn ($q) => $q->where('is_internal', false)),
                    ),
                    
                Tables\Filters\Filter::make('created_at')
                    ->label('Data')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Da'),
                        Forms\Components\DatePicker::make('until')->label('Fino a'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('Visualizza')
                    ->label('')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\GlobalTimeline::route('/'),
            'list' => Pages\ManageCommunicationThreads::route('/list'),
            'view' => Pages\ViewComment::route('/{record}'),
            'timeline' => Pages\GlobalTimeline::route('/timeline'),
        ];
    }
}

