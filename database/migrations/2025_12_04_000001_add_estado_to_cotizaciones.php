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
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Agregar campo estado si no existe
            if (!Schema::hasColumn('cotizaciones', 'estado')) {
                $table->enum('estado', [
                    'BORRADOR',
                    'ENVIADA_CONTADOR',
                    'APROBADA_CONTADOR',
                    'APROBADA_COTIZACIONES',
                    'CONVERTIDA_PEDIDO',
                    'FINALIZADA'
                ])->default('BORRADOR')->after('es_borrador');
            }

            // Agregar campos de aprobación
            if (!Schema::hasColumn('cotizaciones', 'aprobada_por_contador_en')) {
                $table->timestamp('aprobada_por_contador_en')->nullable()->after('estado');
            }

            if (!Schema::hasColumn('cotizaciones', 'aprobada_por_aprobador_en')) {
                $table->timestamp('aprobada_por_aprobador_en')->nullable()->after('aprobada_por_contador_en');
            }

            // Hacer numero_cotizacion UNIQUE NULLABLE
            if (Schema::hasColumn('cotizaciones', 'numero_cotizacion')) {
                $table->dropUnique('cotizaciones_numero_cotizacion_unique');
            } else {
                $table->unsignedInteger('numero_cotizacion')->nullable()->unique()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('cotizaciones', 'aprobada_por_aprobador_en')) {
                $table->dropColumn('aprobada_por_aprobador_en');
            }
            if (Schema::hasColumn('cotizaciones', 'aprobada_por_contador_en')) {
                $table->dropColumn('aprobada_por_contador_en');
            }
            if (Schema::hasColumn('cotizaciones', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};
