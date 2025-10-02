<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SimpleDemoSetupCommand extends Command
{
    protected $signature = 'demo:simple-setup 
                           {domain=demo.spenderlock.local : Domain del tenant demo}
                           {--reset : Reset del tenant demo se esiste già}';

    protected $description = 'Setup semplificato del tenant demo con dati di esempio';

    public function handle()
    {
        $domain = $this->argument('domain');
        $shouldReset = $this->option('reset');

        $this->info("🚀 Setup tenant demo: {$domain}");

        // Controlla se il tenant demo esiste già
        $tenant = Tenant::where('name', 'Demo SpenderLock')
                       ->orWhere('domain', $domain)
                       ->first();

        if ($tenant && !$shouldReset) {
            $this->warn("⚠️  Tenant demo già esistente! Usa --reset per ricreare");
            return 1;
        }

        if ($tenant && $shouldReset) {
            $this->info("🔄 Reset del tenant demo esistente...");
            $tenant->delete();
            $tenant = null;
        }

        // Crea il tenant demo usando il comando esistente
        if (!$tenant) {
            $this->info("📦 Creazione nuovo tenant demo...");
            $exitCode = $this->call('tenants:create', [
                'name' => 'Demo SpenderLock',
                'domain' => $domain
            ]);

            if ($exitCode !== 0) {
                $this->error("❌ Errore nella creazione del tenant");
                return 1;
            }

            // Recupera il tenant appena creato
            $tenant = Tenant::where('domain', $domain)->first();
            if (!$tenant) {
                $this->error("❌ Impossibile trovare il tenant appena creato");
                return 1;
            }
        }

        // Esegui setup nel contesto del tenant
        $tenant->makeCurrent();

        try {
            $this->info("🛡️  Setup permissions e ruoli...");
            $this->setupPermissionsAndRoles();

            $this->info("👤 Creazione utente demo...");
            $this->createDemoUser();

            $this->info("🎨 Creazione dati demo...");
            $this->call('db:seed', ['--class' => 'DemoDataSeeder']);

            $this->info("✅ Setup demo completato con successo!");
            $this->info("🌐 Domain: {$domain}");
            $this->info("👤 Demo User: demo@demo.local / demo123");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Errore durante setup demo: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        } finally {
            Tenant::forgetCurrent();
        }
    }

    private function setupPermissionsAndRoles()
    {
        $this->info("Creazione permessi e ruoli...");
        
        // Lista completa dei permessi di Filament Shield
        $permissionNames = [
            // Budget permissions
            'view_any_budget', 'view_budget', 'create_budget', 'update_budget', 'delete_budget', 'delete_any_budget',
            // Contact permissions
            'view_any_contact', 'view_contact', 'create_contact', 'update_contact', 'delete_contact', 'delete_any_contact',
            // Contract permissions
            'view_any_contract', 'view_contract', 'create_contract', 'update_contract', 'delete_contract', 'delete_any_contract',
            // Contract Category permissions
            'view_any_contract::category', 'view_contract::category', 'create_contract::category', 'update_contract::category', 'delete_contract::category', 'delete_any_contract::category',
            // Role permissions
            'view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
            // Supplier permissions
            'view_any_supplier', 'view_supplier', 'create_supplier', 'update_supplier', 'delete_supplier', 'delete_any_supplier',
            // User permissions
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user', 'delete_any_user',
        ];

        // Crea tutti i permessi sulla connessione tenant
        foreach ($permissionNames as $permissionName) {
            Permission::on('tenant')->firstOrCreate(['name' => $permissionName]);
        }

        // Crea il ruolo super_admin sulla connessione tenant
        $superAdminRole = Role::on('tenant')->firstOrCreate(['name' => 'super_admin']);
        
        // Assegna tutti i permessi al ruolo super_admin
        $allPermissions = Permission::on('tenant')->get();
        $superAdminRole->givePermissionTo($allPermissions);
        
        $this->info("✅ Creati " . count($permissionNames) . " permessi e ruolo super_admin");
    }

    private function createDemoUser()
    {
        // Crea l'utente demo sulla connessione tenant
        $user = User::on('tenant')->firstOrCreate(
            ['email' => 'demo@demo.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('demo123'),
                'email_verified_at' => now(),
            ]
        );

        // Assegna il ruolo super_admin usando la connessione tenant
        $role = Role::on('tenant')->where('name', 'super_admin')->first();
        if ($role && !$user->hasRole('super_admin')) {
            $user->assignRole($role);
        }

        $this->info("✅ Utente demo creato: demo@demo.local / demo123");
        return $user;
    }
}