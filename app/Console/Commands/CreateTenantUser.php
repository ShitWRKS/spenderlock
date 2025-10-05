<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateTenantUser extends Command
{
    protected $signature = 'tenants:create-user 
                           {tenant : ID o dominio del tenant}
                           {name : Nome dell\'utente}
                           {email : Email dell\'utente}
                           {password : Password dell\'utente}
                           {--admin : Rendi l\'utente super admin}';

    protected $description = 'Crea un nuovo utente in un tenant specifico';

    public function handle(): int
    {
        try {
            $tenant = $this->findTenant($this->argument('tenant'));
            
            if (!$tenant) {
                $this->error("❌ Tenant '{$this->argument('tenant')}' non trovato!");
                return Command::FAILURE;
            }

            $this->info("🔧 Creazione utente nel tenant: {$tenant->name} ({$tenant->domain})");

            $tenant->makeCurrent();

            try {
                $user = $this->createUser();
                
                if ($this->option('admin')) {
                    $this->makeUserAdmin($user, $tenant);
                }
                
                $this->displayUserInfo($user, $tenant);
                
                return Command::SUCCESS;
            } finally {
                Tenant::forgetCurrent();
            }
        } catch (\Exception $e) {
            $this->error("❌ Errore: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function findTenant(string $identifier): ?Tenant
    {
        return is_numeric($identifier) 
            ? Tenant::find($identifier)
            : Tenant::where('domain', $identifier)->first();
    }

    private function createUser(): User
    {
        $email = $this->argument('email');
        
        if (User::where('email', $email)->exists()) {
            throw new \Exception("Utente con email '{$email}' esiste già in questo tenant!");
        }

        return User::create([
            'name' => $this->argument('name'),
            'email' => $email,
            'password' => Hash::make($this->argument('password')),
        ]);
    }

    private function makeUserAdmin(User $user, Tenant $tenant): void
    {
        $this->ensurePermissionsExist($tenant);
        $role = $this->ensureSuperAdminRoleExists();
        
        $user->assignRole($role);
        $this->info("✅ Utente creato come Super Admin");
    }

    private function ensurePermissionsExist(Tenant $tenant): void
    {
        $count = Permission::on('tenant')->count();
        
        if ($count === 0) {
            $this->info("�️  Generazione permessi con Shield...");
            Artisan::call('tenants:artisan', [
                'artisanCommand' => 'shield:generate --all --panel=admin',
                '--tenant' => $tenant->id,
            ]);
        }
    }

    private function ensureSuperAdminRoleExists(): Role
    {
        $role = Role::on('tenant')->where('name', 'super_admin')->first();
        
        if ($role) {
            $this->syncRolePermissions($role);
            return $role;
        }

        return $this->createSuperAdminRole();
    }

    private function createSuperAdminRole(): Role
    {
        $this->line("   👑 Creazione ruolo super_admin...");
        
        $role = new Role([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);
        
        $role->setConnection('tenant');
        $role->save();

        $this->syncRolePermissions($role);
        
        return $role;
    }

    private function syncRolePermissions(Role $role): void
    {
        $permissions = Permission::on('tenant')->get();
        
        if ($permissions->isEmpty()) {
            return;
        }

        $currentCount = $role->permissions()->count();
        
        if ($currentCount < $permissions->count()) {
            $role->givePermissionTo($permissions);
            $this->line("   ✅ Assegnati {$permissions->count()} permessi");
        }
    }

    private function displayUserInfo(User $user, Tenant $tenant): void
    {
        $this->newLine();
        $this->info("✅ Utente creato con successo!");
        $this->line("👤 Nome: {$user->name}");
        $this->line("📧 Email: {$user->email}");
        $this->line("🏢 Tenant: {$tenant->name}");
        $this->line("🌐 Login: http://{$tenant->domain}:8000/admin");
        $this->newLine();
    }
}