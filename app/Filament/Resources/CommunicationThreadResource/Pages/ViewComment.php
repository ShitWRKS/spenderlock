<?php

namespace App\Filament\Resources\CommunicationThreadResource\Pages;

use App\Filament\Resources\CommunicationThreadResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewComment extends ViewRecord
{
    protected static string $resource = CommunicationThreadResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informazioni Commento')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),
                        Infolists\Components\TextEntry::make('subject_type')
                            ->label('Tipo Entità')
                            ->formatStateUsing(fn (string $state): string => match(class_basename($state)) {
                                'Supplier' => 'Fornitore',
                                'Contact' => 'Contatto',
                                'Contract' => 'Contratto',
                                default => class_basename($state),
                            })
                            ->badge()
                            ->color(fn (string $state): string => match(class_basename($state)) {
                                'Supplier' => 'success',
                                'Contact' => 'info',
                                'Contract' => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('subject.name')
                            ->label('Entità')
                            ->getStateUsing(function ($record) {
                                $subject = $record->subject;
                                if ($subject) {
                                    return match(class_basename($record->subject_type)) {
                                        'Supplier' => $subject->name ?? 'N/A',
                                        'Contact' => $subject->name ?? 'N/A',
                                        'Contract' => $subject->title ?? 'N/A',
                                        default => 'N/A',
                                    };
                                }
                                return 'N/A';
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Data')
                            ->dateTime('d/m/Y H:i:s'),
                        Infolists\Components\IconEntry::make('has_eml_attachment')
                            ->label('Allegato Email')
                            ->boolean()
                            ->trueIcon('heroicon-o-paper-clip')
                            ->falseIcon('heroicon-o-x-mark')
                            ->getStateUsing(function ($record) {
                                return $record->links()->where('link_type', 'email_archive')->exists();
                            }),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Contenuto Commento')
                    ->schema([
                        Infolists\Components\TextEntry::make('comment')
                            ->label('Testo')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('Metadati')
                    ->schema([
                        Infolists\Components\TextEntry::make('metadata.source')
                            ->label('Fonte')
                            ->formatStateUsing(fn ($state): string => match($state) {
                                'gmail' => 'Gmail',
                                'web' => 'Web',
                                'api' => 'API',
                                'system' => 'Sistema',
                                default => ucfirst($state ?? 'N/A'),
                            })
                            ->badge()
                            ->color(fn ($state): string => match($state) {
                                'gmail' => 'danger',
                                'web' => 'primary',
                                'api' => 'warning',
                                'system' => 'secondary',
                                default => 'gray',
                            }),
                        
                        Infolists\Components\IconEntry::make('metadata.is_internal')
                            ->label('Riservato')
                            ->boolean()
                            ->trueIcon('heroicon-o-lock-closed')
                            ->falseIcon('heroicon-o-lock-open'),
                        
                        Infolists\Components\TextEntry::make('metadata.author')
                            ->label('Autore')
                            ->placeholder('N/A'),
                        
                        Infolists\Components\TextEntry::make('metadata.subject_line')
                            ->label('Soggetto')
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Link Correlati')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('links')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('link_type')
                                    ->label('Tipo'),
                                Infolists\Components\TextEntry::make('url')
                                    ->label('URL')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true),
                            ])
                            ->columnSpanFull()
                            ->hidden(fn ($record) => $record->links()->count() === 0),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\Action::make('download_eml')
                ->label('Scarica Email Originale (.eml)')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn ($record) => $record->metadata && $record->metadata->gmail_message_id && $record->links()->where('link_type', 'email_archive')->exists())
                ->action(fn ($record) => redirect()->route('download.eml', ['comment' => $record->id]))
        ];
    }
}
