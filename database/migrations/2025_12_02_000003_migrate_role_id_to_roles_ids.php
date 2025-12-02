<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migra los datos de role_id a roles_ids (JSON)
     * Mantiene la relación con la tabla roles
     */
    public function up(): void
    {
        // Migrar datos existentes de role_id a roles_ids
        DB::table('users')->whereNotNull('role_id')->orderBy('id')->each(function ($user) {
            // Si el usuario tiene role_id, agregarlo a roles_ids
            $rolesIds = [];
            if ($user->role_id) {
                $rolesIds[] = (int) $user->role_id;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['roles_ids' => json_encode($rolesIds)]);
        });

        // Usuarios sin role_id quedan con roles_ids = []
        DB::table('users')
            ->whereNull('role_id')
            ->whereNull('roles_ids')
            ->update(['roles_ids' => json_encode([])]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir: copiar roles_ids de vuelta a role_id
        DB::table('users')->each(function ($user) {
            $rolesIds = json_decode($user->roles_ids, true) ?? [];

            // Tomar el primer rol como role_id
            $roleId = !empty($rolesIds) ? $rolesIds[0] : null;

            DB::table('users')
                ->where('id', $user->id)
                ->update(['role_id' => $roleId]);
        });
    }
};
