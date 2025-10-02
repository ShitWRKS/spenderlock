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

        // Usa il comando esistente per creare il tenant
        $exitCode = \Illuminate\Support\Facades\Artisan::call('tenants:create', [
            'name' => $name,
            'domain' => $domain,
        ]);

        if ($exitCode !== 0) {
            throw new \Exception("Fallimento nella creazione del tenant");
        }

        $tenant = Tenant::where('domain', $domain)->first();
        if (!$tenant) {
            throw new \Exception("Tenant creato ma non trovato nel database");
        }

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

            // Usa il comando esistente per creare l'utente admin
            $exitCode = \Illuminate\Support\Facades\Artisan::call('tenants:create-user', [
                'tenant' => $tenant->id,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                '--admin' => true,
            ]);

            if ($exitCode !== 0) {
                throw new \Exception("Fallimento nella creazione dell'utente admin");
            }

            $this->line("   ✅ Utente admin creato");

        } finally {
            // Reset del tenant
            Tenant::forgetCurrent();
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