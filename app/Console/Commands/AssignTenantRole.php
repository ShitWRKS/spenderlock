<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignTenantRole extends Command
{
    protected $signature = 'tenants:assign-role 
                           {tenant : ID o dominio del tenant}
                           {email : Email dell\'utente}
                           {role : Nome del ruolo da assegnare}';

    protected $description = 'Assegna un ruolo a un utente in un tenant specifico';

    public function handle(): int
    {
        $tenant = $this->findTenant($this->argument('tenant'));
        
        if (!$tenant) {
            $this->error("❌ Tenant non trovato!");
            return Command::FAILURE;
        }

        $tenant->makeCurrent();

        try {
            $user = $this->findUser($this->argument('email'));
            $role = $this->findRole($this->argument('role'));
            
            $user->assignRole($role);
            
            $this->displaySuccess($user, $role, $tenant);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Errore: {$e->getMessage()}");
            return Command::FAILURE;
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

    private function findUser(string $email): User
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            throw new \Exception("Utente con email '{$email}' non trovato!");
        }
        
        return $user;
    }

    private function findRole(string $name): Role
    {
        $role = Role::on('tenant')->where('name', $name)->first();
        
        if (!$role) {
            throw new \Exception("Ruolo '{$name}' non trovato!");
        }
        
        return $role;
    }

    private function displaySuccess(User $user, Role $role, Tenant $tenant): void
    {
        $this->newLine();
        $this->info("✅ Ruolo assegnato con successo!");
        $this->line("👤 Utente: {$user->name} ({$user->email})");
        $this->line("🛡️  Ruolo: {$role->name}");
        $this->line("🏢 Tenant: {$tenant->name}");
        $this->newLine();
    }
}
