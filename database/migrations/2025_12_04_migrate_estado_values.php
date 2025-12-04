<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrar valores antiguos de estado a los nuevos valores del Enum
     */
    public function up(): void
    {
        // Primero, cambiar el campo a VARCHAR para permitir valores más largos
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('estado', 255)->change();
        });

        // Mapeo de valores antiguos a nuevos (case-insensitive)
        $mapping = [
            'borrador' => 'BORRADOR',
            'enviada' => 'ENVIADA_CONTADOR',
            'entregar' => 'APROBADA_COTIZACIONES',
            'anular' => 'FINALIZADA',
            'pendiente_aprobacion' => 'APROBADA_CONTADOR',
        ];

        // Actualizar cada valor (case-insensitive)
        foreach ($mapping as $oldValue => $newValue) {
            DB::table('cotizaciones')
                ->whereRaw('LOWER(estado) = ?', [strtolower($oldValue)])
                ->update(['estado' => $newValue]);
        }
    }

    /**
     * Revertir la migración
     */
    public function down(): void
    {
        // Mapeo inverso para revertir
        $mapping = [
            'BORRADOR' => 'borrador',
            'ENVIADA_CONTADOR' => 'enviada',
            'APROBADA_CONTADOR' => 'pendiente_aprobacion',
            'APROBADA_COTIZACIONES' => 'entregar',
            'CONVERTIDA_PEDIDO' => 'entregar',
            'FINALIZADA' => 'anular',
        ];

        // Revertir cada valor
        foreach ($mapping as $newValue => $oldValue) {
            DB::table('cotizaciones')
                ->where('estado', $newValue)
                ->update(['estado' => $oldValue]);
        }
    }
};
