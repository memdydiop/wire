<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔐 Création des permissions et rôles...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========== CRÉER LES PERMISSIONS ==========
        $permissions = [
            // Gestion des utilisateurs
            'view users',
            'create users',
            'edit users',
            'delete users',
            'suspend users',
            
            // Gestion des invitations
            'view invitations',
            'create invitations',
            'resend invitations',
            'revoke invitations',
            'delete invitations',
            
            // Gestion des rôles et permissions
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'assign roles',
            
            'view permissions',
            'assign permissions',
            
            // Gestion du contenu (pour futurs modules)
            'view content',
            'create content',
            'edit content',
            'delete content',
            'publish content',
            
            // Logs d'activité
            'view activity logs',
            'delete activity logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        //$this->command->info("✓ {count($permissions)} permissions créées");

        // ========== CRÉER LES RÔLES ==========
        
        // 1. Ghost (Super Admin)
        $ghost = Role::create([
            'name' => 'ghost',
            'description' => 'Super administrateur avec tous les droits',
        ]);
        $ghost->givePermissionTo(Permission::all());
        $this->command->info('✓ Rôle Ghost créé (toutes permissions)');

        // 2. Admin
        $admin = Role::create([
            'name' => 'admin',
            'description' => 'Administrateur du système',
        ]);
        $admin->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'suspend users',
            'view invitations',
            'create invitations',
            'resend invitations',
            'revoke invitations',
            'delete invitations',
            'view roles',
            'assign roles',
            'view permissions',
            'assign permissions',
            'view content',
            'create content',
            'edit content',
            'delete content',
            'publish content',
            'view activity logs',
        ]);
        $this->command->info('✓ Rôle Admin créé');

        // 3. Manager
        $manager = Role::create([
            'name' => 'manager',
            'description' => 'Gestionnaire de contenu et d\'invitations',
        ]);
        $manager->givePermissionTo([
            'view users',
            'view invitations',
            'create invitations',
            'resend invitations',
            'view content',
            'create content',
            'edit content',
            'delete content',
            'publish content',
        ]);
        $this->command->info('✓ Rôle Manager créé');

        // 4. Editor
        $editor = Role::create([
            'name' => 'editor',
            'description' => 'Éditeur de contenu',
        ]);
        $editor->givePermissionTo([
            'view users',
            'view content',
            'create content',
            'edit content',
        ]);
        $this->command->info('✓ Rôle Editor créé');

        // 5. User
        $user = Role::create([
            'name' => 'user',
            'description' => 'Utilisateur standard',
        ]);
        $user->givePermissionTo([
            'view content',
        ]);
        $this->command->info('✓ Rôle User créé');

        // ========== RÉSUMÉ ==========
        $this->command->newLine();
        $this->command->info('✅ Seeding des permissions et rôles terminé !');
        $this->command->table(
            ['Rôle', 'Permissions'],
            [
                ['Ghost', Permission::count() . ' (toutes)'],
                ['Admin', $admin->permissions->count()],
                ['Manager', $manager->permissions->count()],
                ['Editor', $editor->permissions->count()],
                ['User', $user->permissions->count()],
            ]
        );
    }
}