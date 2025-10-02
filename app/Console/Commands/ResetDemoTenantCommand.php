<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

/**
 * Comando per resettare automaticamente il tenant demo.
 * Viene eseguito via scheduler per mantenere il demo sempre pulito.
 */
class ResetDemoTenantCommand extends Command
{
    protected $signature = 'tenants:reset-demo 
                           {--force : Forza il reset senza conferma}';

    protected $description = 'Resetta automaticamente il tenant demo con dati freschi';

    public function handle()
    {
        $demoDomain = env('DEMO_TENANT_DOMAIN', 'demo.spenderlock.com');
        $force = $this->option('force');

        $this->info("🔄 Reset automatico tenant demo");
        $this->info("🌐 Dominio: {$demoDomain}");

        // Trova il tenant demo
        $demoTenant = Tenant::where('domain', $demoDomain)->first();
        
        if (!$demoTenant) {
            $this->warn("⚠️  Tenant demo non trovato. Creazione...");
            return $this->call('tenants:setup-demo');
        }

        if (!$force && !$this->confirm("Sei sicuro di voler resettare il tenant demo? Tutti i dati saranno persi.")) {
            $this->info("Reset annullato.");
            return 0;
        }

        try {
            // Reset del tenant demo
            $exitCode = $this->call('tenants:setup-demo', ['--reset' => true]);
            
            if ($exitCode === 0) {
                $this->info("✅ Tenant demo resettato con successo!");
                
                // Log dell'operazione
                \Illuminate\Support\Facades\Log::info("Demo tenant reset completed", [
                    'tenant_id' => $demoTenant->id,
                    'domain' => $demoDomain,
                    'reset_time' => now(),
                ]);
                
                return 0;
            } else {
                $this->error("❌ Errore durante il reset del tenant demo");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Errore: " . $e->getMessage());
            
            // Log dell'errore
            \Illuminate\Support\Facades\Log::error("Demo tenant reset failed", [
                'tenant_id' => $demoTenant->id,
                'domain' => $demoDomain,
                'error' => $e->getMessage(),
                'reset_time' => now(),
            ]);
            
            return 1;
        }
    }
}