<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migra todos los registros existentes de consecutivos_recibos_pedidos con marcar_plooter = 1
     * a la tabla plooter
     */
    public function up(): void
    {
        // Obtener todos los recibos marcados con plooter
        $recibossMarcados = DB::table('consecutivos_recibos_pedidos')
            ->where('marcar_plooter', 1)
            ->select('id', 'created_at', 'updated_at')
            ->get();

        // Crear registros en tabla plooter para cada recibo marcado
        foreach ($recibossMarcados as $recibo) {
            DB::table('plooter')->insertOrIgnore([
                'consecutivo_recibo_pedido_id' => $recibo->id,
                'fecha_envio' => null,
                'fecha_llegada' => null,
                'notas' => null,
                'created_at' => $recibo->created_at,
                'updated_at' => $recibo->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar todos los registros que fueron creados en la migración
        DB::table('plooter')->truncate();
    }
};
