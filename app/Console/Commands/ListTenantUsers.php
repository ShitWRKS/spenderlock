<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;

class ListTenantUsers extends Command
{
    protected $signature = 'tenants:list-users {tenant : ID o dominio del tenant}';
    protected $description = 'Elenca gli utenti in un tenant specifico';

    public function handle()
    {
        $tenantIdentifier = $this->argument('tenant');
        
        $tenant = is_numeric($tenantIdentifier) 
            ? Tenant::find($tenantIdentifier)
            : Tenant::where('domain', $tenantIdentifier)->first();

        if (!$tenant) {
            $this->error("Tenant '{$tenantIdentifier}' non trovato!");
            return 1;
        }

        $tenant->makeCurrent();
        
        $users = User::with('roles')->get();
        
        $this->info("Utenti nel tenant: {$tenant->name} ({$tenant->domain})");
        $this->line("👥 Totale utenti: {$users->count()}");
        $this->line("");
        
        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->join(', ') ?: 'Nessun ruolo';
            $this->line("👤 {$user->name}");
            $this->line("   📧 {$user->email}");
            $this->line("   🛡️  Ruoli: {$roles}");
            $this->line("");
        }
        
        Tenant::forgetCurrent();
        return 0;
    }
}
