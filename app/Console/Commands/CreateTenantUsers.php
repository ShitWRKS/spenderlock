<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateTenantUsers extends Command
{
    protected $signature = 'tenants:create-users';
    protected $description = 'Create admin users for all tenants with proper roles and permissions';

    private const RESOURCES = ['budget', 'contact', 'contract', 'contract_category', 'supplier', 'user', 'role'];
    private const ACTIONS = ['view_any', 'view', 'create', 'update', 'restore', 'restore_any', 'replicate', 'reorder', 'delete', 'delete_any', 'force_delete', 'force_delete_any'];
    private const WIDGET_PERMISSIONS = ['view_contratti_calendar_widget', 'view_totale_speso_per_anno', 'view_upcoming_contracts'];

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->setupTenant($tenant);
        }
        
        $this->info("✅ All tenants setup completed successfully!");
        return Command::SUCCESS;
    }

    private function setupTenant(Tenant $tenant): void
    {
        $this->info("🔧 Setting up tenant: {$tenant->id}");
        
        tenancy()->initialize($tenant);
        
        try {
            $this->createPermissions();
            $role = $this->createSuperAdminRole();
            $user = $this->createAdminUser($tenant);
            
            $this->assignRoleToUser($user, $role);
            $this->displayUserInfo($user);
        } catch (\Exception $e) {
            $this->handleError($tenant, $e);
        } finally {
            tenancy()->end();
        }
    }

    private function createPermissions(): void
    {
        $this->createResourcePermissions();
        $this->createWidgetPermissions();
    }

    private function createResourcePermissions(): void
    {
        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}_{$resource}",
                    'guard_name' => 'web'
                ]);
            }
        }
    }

    private function createWidgetPermissions(): void
    {
        foreach (self::WIDGET_PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
    }

    private function createSuperAdminRole(): Role
    {
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);
        
        $permissions = Permission::all();
        $role->syncPermissions($permissions);
        
        $this->line("   ✅ Created {$permissions->count()} permissions");
        
        return $role;
    }

    private function createAdminUser(Tenant $tenant): User
    {
        return User::updateOrCreate(
            ['email' => "admin@{$tenant->id}.local"],
            [
                'name' => 'Admin ' . ucfirst($tenant->id),
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
            ]
        );
    }

    private function assignRoleToUser(User $user, Role $role): void
    {
        $user->syncRoles([$role]);
        $this->line("   ✅ User created: {$user->email}");
    }

    private function displayUserInfo(User $user): void
    {
        $user = $user->fresh();
        $roles = $user->roles()->pluck('name')->toArray();
        $permissionCount = $user->getAllPermissions()->count();
        
        $this->line("   👤 Roles: " . implode(', ', $roles));
        $this->line("   🔐 Permissions: {$permissionCount}");
    }

    private function handleError(Tenant $tenant, \Exception $e): void
    {
        $this->error("❌ Error setting up tenant {$tenant->id}: {$e->getMessage()}");
        
        if ($this->option('verbose')) {
            $this->error("Trace: {$e->getTraceAsString()}");
        }
    }
}
