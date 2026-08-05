<?php

namespace Database\Seeders\User;

use App\Helpers\PermissionHelper;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const hs = [
        PermissionHelper::ACCESS_DASHBOARD => [PermissionHelper::TYPE_READ],
    ];
    const sales = [
        PermissionHelper::ACCESS_DASHBOARD => [PermissionHelper::TYPE_READ],
        PermissionHelper::ACCESS_MONITORING_BUKTI_TRANSFER => [PermissionHelper::TYPE_READ],
    ];
    const client = [
        PermissionHelper::ACCESS_DASHBOARD => [PermissionHelper::TYPE_READ],
        PermissionHelper::ACCESS_CLIENT_FORM_CONFIRMATION => PermissionHelper::TYPE_ALL,
        PermissionHelper::ACCESS_CLIENT_AGREEMENT => [PermissionHelper::TYPE_READ],
        PermissionHelper::ACCESS_CLIENT_DOCUMENT => [PermissionHelper::TYPE_READ],
    ];
    public function run(): void
    {

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        // create role
        $role = Role::create(['name' => User::ROLE_SUPER_ADMIN]);
        foreach (PermissionHelper::ACCESS_TYPE_ALL as $access => $types) {
            foreach ($types as $type) {
                $role->givePermissionTo(PermissionHelper::transform($access, $type));
            }
        }
        $role = Role::create(['name' => User::ROLE_HS]);
        foreach (self::hs as $access => $types) {
            foreach ($types as $type) {
                $role->givePermissionTo(PermissionHelper::transform($access, $type));
            }
        }
        $role = Role::create(['name' => User::ROLE_SALES]);
        foreach (self::sales as $access => $types) {
            foreach ($types as $type) {
                $role->givePermissionTo(PermissionHelper::transform($access, $type));
            }
        }
        $role = Role::create(['name' => User::ROLE_CLIENT]);
        foreach (self::client as $access => $types) {
            foreach ($types as $type) {
                $role->givePermissionTo(PermissionHelper::transform($access, $type));
            }
        }
    }
}
