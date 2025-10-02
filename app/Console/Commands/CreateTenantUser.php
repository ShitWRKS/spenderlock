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
                // Controlla e crea permessi/ruoli se necessario
                $this->ensurePermissionsAndRoleExist($tenant);
                
                // Ottieni il ruolo super_admin dal database tenant
                $superAdminRole = Role::on('tenant')->where('name', 'super_admin')->first();
                if (!$superAdminRole) {
                    throw new \Exception("Impossibile trovare il ruolo super_admin dopo la creazione!");
                }
                
                // Assegna il ruolo super_admin utilizzando l'ID del ruolo
                $user->assignRole($superAdminRole);
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

    /**
     * Assicura che esistano tutti i permessi e il ruolo super_admin nel tenant.
     * Se non esistono, li crea automaticamente.
     */
    private function ensurePermissionsAndRoleExist(Tenant $tenant)
    {
        $this->info("🔍 Verifica permessi e ruoli...");

        // Verifica la connessione corrente
        $currentDb = \Illuminate\Support\Facades\DB::connection('tenant')->getDatabaseName();
        $this->info("🔗 Database tenant corrente: " . basename($currentDb ?? 'none'));

        // Controlla se esistono già permessi nel database tenant
        $existingPermissions = Permission::on('tenant')->count();
        
        if ($existingPermissions === 0) {
            $this->info("📋 Nessun permesso trovato nel tenant. Creazione permessi base...");
            $this->createFallbackPermissions();
            $existingPermissions = Permission::on('tenant')->count();
        } else {
            $this->info("   ✅ Permessi già esistenti ({$existingPermissions} trovati)");
        }

        // Controlla se esiste il ruolo super_admin nel database tenant
        $superAdminRole = Role::on('tenant')->where('name', 'super_admin')->first();
        
        if (!$superAdminRole) {
            $this->info("👑 Creazione ruolo super_admin...");
            
            // Crea il ruolo super_admin sul database tenant
            $superAdminRole = new Role([
                'name' => 'super_admin',
                'guard_name' => 'web'
            ]);
            $superAdminRole->setConnection('tenant');
            $superAdminRole->save();

            // Assegna tutti i permessi al ruolo
            $allPermissions = Permission::on('tenant')->get();
            if ($allPermissions->isNotEmpty()) {
                $superAdminRole->givePermissionTo($allPermissions);
                $this->info("   ✅ Ruolo super_admin creato con {$allPermissions->count()} permessi");
            } else {
                $this->warn("   ⚠️  Nessun permesso da assegnare al ruolo");
            }
        } else {
            $this->info("   ✅ Ruolo super_admin già esistente");
            
            // Verifica che abbia tutti i permessi
            $allPermissions = Permission::on('tenant')->get();
            $rolePermissions = $superAdminRole->permissions;
            
            if ($rolePermissions->count() < $allPermissions->count()) {
                $this->info("   🔄 Aggiornamento permessi del ruolo super_admin...");
                $superAdminRole->givePermissionTo($allPermissions);
                $this->info("   ✅ Permessi aggiornati");
            }
        }
    }

    /**
     * Crea permessi base di fallback se la generazione automatica fallisce
     */
    private function createFallbackPermissions()
    {
        $basicPermissions = [
            // Budget permissions
            'view_any_budget', 'view_budget', 'create_budget', 'update_budget', 'delete_budget', 'delete_any_budget',
            'restore_budget', 'restore_any_budget', 'replicate_budget', 'reorder_budget', 'force_delete_budget', 'force_delete_any_budget',
            
            // Contact permissions
            'view_any_contact', 'view_contact', 'create_contact', 'update_contact', 'delete_contact', 'delete_any_contact',
            'restore_contact', 'restore_any_contact', 'replicate_contact', 'reorder_contact', 'force_delete_contact', 'force_delete_any_contact',
            
            // Contract permissions
            'view_any_contract', 'view_contract', 'create_contract', 'update_contract', 'delete_contract', 'delete_any_contract',
            'restore_contract', 'restore_any_contract', 'replicate_contract', 'reorder_contract', 'force_delete_contract', 'force_delete_any_contract',
            
            // Contract Category permissions
            'view_any_contract::category', 'view_contract::category', 'create_contract::category', 'update_contract::category', 
            'delete_contract::category', 'delete_any_contract::category', 'restore_contract::category', 'restore_any_contract::category',
            'replicate_contract::category', 'reorder_contract::category', 'force_delete_contract::category', 'force_delete_any_contract::category',
            
            // Supplier permissions
            'view_any_supplier', 'view_supplier', 'create_supplier', 'update_supplier', 'delete_supplier', 'delete_any_supplier',
            'restore_supplier', 'restore_any_supplier', 'replicate_supplier', 'reorder_supplier', 'force_delete_supplier', 'force_delete_any_supplier',
            
            // User permissions
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user', 'delete_any_user',
            'restore_user', 'restore_any_user', 'replicate_user', 'reorder_user', 'force_delete_user', 'force_delete_any_user',
            
            // Role permissions
            'view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
            
            // Widget permissions
            'widget_TotaleSpesoPerAnno', 'widget_UpcomingContracts', 'widget_ContrattiCalendarWidget',
        ];

        foreach ($basicPermissions as $permission) {
            $perm = new Permission([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            $perm->setConnection('tenant');
            $perm->save();
        }
        
        $this->info("   ✅ Creati " . count($basicPermissions) . " permessi base");
    }
}
