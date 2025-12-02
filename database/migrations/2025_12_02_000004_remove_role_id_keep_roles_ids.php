<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Elimina role_id y mantiene solo roles_ids (JSON)
     * Todos los roles están en roles_ids
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar la foreign key de role_id
            $table->dropForeign(['role_id']);
            // Eliminar la columna role_id
            $table->dropColumn('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Recrear columna role_id
            $table->foreignId('role_id')->nullable()->after('password')->constrained('roles')->onDelete('set null');
        });
    }
};
