<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Helpers\PermissionHelper;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the permission
        $permissionName = PermissionHelper::transform(
            PermissionHelper::ACCESS_MONITORING_BUKTI_TRANSFER,
            PermissionHelper::TYPE_READ
        );

        $permission = Permission::firstOrCreate(['name' => $permissionName]);

        // Assign to Super Admin
        $superAdmin = Role::where('name', User::ROLE_SUPER_ADMIN)->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }

        // Assign to Sales
        $sales = Role::where('name', User::ROLE_SALES)->first();
        if ($sales) {
            $sales->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionName = PermissionHelper::transform(
            PermissionHelper::ACCESS_MONITORING_BUKTI_TRANSFER,
            PermissionHelper::TYPE_READ
        );

        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
