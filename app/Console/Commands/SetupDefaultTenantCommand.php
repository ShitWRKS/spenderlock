<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Comando per l'inizializzazione automatica del tenant di default.
 * Utilizzato principalmente durante il setup Docker.
 */
class SetupDefaultTenantCommand extends Command
{
    protected $signature = 'tenants:setup-default 
                           {--tenant-name= : Nome del tenant (default: SpenderLock Demo)}
                           {--tenant-domain= : Dominio del tenant (default: localhost)}
                           {--admin-name= : Nome dell\'admin (default: Administrator)}
                           {--admin-email= : Email dell\'admin (default: admin@localhost)}
                           {--admin-password= : Password dell\'admin (default: password)}';

    protected $description = 'Setup automatico del tenant di default con admin user';

    public function handle()
    {
        $tenantName = $this->option('tenant-name') ?: env('DEFAULT_TENANT_NAME', 'SpenderLock Demo');
        $tenantDomain = $this->option('tenant-domain') ?: env('DEFAULT_TENANT_DOMAIN', 'localhost');
        $adminName = $this->option('admin-name') ?: env('DEFAULT_ADMIN_NAME', 'Administrator');
        $adminEmail = $this->option('admin-email') ?: env('DEFAULT_ADMIN_EMAIL', 'admin@localhost');
        $adminPassword = $this->option('admin-password') ?: env('DEFAULT_ADMIN_PASSWORD', 'password');

        $this->info("🚀 Setup tenant di default");
        $this->info("📋 Tenant: {$tenantName} ({$tenantDomain})");
        $this->info("👤 Admin: {$adminName} ({$adminEmail})");

        try {
            // Verifica se il tenant esiste già
            $existingTenant = Tenant::where('domain', $tenantDomain)->first();
            if ($existingTenant) {
                $this->info("✅ Tenant già esistente (ID: {$existingTenant->id})");
                $tenant = $existingTenant;
            } else {
                // Crea il tenant
                $tenant = $this->createDefaultTenant($tenantName, $tenantDomain);
            }

            // Crea l'utente admin se non esiste
            $this->createDefaultAdmin($tenant, $adminName, $adminEmail, $adminPassword);

            $this->displaySuccess($tenant, $adminEmail, $adminPassword);
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Errore durante il setup: " . $e->getMessage());
            return 1;
        }
    }

    private function createDefaultTenant(string $name, string $domain): Tenant
    {
        $this->info("📝 Creazione tenant di default...");

        // Genera il nome del database
        $databaseName = 'tenant_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($name)) . '.sqlite';
        $databasePath = database_path($databaseName);

        // Verifica che il database non esista già
        if (!\Illuminate\Support\Facades\File::exists($databasePath)) {
            // Crea il file database SQLite
            $this->line("   🗄️  Creazione database tenant...");
            \Illuminate\Support\Facades\File::put($databasePath, '');
        }

        // Crea il record tenant nel database landlord
        $tenant = Tenant::create([
            'name' => $name,
            'domain' => $domain,
            'database' => $databasePath,
        ]);

        // Esegui le migrazioni nel database tenant (senza interattività)
        $this->line("   ⚡ Esecuzione migrazioni tenant...");
        \Illuminate\Support\Facades\Artisan::call('tenants:artisan', [
            'artisanCommand' => 'migrate --database=tenant --force',
            '--tenant' => $tenant->id,
        ]);

        $this->line("   ✅ Tenant creato (ID: {$tenant->id})");
        return $tenant;
    }

    private function createDefaultAdmin(Tenant $tenant, string $name, string $email, string $password): void
    {
        $this->info("👤 Verifica/creazione utente admin...");

        // Switch al database del tenant
        $tenant->makeCurrent();

        try {
            // Verifica se l'utente admin esiste già
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $this->info("   ✅ Utente admin già esistente");
                return;
            }

            // Crea l'utente
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            // Assicura che esistano permessi e ruoli
            $this->ensurePermissionsAndRoleExist();
            
            // Ottieni il ruolo super_admin dal database tenant
            $superAdminRole = Role::on('tenant')->where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $user->assignRole($superAdminRole);
            }

            $this->line("   ✅ Utente admin creato");

        } finally {
            // Reset del tenant
            Tenant::forgetCurrent();
        }
    }

    /**
     * Assicura che esistano tutti i permessi e il ruolo super_admin nel tenant.
     */
    private function ensurePermissionsAndRoleExist(): void
    {
        // Controlla se esistono già permessi nel database tenant
        $existingPermissions = Permission::on('tenant')->count();
        
        if ($existingPermissions === 0) {
            $this->line("   📋 Creazione permessi base...");
            $this->createFallbackPermissions();
        }

        // Controlla se esiste il ruolo super_admin nel database tenant
        $superAdminRole = Role::on('tenant')->where('name', 'super_admin')->first();
        
        if (!$superAdminRole) {
            $this->line("   👑 Creazione ruolo super_admin...");
            
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
            }
        }
    }

    /**
     * Crea permessi base di fallback
     */
    private function createFallbackPermissions(): void
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
    }

    private function displaySuccess(Tenant $tenant, string $adminEmail, string $adminPassword): void
    {
        $this->line("");
        $this->info("🎉 Setup completato con successo!");
        $this->line("");
        
        $this->table(['Configurazione', 'Valore'], [
            ['Tenant Name', $tenant->name],
            ['Tenant Domain', $tenant->domain],
            ['Tenant ID', $tenant->id],
            ['Admin Email', $adminEmail],
            ['Admin Password', $adminPassword],
            ['Admin Panel URL', "http://{$tenant->domain}/admin"],
        ]);

        $this->line("");
        $this->comment("💡 Per accedere:");
        $this->line("1. Visita: http://{$tenant->domain}/admin");
        $this->line("2. Login: {$adminEmail} / {$adminPassword}");
        $this->line("");
    }
}