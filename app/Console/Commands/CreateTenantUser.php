<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Comando per creare utenti admin in tenant specifici.
 */
class CreateTenantUser extends Command
{
    protected $signature = 'tenants:create-user 
                           {tenant : ID o dominio del tenant}
                           {name : Nome dell\'utente}
                           {email : Email dell\'utente}
                           {password : Password dell\'utente}
                           {--admin : Rendi l\'utente super admin}';

    protected $description = 'Crea un nuovo utente in un tenant specifico';

    public function handle()
    {
        $tenantIdentifier = $this->argument('tenant');
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $makeAdmin = $this->option('admin');

        // Trova il tenant
        $tenant = is_numeric($tenantIdentifier) 
            ? Tenant::find($tenantIdentifier)
            : Tenant::where('domain', $tenantIdentifier)->first();

        if (!$tenant) {
            $this->error("Tenant '{$tenantIdentifier}' non trovato!");
            return 1;
        }

        $this->info("Creazione utente nel tenant: {$tenant->name} ({$tenant->domain})");

        try {
            // Switch al database del tenant
            $tenant->makeCurrent();

            // Verifica se l'utente esiste già
            if (User::where('email', $email)->exists()) {
                $this->error("Utente con email '{$email}' esiste già in questo tenant!");
                return 1;
            }

            // Crea l'utente
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            if ($makeAdmin) {
                // Crea ruoli e permessi se non esistono
                $this->ensureAdminRoleExists();
                
                // Assegna il ruolo super_admin
                $user->assignRole('super_admin');
                $this->info("✅ Utente creato come Super Admin");
            } else {
                $this->info("✅ Utente creato");
            }

            $this->line("👤 Nome: {$user->name}");
            $this->line("📧 Email: {$user->email}");
            $this->line("🏢 Tenant: {$tenant->name}");
            $this->line("🌐 Login: http://{$tenant->domain}:8000/admin");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Errore: " . $e->getMessage());
            return 1;
        } finally {
            // Reset del tenant
            Tenant::forgetCurrent();
        }
    }

    private function ensureAdminRoleExists()
    {
        // Crea permessi base
        $permissions = [
            'view_any_budget', 'view_budget', 'create_budget', 'update_budget', 'delete_budget', 'delete_any_budget',
            'view_any_contact', 'view_contact', 'create_contact', 'update_contact', 'delete_contact', 'delete_any_contact',
            'view_any_contract', 'view_contract', 'create_contract', 'update_contract', 'delete_contract', 'delete_any_contract',
            'view_any_contract_category', 'view_contract_category', 'create_contract_category', 'update_contract_category', 'delete_contract_category', 'delete_any_contract_category',
            'view_any_supplier', 'view_supplier', 'create_supplier', 'update_supplier', 'delete_supplier', 'delete_any_supplier',
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user', 'delete_any_user',
            'view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Crea il ruolo super_admin
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);

        // Assegna tutti i permessi al super_admin
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);
    }
}
