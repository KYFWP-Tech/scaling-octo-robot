<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    /**
     * @var array<string, list<string>>
     */
    private const PERMISSIONS = [
        'admins' => [
            'admins.index',
            'admins.show',
            'admins.store',
            'admins.update',
            'admins.destroy',
        ],
        'roles' => [
            'roles.index',
            'roles.show',
            'roles.update',
            'roles.destroy',
        ],
        'permissions' => [
            'permissions.index',
            'permissions.show',
            'permissions.update',
            'permissions.destroy',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)
            ->flatten()
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => self::GUARD,
            ]))
            ->keyBy('name');

        $superAdmin = SpatieRole::firstOrCreate([
            'name' => Role::SuperAdmin->value,
            'guard_name' => self::GUARD,
        ]);
        $superAdmin->syncPermissions($permissions->values());

        $admin = SpatieRole::firstOrCreate([
            'name' => Role::Admin->value,
            'guard_name' => self::GUARD,
        ]);
        $admin->syncPermissions($permissions->only([
            'admins.index',
            'admins.show',
            'roles.index',
            'roles.show',
            'roles.update',
            'permissions.index',
            'permissions.show',
            'permissions.update',
        ])->values());

        $editor = SpatieRole::firstOrCreate([
            'name' => Role::Editor->value,
            'guard_name' => self::GUARD,
        ]);
        $editor->syncPermissions($permissions->only([
            'admins.index',
            'admins.show',
            'roles.index',
            'roles.show',
            'permissions.index',
            'permissions.show',
        ])->values());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
