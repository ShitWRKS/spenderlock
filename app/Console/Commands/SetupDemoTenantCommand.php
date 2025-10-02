<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Comando per creare e gestire il tenant DEMO.
 * Il tenant demo si resetta periodicamente mantenendo solo l'utente demo.
 */
class SetupDemoTenantCommand extends Command
{
    protected $signature = 'tenants:setup-demo 
                           {--reset : Resetta il tenant demo esistente}
                           {--demo-domain= : Dominio del tenant demo (default: demo.tuodominio.com)}
                           {--demo-email= : Email utente demo (default: demo@tuodominio.com)}
                           {--demo-password= : Password utente demo (default: demo123)}';

    protected $description = 'Setup tenant DEMO con reset automatico e dati di esempio';

    public function handle()
    {
        $demoDomain = $this->option('demo-domain') ?: env('DEMO_TENANT_DOMAIN', 'demo.spenderlock.com');
        $demoEmail = $this->option('demo-email') ?: env('DEMO_ADMIN_EMAIL', 'demo@spenderlock.com');
        $demoPassword = $this->option('demo-password') ?: env('DEMO_ADMIN_PASSWORD', 'demo123');
        $reset = $this->option('reset');

        $this->info("🎭 Setup Tenant DEMO");
        $this->info("🌐 Dominio: {$demoDomain}");
        $this->info("👤 Demo User: {$demoEmail}");

        try {
            // Verifica se esiste già un tenant demo
            $existingTenant = Tenant::where('domain', $demoDomain)->first();
            
            if ($existingTenant && !$reset) {
                $this->info("✅ Tenant demo già esistente (ID: {$existingTenant->id})");
                $this->info("💡 Usa --reset per resettare il tenant demo");
                return 0;
            }

            if ($existingTenant && $reset) {
                $this->info("🔄 Reset tenant demo esistente...");
                $this->resetDemoTenant($existingTenant);
            } else {
                $this->info("🆕 Creazione nuovo tenant demo...");
                $existingTenant = $this->createDemoTenant($demoDomain);
            }

            // Setup completo del tenant demo
            $this->setupDemoData($existingTenant, $demoEmail, $demoPassword);

            $this->displayDemoInfo($existingTenant, $demoEmail, $demoPassword);
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Errore durante il setup demo: " . $e->getMessage());
            return 1;
        }
    }

    private function createDemoTenant(string $domain): Tenant
    {
        // Usa il comando esistente per creare il tenant
        $exitCode = Artisan::call('tenants:create', [
            'name' => 'SpenderLock DEMO',
            'domain' => $domain,
        ]);

        if ($exitCode !== 0) {
            throw new \Exception("Fallimento nella creazione del tenant demo");
        }

        $tenant = Tenant::where('domain', $domain)->first();
        if (!$tenant) {
            throw new \Exception("Tenant demo creato ma non trovato nel database");
        }

        $this->line("   ✅ Tenant demo creato (ID: {$tenant->id})");
        return $tenant;
    }

    private function resetDemoTenant(Tenant $tenant): void
    {
        $this->info("🗑️  Reset database tenant demo...");
        
        // Rimuovi il database esistente
        if (File::exists($tenant->database)) {
            File::delete($tenant->database);
            $this->line("   ✅ Database demo rimosso");
        }

        // Ricrea il database e esegui migrazioni
        File::put($tenant->database, '');
        
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'migrate --database=tenant',
            '--tenant' => $tenant->id,
        ]);

        $this->line("   ✅ Database demo resettato e migrato");
    }

    private function setupDemoData(Tenant $tenant, string $demoEmail, string $demoPassword): void
    {
        $this->info("🎨 Setup dati demo...");
        
        // Switch al database del tenant
        $tenant->makeCurrent();

        try {
            // Crea permessi e ruoli
            $this->createDemoPermissions();
            
            // Crea utente demo
            $demoUser = $this->createDemoUser($demoEmail, $demoPassword);
            
            // Crea dati di esempio
            $this->createSampleData();

        } finally {
            // Reset del tenant
            Tenant::forgetCurrent();
        }
    }

    private function createDemoPermissions(): void
    {
        $this->line("🔑 Creazione permessi demo...");

        // Crea permessi base usando la connessione tenant
        $permissions = [
            'view_any_budget', 'view_budget', 'create_budget', 'update_budget', 'delete_budget', 'delete_any_budget',
            'view_any_contact', 'view_contact', 'create_contact', 'update_contact', 'delete_contact', 'delete_any_contact',
            'view_any_contract', 'view_contract', 'create_contract', 'update_contract', 'delete_contract', 'delete_any_contract',
            'view_any_contract::category', 'view_contract::category', 'create_contract::category', 'update_contract::category', 'delete_contract::category', 'delete_any_contract::category',
            'view_any_supplier', 'view_supplier', 'create_supplier', 'update_supplier', 'delete_supplier', 'delete_any_supplier',
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user', 'delete_any_user',
            'view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
        ];

        foreach ($permissions as $permissionName) {
            $permission = new Permission([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            $permission->setConnection('tenant');
            $permission->save();
        }

        // Crea ruolo demo_admin usando la connessione tenant
        $demoRole = new Role([
            'name' => 'demo_admin',
            'guard_name' => 'web'
        ]);
        $demoRole->setConnection('tenant');
        $demoRole->save();

        // Assegna tutti i permessi al ruolo demo
        $allPermissions = Permission::on('tenant')->get();
        $demoRole->givePermissionTo($allPermissions);

        $this->line("   ✅ Permessi e ruolo demo configurati");
    }

    private function createDemoUser(string $email, string $password): User
    {
        $this->line("👤 Creazione utente demo...");

        $user = User::create([
            'name' => 'Demo User',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        // Ottieni il ruolo dal database tenant usando la connessione corretta
        $demoRole = Role::on('tenant')->where('name', 'demo_admin')->first();
        if ($demoRole) {
            $user->assignRole($demoRole);
            $this->line("   ✅ Ruolo demo_admin assegnato");
        } else {
            $this->warn("   ⚠️  Ruolo demo_admin non trovato, utente creato senza ruolo");
        }

        $this->line("   ✅ Utente demo creato");
        return $user;
    }

    private function createSampleData(): void
    {
        $this->line("📊 Creazione dati di esempio...");

        // Crea categorie contratto di esempio
        $categories = [
            'Software e Licenze',
            'Servizi IT',
            'Marketing e Pubblicità',
            'Consulenze',
            'Manutenzioni',
        ];

        foreach ($categories as $categoryName) {
            \App\Models\ContractCategory::create([
                'name' => $categoryName,
                'description' => "Categoria per {$categoryName}",
            ]);
        }

        // Crea fornitori di esempio
        $suppliers = [
            ['name' => 'TechSoft Solutions', 'email' => 'info@techsoft.com', 'phone' => '+39 02 1234567'],
            ['name' => 'Digital Marketing Pro', 'email' => 'contact@digitalmarketing.com', 'phone' => '+39 06 7654321'],
            ['name' => 'IT Consulting Group', 'email' => 'hello@itconsulting.com', 'phone' => '+39 011 9876543'],
        ];

        foreach ($suppliers as $supplierData) {
            \App\Models\Supplier::create($supplierData);
        }

        // Crea contratti di esempio
        $contracts = [
            [
                'title' => 'Licenza Software CRM',
                'supplier_id' => 1,
                'contract_category_id' => 1,
                'start_date' => now()->subMonths(6),
                'end_date' => now()->addMonths(6),
                'amount' => 12000.00,
                'status' => 'active',
                'description' => 'Contratto per licenza software CRM aziendale',
            ],
            [
                'title' => 'Campagna Marketing Q4',
                'supplier_id' => 2,
                'contract_category_id' => 3,
                'start_date' => now()->subMonths(2),
                'end_date' => now()->addMonths(1),
                'amount' => 8500.00,
                'status' => 'active',
                'description' => 'Campagna marketing digitale per Q4',
            ],
            [
                'title' => 'Consulenza IT Annuale',
                'supplier_id' => 3,
                'contract_category_id' => 4,
                'start_date' => now()->subYear(),
                'end_date' => now()->addDays(30),
                'amount' => 15000.00,
                'status' => 'expiring',
                'description' => 'Consulenza IT per tutto l\'anno',
            ],
        ];

        foreach ($contracts as $contractData) {
            \App\Models\Contract::create($contractData);
        }

        // Crea budget di esempio
        $budgets = [
            [
                'year' => now()->year,
                'contract_category_id' => 1,
                'allocated_amount' => 50000.00,
                'spent_amount' => 12000.00,
            ],
            [
                'year' => now()->year,
                'contract_category_id' => 3,
                'allocated_amount' => 30000.00,
                'spent_amount' => 8500.00,
            ],
            [
                'year' => now()->year,
                'contract_category_id' => 4,
                'allocated_amount' => 40000.00,
                'spent_amount' => 15000.00,
            ],
        ];

        foreach ($budgets as $budgetData) {
            \App\Models\Budget::create($budgetData);
        }

        $this->line("   ✅ Dati di esempio creati");
    }

    private function displayDemoInfo(Tenant $tenant, string $demoEmail, string $demoPassword): void
    {
        $this->line("");
        $this->info("🎉 Tenant DEMO configurato con successo!");
        $this->line("");
        
        $this->table(['Configurazione Demo', 'Valore'], [
            ['Nome Tenant', $tenant->name],
            ['Dominio Demo', $tenant->domain],
            ['ID Tenant', $tenant->id],
            ['Demo Email', $demoEmail],
            ['Demo Password', $demoPassword],
            ['Demo URL', "https://{$tenant->domain}/admin"],
        ]);

        $this->line("");
        $this->comment("🎭 Dati Demo Inclusi:");
        $this->line("• 5 Categorie contratto");
        $this->line("• 3 Fornitori di esempio");
        $this->line("• 3 Contratti attivi/in scadenza");
        $this->line("• Budget per anno corrente");
        $this->line("• Utente demo con tutti i permessi");
        
        $this->line("");
        $this->comment("💡 Comandi utili:");
        $this->line("• Reset demo: php artisan tenants:setup-demo --reset");
        $this->line("• Programma reset: php artisan schedule:work");
        $this->line("");
        
        $this->warn("⚠️  IMPORTANTE: Questo tenant verrà resettato automaticamente ogni giorno!");
    }
}