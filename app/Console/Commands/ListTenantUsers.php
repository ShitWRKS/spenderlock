<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;

class ListTenantUsers extends Command
{
    protected $signature = 'tenants:list-users {tenant : ID o dominio del tenant}';
    protected $description = 'Elenca gli utenti in un tenant specifico';

    public function handle(): int
    {
        $tenant = $this->findTenant($this->argument('tenant'));
        
        if (!$tenant) {
            $this->error("❌ Tenant '{$this->argument('tenant')}' non trovato!");
            return Command::FAILURE;
        }

        $tenant->makeCurrent();
        
        try {
            $users = User::with('roles')->get();
            $this->displayUsers($tenant, $users);
            
            return Command::SUCCESS;
        } finally {
            Tenant::forgetCurrent();
        }
    }

    private function findTenant(string $identifier): ?Tenant
    {
        return is_numeric($identifier) 
            ? Tenant::find($identifier)
            : Tenant::where('domain', $identifier)->first();
    }

    private function displayUsers(Tenant $tenant, $users): void
    {
        $this->info("👥 Utenti nel tenant: {$tenant->name} ({$tenant->domain})");
        $this->line("� Totale: {$users->count()}");
        $this->newLine();
        
        foreach ($users as $user) {
            $this->displayUser($user);
        }
    }

    private function displayUser(User $user): void
    {
        $roles = $user->roles->pluck('name')->join(', ') ?: 'Nessun ruolo';
        
        $this->line("👤 {$user->name}");
        $this->line("   📧 {$user->email}");
        $this->line("   🛡️  Ruoli: {$roles}");
        $this->newLine();
    }
}
