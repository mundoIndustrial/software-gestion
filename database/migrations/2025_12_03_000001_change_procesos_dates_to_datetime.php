<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cambiar campos fecha_inicio y fecha_fin de DATE a DATETIME
     * para preservar la hora correcta en zona horaria
     */
    public function up(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            // Cambiar de DATE a DATETIME para preservar hora
            $table->dateTime('fecha_inicio')->nullable()->change();
            $table->dateTime('fecha_fin')->nullable()->change();
        });
    }

    /**
     * Revertir cambios
     */
    public function down(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            // Revertir a DATE
            $table->date('fecha_inicio')->nullable()->change();
            $table->date('fecha_fin')->nullable()->change();
        });
    }
};
