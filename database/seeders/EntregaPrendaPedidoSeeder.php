<?php

namespace Database\Seeders;

use App\Models\PrendaPedido;
use App\Models\EntregaPrendaPedido;
use Illuminate\Database\Seeder;

class EntregaPrendaPedidoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todas las prendas_pedido
        $prendas = PrendaPedido::all();

        foreach ($prendas as $prenda) {
            // Extraer tallas desde cantidad_talla (JSON)
            $cantidadTalla = is_string($prenda->cantidad_talla) 
                ? json_decode($prenda->cantidad_talla, true) 
                : $prenda->cantidad_talla;

            // Si hay tallas, crear un registro por cada talla
            if (is_array($cantidadTalla) && !empty($cantidadTalla)) {
                foreach ($cantidadTalla as $talla => $cantidad) {
                    // Verificar que no exista ya
                    $exists = EntregaPrendaPedido::where('prenda_pedido_id', $prenda->id)
                        ->where('talla', $talla)
                        ->exists();

                    if (!$exists) {
                        EntregaPrendaPedido::create([
                            'prenda_pedido_id' => $prenda->id,
                            'talla' => $talla,
                            'cantidad_original' => $cantidad,
                            'costurero' => null,
                            'total_producido_por_talla' => 0,
                            'total_pendiente_por_talla' => $cantidad,
                            'fecha_completado' => null,
                        ]);
                    }
                }
            }
        }

        $this->command->info('EntregaPrendaPedido seeder completado.');
    }
}
