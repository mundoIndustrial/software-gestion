<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Normalizar tabla cotizaciones:
     * - Remover campos que ahora están en prendas_cotizaciones
     * - Remover campos que ahora están en logo_cotizaciones
     * - Mantener solo datos de cabeza de cotización
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Remover campos que están en prendas_cotizaciones
            if (Schema::hasColumn('cotizaciones', 'productos')) {
                $table->dropColumn('productos');
            }
            
            // Remover campos que están en logo_cotizaciones
            if (Schema::hasColumn('cotizaciones', 'imagenes')) {
                $table->dropColumn('imagenes');
            }
            if (Schema::hasColumn('cotizaciones', 'tecnicas')) {
                $table->dropColumn('tecnicas');
            }
            if (Schema::hasColumn('cotizaciones', 'observaciones_tecnicas')) {
                $table->dropColumn('observaciones_tecnicas');
            }
            if (Schema::hasColumn('cotizaciones', 'ubicaciones')) {
                $table->dropColumn('ubicaciones');
            }
            if (Schema::hasColumn('cotizaciones', 'observaciones_generales')) {
                $table->dropColumn('observaciones_generales');
            }
            
            // Remover campo especificaciones (ya que está en prendas_cotizaciones)
            if (Schema::hasColumn('cotizaciones', 'especificaciones')) {
                $table->dropColumn('especificaciones');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Restaurar campos removidos
            $table->json('productos')->nullable()->after('cotizar_segun_indicaciones');
            $table->json('especificaciones')->nullable()->after('productos');
            $table->json('imagenes')->nullable()->after('especificaciones');
            $table->json('tecnicas')->nullable()->after('imagenes');
            $table->text('observaciones_tecnicas')->nullable()->after('tecnicas');
            $table->json('ubicaciones')->nullable()->after('observaciones_tecnicas');
            $table->json('observaciones_generales')->nullable()->after('ubicaciones');
        });
    }
};
