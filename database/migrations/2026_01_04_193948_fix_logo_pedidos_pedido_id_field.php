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
     * Corrige el campo pedido_id en logo_pedidos para que contenga
     * el numero_pedido en lugar del id de pedidos_produccion.
     * Elimina la clave foránea ya que la relación será manejada por Eloquent.
     */
    public function up(): void
    {
        // Paso 1: Eliminar la restricción de clave foránea si existe
        try {
            Schema::table('logo_pedidos', function (Blueprint $table) {
                $table->dropForeign(['pedido_id']);
            });
            \Log::info('🔓 Clave foránea eliminada');
        } catch (\Exception $e) {
            \Log::info('ℹ️ No había clave foránea que eliminar');
        }
        
        // Paso 2: Actualizar todos los registros de logo_pedidos que tengan pedido_id no nulo
        $updated = DB::statement("
            UPDATE logo_pedidos lp
            INNER JOIN pedidos_produccion pp ON lp.pedido_id = pp.id
            SET lp.pedido_id = pp.numero_pedido
            WHERE lp.pedido_id IS NOT NULL
        ");
        
        \Log::info('✅ Campo pedido_id actualizado con numero_pedido', [
            'affected_rows' => $updated
        ]);
        
        // No recreamos la clave foránea porque:
        // - logo_pedidos.pedido_id es bigint
        // - pedidos_produccion.numero_pedido es int
        // - La relación será manejada por Eloquent con belongsTo personalizado
    }

    /**
     * Reverse the migrations.
     * 
     * NOTA: No es posible revertir esta migración porque perdemos
     * la información del ID original. Si necesitas revertir, 
     * tendrás que hacer un restore de backup.
     */
    public function down(): void
    {
        \Log::warning('⚠️ No se puede revertir esta migración automáticamente');
        // No se puede revertir sin backup
    }
};
