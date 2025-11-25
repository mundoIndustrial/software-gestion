<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migra datos: 'asesora' (nombre) → 'asesor_id' (ID)
     * Busca el usuario por nombre y asigna su ID
     */
    public function up(): void
    {
        // Obtener todos los pedidos con nombre de asesor
        $pedidos = DB::table('pedidos_produccion')
            ->whereNotNull('asesora')
            ->get();

        foreach ($pedidos as $pedido) {
            // Buscar el usuario por nombre
            $usuario = DB::table('users')
                ->where('name', $pedido->asesora)
                ->first();

            if ($usuario) {
                // Actualizar con el ID del usuario
                DB::table('pedidos_produccion')
                    ->where('id', $pedido->id)
                    ->update(['asesor_id' => $usuario->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir: limpiar asesor_id
        DB::table('pedidos_produccion')->update(['asesor_id' => null]);
    }
};
