<?php

namespace App\Filament\Resources\SupplierResource\Widgets;

use App\Models\Contact;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ContactsOverview extends BaseWidget
{
    public ?object $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        // Numero totale di contatti per questo fornitore
        $totalContacts = $this->record->contacts()->count();
        
        // Contatti con email
        $contactsWithEmail = $this->record->contacts()->whereNotNull('email')->count();
        
        // Contatti con telefono
        $contactsWithPhone = $this->record->contacts()->whereNotNull('phone')->count();
        
        return [
            Stat::make('Contatti Totali', $totalContacts)
                ->description('Associati a questo fornitore')
                ->color('info')
                ->icon('heroicon-o-users'),
                
            Stat::make('Con Email', $contactsWithEmail)
                ->description("Su $totalContacts totali")
                ->color($contactsWithEmail > 0 ? 'success' : 'warning')
                ->icon('heroicon-o-envelope'),
                
            Stat::make('Con Telefono', $contactsWithPhone)
                ->description("Su $totalContacts totali")
                ->color($contactsWithPhone > 0 ? 'success' : 'warning')
                ->icon('heroicon-o-phone'),
        ];
    }
}
