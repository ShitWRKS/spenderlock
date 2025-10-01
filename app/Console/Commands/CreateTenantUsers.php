<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateTenantUsers extends Command
{
    protected $signature = 'tenants:create-users';
    protected $description = 'Create admin users for all tenants with proper roles and permissions';

    public function handle()
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("Setting up tenant: {$tenant->id}");
            
            // Inizializza il contesto del tenant
            tenancy()->initialize($tenant);
            
            try {
                // Crea tutti i permessi per le risorse Filament
                $resources = [
                    'budget',
                    'contact', 
                    'contract',
                    'contract_category',
                    'supplier',
                    'user',
                    'role'
                ];
                
                $actions = [
                    'view_any', 'view', 'create', 'update', 'restore', 'restore_any',
                    'replicate', 'reorder', 'delete', 'delete_any', 
                    'force_delete', 'force_delete_any'
                ];
                
                foreach ($resources as $resource) {
                    foreach ($actions as $action) {
                        Permission::firstOrCreate([
                            'name' => $action . '_' . $resource,
                            'guard_name' => 'web'
                        ]);
                    }
                }
                
                // Aggiungi permessi specifici per i widget
                $widgetPermissions = [
                    'view_contratti_calendar_widget',
                    'view_totale_speso_per_anno',
                    'view_upcoming_contracts'
                ];
                
                foreach ($widgetPermissions as $permission) {
                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web'
                    ]);
                }
                
                // Crea il ruolo super_admin
                $superAdminRole = Role::firstOrCreate([
                    'name' => 'super_admin',
                    'guard_name' => 'web'
                ]);
                
                // Assegna tutti i permessi al super_admin
                $allPermissions = Permission::all();
                $superAdminRole->syncPermissions($allPermissions);
                $this->info("✓ Created {$allPermissions->count()} permissions and assigned to super_admin");
                
                // Crea un utente admin per questo tenant
                $user = User::updateOrCreate(
                    ['email' => 'admin@' . $tenant->id . '.local'],
                    [
                        'name' => 'Admin ' . ucfirst($tenant->id),
                        'password' => bcrypt('password'),
                        'tenant_id' => $tenant->id,
                    ]
                );
                
                // Assegna il ruolo super_admin all'utente
                $user->syncRoles([$superAdminRole]);
                
                $this->info("✓ User created: admin@{$tenant->id}.local with super_admin role");
                
                // Verifica che l'utente abbia effettivamente i ruoli
                $userRoles = $user->fresh()->roles()->pluck('name')->toArray();
                $userPermissions = $user->fresh()->getAllPermissions()->pluck('name')->count();
                $this->info("✓ User roles: " . implode(', ', $userRoles));
                $this->info("✓ User permissions: {$userPermissions}");
                
            } catch (\Exception $e) {
                $this->error("Error setting up tenant {$tenant->id}: " . $e->getMessage());
                $this->error("Trace: " . $e->getTraceAsString());
            } finally {
                // Termina il contesto del tenant
                tenancy()->end();
            }
        }
        
        $this->info("All tenants setup completed successfully!");
        return 0;
    }
}
