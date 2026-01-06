<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ejecutar comandos SQL directos para eliminar las tablas
        DB::statement('DROP TABLE IF EXISTS logo_cotizacion_tecnica_prendas');
        DB::statement('DROP TABLE IF EXISTS logo_cotizacion_tecnicas');
        DB::statement('DROP TABLE IF EXISTS tipo_logo_cotizaciones');
        
        echo "✅ Tablas eliminadas correctamente\n";
    }

    public function down(): void
    {
        // No-op
    }
};

