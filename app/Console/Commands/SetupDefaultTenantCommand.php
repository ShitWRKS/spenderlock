<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupDefaultTenantCommand extends Command
{
    protected $signature = 'tenants:setup-default 
                           {--tenant-name= : Nome del tenant}
                           {--tenant-domain= : Dominio del tenant}
                           {--admin-name= : Nome dell\'admin}
                           {--admin-email= : Email dell\'admin}
                           {--admin-password= : Password dell\'admin}';

    protected $description = 'Setup automatico del tenant di default con admin user';

    public function handle(): int
    {
        try {
            $config = $this->getConfiguration();
            $this->displaySetupInfo($config);
            
            $tenant = $this->findOrCreateTenant($config['tenant']);
            $this->createAdminUser($tenant, $config['admin']);
            
            $this->displaySuccess($tenant, $config['admin']);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Errore durante il setup: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function getConfiguration(): array
    {
        return [
            'tenant' => [
                'name' => $this->option('tenant-name') ?: env('DEFAULT_TENANT_NAME', 'SpenderLock Demo'),
                'domain' => $this->option('tenant-domain') ?: env('DEFAULT_TENANT_DOMAIN', 'localhost'),
            ],
            'admin' => [
                'name' => $this->option('admin-name') ?: env('DEFAULT_ADMIN_NAME', 'Administrator'),
                'email' => $this->option('admin-email') ?: env('DEFAULT_ADMIN_EMAIL', 'admin@localhost'),
                'password' => $this->option('admin-password') ?: env('DEFAULT_ADMIN_PASSWORD', 'password'),
            ],
        ];
    }

    private function displaySetupInfo(array $config): void
    {
        $this->info("🚀 Setup tenant di default");
        $this->info("📋 Tenant: {$config['tenant']['name']} ({$config['tenant']['domain']})");
        $this->info("👤 Admin: {$config['admin']['name']} ({$config['admin']['email']})");
    }

    private function findOrCreateTenant(array $config): Tenant
    {
        $tenant = Tenant::where('domain', $config['domain'])->first();
        
        if ($tenant) {
            $this->info("✅ Tenant già esistente (ID: {$tenant->id})");
            $this->ensureTenantDatabaseExists($tenant, $config['name']);
            return $tenant;
        }

        return $this->createTenant($config);
    }

    private function ensureTenantDatabaseExists(Tenant $tenant, string $name): void
    {
        // Ricostruisci il path corretto del database per l'ambiente corrente
        $databaseName = $this->generateDatabaseName($name);
        $correctPath = database_path($databaseName);
        
        // Aggiorna il path nel record del tenant se è cambiato
        if ($tenant->database !== $correctPath) {
            $this->line("   🔄 Aggiornamento path database...");
            $tenant->update(['database' => $correctPath]);
        }
        
        // Crea il database se non esiste
        if (!File::exists($correctPath)) {
            $this->line("   🗄️  Database tenant mancante, creazione in corso...");
            
            // Assicura che la directory esista
            $directory = dirname($correctPath);
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            File::put($correctPath, '');
            $this->runTenantMigrations($tenant);
            $this->generatePermissions($tenant);
            $this->line("   ✅ Database ricreato");
        }
    }

    private function createTenant(array $config): Tenant
    {
        $this->info("📝 Creazione tenant...");
        
        $databasePath = $this->createTenantDatabase($config['name']);
        $tenant = $this->createTenantRecord($config, $databasePath);
        
        $this->runTenantMigrations($tenant);
        $this->generatePermissions($tenant);
        
        $this->line("   ✅ Tenant creato (ID: {$tenant->id})");
        
        return $tenant;
    }

    private function createTenantDatabase(string $name): string
    {
        $databaseName = $this->generateDatabaseName($name);
        $databasePath = database_path($databaseName);

        if (!File::exists($databasePath)) {
            $this->line("   🗄️  Creazione database...");
            File::put($databasePath, '');
        }

        return $databasePath;
    }

    private function generateDatabaseName(string $name): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($name));
        return "tenant_{$sanitized}.sqlite";
    }

    private function createTenantRecord(array $config, string $databasePath): Tenant
    {
        return Tenant::create([
            'name' => $config['name'],
            'domain' => $config['domain'],
            'database' => $databasePath,
        ]);
    }

    private function runTenantMigrations(Tenant $tenant): void
    {
        $this->line("   ⚡ Esecuzione migrazioni...");
        
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'migrate --database=tenant --force',
            '--tenant' => $tenant->id,
        ]);
    }

    private function generatePermissions(Tenant $tenant): void
    {
        $this->line("   🛡️  Generazione permessi...");
        
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'shield:generate --all --panel=admin',
            '--tenant' => $tenant->id,
        ]);
    }

    private function createAdminUser(Tenant $tenant, array $config): void
    {
        $this->info("👤 Verifica/creazione utente admin...");

        $tenant->makeCurrent();

        try {
            if ($this->adminExists($config['email'])) {
                $this->info("   ✅ Utente admin già esistente");
                return;
            }

            $user = $this->createUser($config);
            $this->assignSuperAdminRole($user);
            
            $this->line("   ✅ Utente admin creato");
        } finally {
            Tenant::forgetCurrent();
        }
    }

    private function adminExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    private function createUser(array $config): User
    {
        return User::create([
            'name' => $config['name'],
            'email' => $config['email'],
            'password' => Hash::make($config['password']),
        ]);
    }

    private function assignSuperAdminRole(User $user): void
    {
        $role = $this->ensureSuperAdminRoleExists();
        
        if ($role) {
            $user->assignRole($role);
        }
    }

    private function ensureSuperAdminRoleExists(): ?Role
    {
        $role = Role::on('tenant')->where('name', 'super_admin')->first();
        
        if ($role) {
            return $role;
        }

        return $this->createSuperAdminRole();
    }

    private function createSuperAdminRole(): Role
    {
        $this->line("   👑 Creazione ruolo super_admin...");
        
        $role = $this->createRole();
        $this->assignAllPermissionsToRole($role);
        
        return $role;
    }

    private function createRole(): Role
    {
        $role = new Role([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);
        
        $role->setConnection('tenant');
        $role->save();
        
        return $role;
    }

    private function assignAllPermissionsToRole(Role $role): void
    {
        $permissions = Permission::on('tenant')->get();
        
        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
            $this->line("   ✅ Assegnati {$permissions->count()} permessi");
        }
    }

    private function displaySuccess(Tenant $tenant, array $adminConfig): void
    {
        $this->newLine();
        $this->info("🎉 Setup completato con successo!");
        $this->newLine();
        
        $this->displayConfigurationTable($tenant, $adminConfig);
        $this->displayLoginInstructions($tenant, $adminConfig['email'], $adminConfig['password']);
    }

    private function displayConfigurationTable(Tenant $tenant, array $adminConfig): void
    {
        $this->table(['Configurazione', 'Valore'], [
            ['Tenant Name', $tenant->name],
            ['Tenant Domain', $tenant->domain],
            ['Tenant ID', $tenant->id],
            ['Admin Email', $adminConfig['email']],
            ['Admin Password', $adminConfig['password']],
            ['Admin Panel URL', $this->getAdminUrl($tenant)],
        ]);
    }

    private function displayLoginInstructions(Tenant $tenant, string $email, string $password): void
    {
        $url = $this->getAdminUrl($tenant);
        
        $this->newLine();
        $this->comment("💡 Per accedere:");
        $this->line("1. Visita: {$url}");
        $this->line("2. Login: {$email} / {$password}");
        $this->newLine();
    }

    private function getAdminUrl(Tenant $tenant): string
    {
        return "http://{$tenant->domain}/admin";
    }
}