<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Contract;
use App\Models\ContractCategory;
use App\Models\Contact;
use App\Models\Budget;

class SimpleDemoResetCommand extends Command
{
    protected $signature = 'demo:reset 
                           {domain=demo-final.local : Domain del tenant demo}';

    protected $description = 'Reset del tenant demo mantenendo solo l\'utente demo';

    public function handle()
    {
        $domain = $this->argument('domain');

        $this->info("🔄 Reset tenant demo: {$domain}");

        // Trova il tenant demo
        $tenant = Tenant::where('domain', $domain)->first();

        if (!$tenant) {
            $this->error("❌ Tenant demo non trovato: {$domain}");
            return 1;
        }

        // Esegui reset nel contesto del tenant
        $tenant->makeCurrent();

        try {
            $this->info("🗑️  Cancellazione dati demo...");
            
            // Cancella tutti i dati tranne l'utente demo
            Budget::truncate();
            Contract::truncate();
            Contact::truncate();
            Supplier::truncate();
            ContractCategory::truncate();
            
            // Mantieni solo l'utente demo
            User::where('email', '!=', 'demo@demo.local')->delete();

            $this->info("🛡️  Setup permissions e ruoli...");
            $this->setupPermissionsAndRoles();

            $this->info("🛡️  Riassegno ruoli utente demo...");
            $this->ensureDemoUserRoles();

            $this->info("🎨 Ricreo dati demo freschi...");
            $this->call('db:seed', ['--class' => 'DemoDataSeeder']);

            $this->info("✅ Reset demo completato con successo!");
            $this->info("🌐 Domain: {$domain}");
            $this->info("👤 Demo User: demo@demo.local / demo123");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Errore durante reset demo: " . $e->getMessage());
            return 1;
        } finally {
            Tenant::forgetCurrent();
        }
    }

    private function ensureDemoUserRoles()
    {
        // Trova l'utente demo
        $user = User::on('tenant')->where('email', 'demo@demo.local')->first();
        
        if (!$user) {
            $this->error("❌ Utente demo non trovato!");
            return;
        }

        // Assicurati che esista il ruolo super_admin
        $role = Role::on('tenant')->where('name', 'super_admin')->first();
        if (!$role) {
            $this->error("❌ Ruolo super_admin non trovato!");
            return;
        }

        // Rimuovi tutti i ruoli esistenti e riassegna super_admin
        $user->roles()->detach();
        $user->assignRole($role);
        
        $this->info("✅ Ruolo super_admin riassegnato all'utente demo");
    }

    private function setupPermissionsAndRoles()
    {
        // Aggiungiamo l'import per Permission
        $permissionClass = \Spatie\Permission\Models\Permission::class;
        
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
            $permissionClass::on('tenant')->firstOrCreate(['name' => $permissionName]);
        }

        // Crea il ruolo super_admin sulla connessione tenant
        $superAdminRole = Role::on('tenant')->firstOrCreate(['name' => 'super_admin']);
        
        // Assegna tutti i permessi al ruolo super_admin
        $allPermissions = $permissionClass::on('tenant')->get();
        $superAdminRole->givePermissionTo($allPermissions);
        
        $this->info("✅ Creati " . count($permissionNames) . " permessi e ruolo super_admin");
    }
}