<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Роль menu.lab_storage (справочник мест хранения) + выдача её всем пользователям,
 * у которых уже есть право на пробы (menu.lab_samples) — с теми же правами.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'menu.lab_storage')->value('id');
        if (!$roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'menu.lab_storage',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $samplesRoleId = DB::table('roles')->where('name', 'menu.lab_samples')->value('id');
        if (!$samplesRoleId) {
            return;
        }

        $existing = DB::table('user_roles')->where('role_id', $roleId)->pluck('user_id')->all();

        $grants = DB::table('user_roles')->where('role_id', $samplesRoleId)->get();
        foreach ($grants as $g) {
            if (in_array($g->user_id, $existing)) {
                continue;
            }
            DB::table('user_roles')->insert([
                'user_id' => $g->user_id,
                'role_id' => $roleId,
                'view' => $g->view,
                'create' => $g->create,
                'update' => $g->update,
                'delete' => $g->delete,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'menu.lab_storage')->value('id');
        if ($roleId) {
            DB::table('user_roles')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
