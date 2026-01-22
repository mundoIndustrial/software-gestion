<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrar tallas desde JSON legacy (tallas_dama, tallas_caballero) 
     * a la tabla relacional pedidos_procesos_prenda_tallas
     */
    public function up(): void
    {
        // Obtener todos los procesos que tienen tallas en JSON
        $procesos = DB::table('pedidos_procesos_prenda_detalles')
            ->whereNotNull('tallas_dama')
            ->orWhereNotNull('tallas_caballero')
            ->get();

        foreach ($procesos as $proceso) {
            // Migrar tallas_dama
            if ($proceso->tallas_dama) {
                $tallasDama = is_string($proceso->tallas_dama) 
                    ? json_decode($proceso->tallas_dama, true) 
                    : $proceso->tallas_dama;

                if (is_array($tallasDama)) {
                    foreach ($tallasDama as $talla => $cantidad) {
                        $cantidad = (int)$cantidad;
                        if ($cantidad > 0) {
                            DB::table('pedidos_procesos_prenda_tallas')->insertOrIgnore([
                                'proceso_prenda_detalle_id' => $proceso->id,
                                'genero' => 'DAMA',
                                'talla' => (string)$talla,
                                'cantidad' => $cantidad,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            // Migrar tallas_caballero
            if ($proceso->tallas_caballero) {
                $tallasCalballero = is_string($proceso->tallas_caballero) 
                    ? json_decode($proceso->tallas_caballero, true) 
                    : $proceso->tallas_caballero;

                if (is_array($tallasCalballero)) {
                    foreach ($tallasCalballero as $talla => $cantidad) {
                        $cantidad = (int)$cantidad;
                        if ($cantidad > 0) {
                            DB::table('pedidos_procesos_prenda_tallas')->insertOrIgnore([
                                'proceso_prenda_detalle_id' => $proceso->id,
                                'genero' => 'CABALLERO',
                                'talla' => (string)$talla,
                                'cantidad' => $cantidad,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en reverso - los datos en tabla relacional se mantienen
        // Los campos JSON legacy permanecen sin cambios para compatibilidad
    }
};
