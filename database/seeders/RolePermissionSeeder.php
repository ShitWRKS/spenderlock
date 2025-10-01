<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seeder per creare ruoli e permessi base in ogni tenant.
     */
    public function run(): void
    {
        // Crea permessi base per tutte le risorse
        $permissions = [
            // Budget permissions
            'view_any_budget', 'view_budget', 'create_budget', 'update_budget', 'delete_budget', 'delete_any_budget',
            'restore_budget', 'restore_any_budget', 'replicate_budget', 'reorder_budget', 'force_delete_budget', 'force_delete_any_budget',
            
            // Contact permissions
            'view_any_contact', 'view_contact', 'create_contact', 'update_contact', 'delete_contact', 'delete_any_contact',
            'restore_contact', 'restore_any_contact', 'replicate_contact', 'reorder_contact', 'force_delete_contact', 'force_delete_any_contact',
            
            // Contract permissions
            'view_any_contract', 'view_contract', 'create_contract', 'update_contract', 'delete_contract', 'delete_any_contract',
            'restore_contract', 'restore_any_contract', 'replicate_contract', 'reorder_contract', 'force_delete_contract', 'force_delete_any_contract',
            
            // Contract Category permissions
            'view_any_contract_category', 'view_contract_category', 'create_contract_category', 'update_contract_category', 
            'delete_contract_category', 'delete_any_contract_category', 'restore_contract_category', 'restore_any_contract_category',
            'replicate_contract_category', 'reorder_contract_category', 'force_delete_contract_category', 'force_delete_any_contract_category',
            
            // Supplier permissions
            'view_any_supplier', 'view_supplier', 'create_supplier', 'update_supplier', 'delete_supplier', 'delete_any_supplier',
            'restore_supplier', 'restore_any_supplier', 'replicate_supplier', 'reorder_supplier', 'force_delete_supplier', 'force_delete_any_supplier',
            
            // User permissions
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user', 'delete_any_user',
            'restore_user', 'restore_any_user', 'replicate_user', 'reorder_user', 'force_delete_user', 'force_delete_any_user',
            
            // Role permissions
            'view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
            'restore_role', 'restore_any_role', 'replicate_role', 'reorder_role', 'force_delete_role', 'force_delete_any_role',
            
            // Shield permissions
            'view_shield::resource', 'create_shield::resource', 'update_shield::resource', 'delete_shield::resource',
        ];

        foreach ($permissions as $permission) {
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

        echo "✅ Creati " . $allPermissions->count() . " permessi e ruolo super_admin\n";
    }
}
