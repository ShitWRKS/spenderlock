<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenants:create 
                           {name : Nome del tenant/organizzazione}
                           {domain : Dominio del tenant}
                           {--seed : Esegui anche i seeder dopo le migrazioni}';

    protected $description = 'Crea un nuovo tenant con database dedicato e migrazioni';

    private ?Tenant $tenant = null;
    private ?string $databasePath = null;

    public function handle(): int
    {
        try {
            $this->validateTenant();
            $this->displayTenantInfo();
            
            $this->tenant = $this->createTenantRecord();
            $this->createTenantDatabase();
            $this->runMigrations();
            
            if ($this->option('seed')) {
                $this->runSeeders();
            }
            
            $this->displaySuccess();
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->handleError($e);
            return Command::FAILURE;
        }
    }

    private function validateTenant(): void
    {
        $domain = $this->argument('domain');
        $this->databasePath = $this->generateDatabasePath();
        
        if (Tenant::where('domain', $domain)->exists()) {
            throw new \Exception("Un tenant con dominio '{$domain}' esiste già!");
        }

        if (File::exists($this->databasePath)) {
            throw new \Exception("Il database esiste già!");
        }
    }

    private function generateDatabasePath(): string
    {
        $name = $this->argument('name');
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($name));
        $databaseName = "tenant_{$sanitized}.sqlite";
        
        return database_path($databaseName);
    }

    private function displayTenantInfo(): void
    {
        $this->info("🚀 Creazione tenant: {$this->argument('name')}");
        $this->line("   📋 Dominio: {$this->argument('domain')}");
        $this->line("   🗄️  Database: " . basename($this->databasePath));
    }

    private function createTenantRecord(): Tenant
    {
        $this->line("📝 Creazione record tenant...");
        
        return Tenant::create([
            'name' => $this->argument('name'),
            'domain' => $this->argument('domain'),
            'database' => $this->databasePath,
        ]);
    }

    private function createTenantDatabase(): void
    {
        $this->line("🗄️  Creazione database...");
        File::put($this->databasePath, '');
    }

    private function runMigrations(): void
    {
        $this->line("⚡ Esecuzione migrazioni...");
        
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'migrate --database=tenant',
            '--tenant' => $this->tenant->id,
        ]);
    }

    private function runSeeders(): void
    {
        $this->line("🌱 Esecuzione seeder...");
        
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'db:seed --database=tenant',
            '--tenant' => $this->tenant->id,
        ]);
    }

    private function displaySuccess(): void
    {
        $this->newLine();
        $this->info("✅ Tenant '{$this->tenant->name}' creato con successo!");
        $this->line("🌐 Accedi su: http://{$this->tenant->domain}");
        $this->line("🆔 ID Tenant: {$this->tenant->id}");
        $this->newLine();
    }

    private function handleError(\Exception $e): void
    {
        $this->error("❌ Errore: {$e->getMessage()}");
        $this->cleanup();
    }

    private function cleanup(): void
    {
        $this->line("🧹 Pulizia dati parziali...");
        
        if ($this->tenant) {
            $this->tenant->delete();
        }
        
        if ($this->databasePath && File::exists($this->databasePath)) {
            File::delete($this->databasePath);
        }
    }
}