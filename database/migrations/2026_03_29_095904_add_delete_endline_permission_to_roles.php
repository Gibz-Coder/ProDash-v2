<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION = 'Delete Endline';
    private const ROLES = ['admin', 'manager', 'moderator'];

    public function up(): void
    {
        foreach (self::ROLES as $slug) {
            $role = DB::table('roles')->where('slug', $slug)->first();
            if (! $role) {
                continue;
            }

            $permissions = json_decode($role->permissions, true) ?? [];

            if (! in_array(self::PERMISSION, $permissions, true)) {
                $permissions[] = self::PERMISSION;
                DB::table('roles')->where('slug', $slug)->update([
                    'permissions' => json_encode($permissions),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::ROLES as $slug) {
            $role = DB::table('roles')->where('slug', $slug)->first();
            if (! $role) {
                continue;
            }

            $permissions = array_values(array_filter(
                json_decode($role->permissions, true) ?? [],
                fn (string $p) => $p !== self::PERMISSION,
            ));

            DB::table('roles')->where('slug', $slug)->update([
                'permissions' => json_encode($permissions),
            ]);
        }
    }
};
