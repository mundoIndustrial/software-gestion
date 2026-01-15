<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            // Agregar columna id_rol como clave foránea
            $table->unsignedBigInteger('id_rol')->nullable()->after('nombre_persona');
            
            // Crear relación con la tabla roles
            $table->foreign('id_rol')
                ->references('id')
                ->on('roles')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            // Eliminar la clave foránea
            $table->dropForeign(['id_rol']);
            // Eliminar la columna
            $table->dropColumn('id_rol');
        });
    }
};
