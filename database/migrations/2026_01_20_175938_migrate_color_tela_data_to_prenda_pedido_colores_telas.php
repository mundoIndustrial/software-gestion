<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar datos existentes de prenda_pedido_variantes a prenda_pedido_colores_telas
        $variantes = DB::table('prenda_pedido_variantes')
            ->whereNotNull('color_id')
            ->orWhereNotNull('tela_id')
            ->get();

        foreach ($variantes as $variante) {
            // Verificar si ya existe un registro para esta prenda
            $existe = DB::table('prenda_pedido_colores_telas')
                ->where('prenda_pedido_id', $variante->prenda_pedido_id)
                ->exists();

            if (!$existe && ($variante->color_id || $variante->tela_id)) {
                DB::table('prenda_pedido_colores_telas')->insert([
                    'prenda_pedido_id' => $variante->prenda_pedido_id,
                    'color_id' => $variante->color_id,
                    'tela_id' => $variante->tela_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Limpiar los datos migrados
        DB::table('prenda_pedido_colores_telas')->truncate();
    }
};
