<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cambiar la restricción UNIQUE para permitir múltiples consecutivos del mismo tipo
        // pero en diferentes prendas dentro del mismo pedido
        
        // Primero, obtener el nombre exacto de la restricción
        $constraintName = null;
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = 'consecutivos_recibos_pedidos' 
            AND TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_TYPE = 'UNIQUE'
        ");

        // Encontrar la restricción que incluye pedido_id y tipo_recibo
        foreach ($constraints as $constraint) {
            if ($constraint->CONSTRAINT_NAME !== 'PRIMARY') {
                $constraintName = $constraint->CONSTRAINT_NAME;
                break;
            }
        }

        // Eliminar la restricción antigua
        if ($constraintName) {
            DB::statement("ALTER TABLE consecutivos_recibos_pedidos DROP INDEX `{$constraintName}`");
        }

        // Crear nueva restricción que incluya prenda_id
        // Permite múltiples consecutivos del mismo tipo siempre que sean de diferentes prendas
        DB::statement("
            ALTER TABLE consecutivos_recibos_pedidos 
            ADD UNIQUE KEY unique_pedido_tipo_prenda (
                pedido_produccion_id, 
                tipo_recibo, 
                prenda_id
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la nueva restricción
        DB::statement("ALTER TABLE consecutivos_recibos_pedidos DROP INDEX `unique_pedido_tipo_prenda`");

        // Restaurar la restricción antigua (solo pedido + tipo)
        DB::statement("
            ALTER TABLE consecutivos_recibos_pedidos 
            ADD UNIQUE KEY unique_pedido_tipo (
                pedido_produccion_id, 
                tipo_recibo
            )
        ");
    }
};
