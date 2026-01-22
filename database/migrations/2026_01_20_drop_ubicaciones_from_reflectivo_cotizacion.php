<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar campo ubicaciones de reflectivo_cotizacion
        if (Schema::hasTable('reflectivo_cotizacion') && Schema::hasColumn('reflectivo_cotizacion', 'ubicaciones')) {
            Schema::table('reflectivo_cotizacion', function (Blueprint $table) {
                $table->dropColumn('ubicaciones');
            });
            
            \Log::info(' [Migración] Campo ubicaciones eliminado de reflectivo_cotizacion');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reflectivo_cotizacion') && !Schema::hasColumn('reflectivo_cotizacion', 'ubicaciones')) {
            Schema::table('reflectivo_cotizacion', function (Blueprint $table) {
                $table->json('ubicaciones')->nullable()->after('ubicacion');
            });
        }
    }
};
