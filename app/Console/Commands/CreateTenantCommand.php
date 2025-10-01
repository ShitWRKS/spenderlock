<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Comando per creare nuovi tenant con database dedicato.
 * 
 * Questo comando:
 * 1. Crea un nuovo record nella tabella tenants (database landlord)
 * 2. Crea il file database SQLite per il tenant
 * 3. Esegue le migrazioni nel nuovo database tenant
 * 4. Opzionalmente esegue i seeder
 */
class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenants:create 
                           {name : Nome del tenant/organizzazione}
                           {domain : Dominio del tenant (es: azienda1.local)}
                           {--seed : Esegui anche i seeder dopo le migrazioni}';

    /**
     * The console command description.
     */
    protected $description = 'Crea un nuovo tenant con database dedicato e migrazioni';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');
        $runSeeders = $this->option('seed');

        // Genera il nome del database
        $databaseName = 'tenant_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($name)) . '.sqlite';
        $databasePath = database_path($databaseName);

        // Verifica che il dominio non esista già
        if (Tenant::where('domain', $domain)->exists()) {
            $this->error("Un tenant con dominio '{$domain}' esiste già!");
            return 1;
        }

        // Verifica che il database non esista già
        if (File::exists($databasePath)) {
            $this->error("Il database '{$databaseName}' esiste già!");
            return 1;
        }

        $this->info("Creazione tenant: {$name}");
        $this->info("Dominio: {$domain}");
        $this->info("Database: {$databaseName}");

        try {
            // 1. Crea il record tenant nel database landlord
            $this->info("📝 Creazione record tenant...");
            $tenant = Tenant::create([
                'name' => $name,
                'domain' => $domain,
                'database' => $databasePath,
            ]);

            // 2. Crea il file database SQLite
            $this->info("🗄️  Creazione database tenant...");
            File::put($databasePath, '');

            // 3. Esegui le migrazioni nel database tenant
            $this->info("⚡ Esecuzione migrazioni tenant...");
            Artisan::call('tenants:artisan', [
                'artisanCommand' => 'migrate --database=tenant',
                '--tenant' => $tenant->id,
            ]);

            // 4. Opzionalmente esegui i seeder
            if ($runSeeders) {
                $this->info("🌱 Esecuzione seeder tenant...");
                Artisan::call('tenants:artisan', [
                    'artisanCommand' => 'db:seed --database=tenant',
                    '--tenant' => $tenant->id,
                ]);
            }

            $this->info("✅ Tenant '{$name}' creato con successo!");
            $this->info("🌐 Accedi su: http://{$domain}");
            $this->line("ID Tenant: {$tenant->id}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Errore durante la creazione del tenant: " . $e->getMessage());
            
            // Cleanup in caso di errore
            if (isset($tenant)) {
                $this->info("🧹 Pulizia dati parziali...");
                $tenant->delete();
            }
            
            if (File::exists($databasePath)) {
                File::delete($databasePath);
            }

            return 1;
        }
    }
}
