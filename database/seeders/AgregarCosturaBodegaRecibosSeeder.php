<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

class AgregarCosturaBodegaRecibosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los pedidos
        $pedidos = PedidoProduccion::all();

        \Log::info('[AgregarCosturaBodegaRecibosSeeder] Iniciando seeder', [
            'total_pedidos' => $pedidos->count()
        ]);

        $agregados = 0;
        $yaExisten = 0;

        foreach ($pedidos as $pedido) {
            // Verificar si ya tiene COSTURA-BODEGA
            $existeCosturaBodega = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedido->id)
                ->where('tipo_recibo', 'COSTURA-BODEGA')
                ->exists();

            if (!$existeCosturaBodega) {
                // Crear registro de COSTURA-BODEGA
                DB::table('consecutivos_recibos_pedidos')->insert([
                    'pedido_produccion_id' => $pedido->id,
                    'prenda_id' => null,
                    'tipo_recibo' => 'COSTURA-BODEGA',
                    'consecutivo_actual' => 0,
                    'consecutivo_inicial' => 0,
                    'activo' => 1,
                    'notas' => 'Recibo de COSTURA-BODEGA para bodeguero',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $agregados++;

                \Log::info('[AgregarCosturaBodegaRecibosSeeder] COSTURA-BODEGA agregado', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente
                ]);
            } else {
                $yaExisten++;

                \Log::debug('[AgregarCosturaBodegaRecibosSeeder] COSTURA-BODEGA ya existe', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido
                ]);
            }
        }

        \Log::info('[AgregarCosturaBodegaRecibosSeeder] ✅ Seeder completado', [
            'total_pedidos' => $pedidos->count(),
            'agregados' => $agregados,
            'ya_existentes' => $yaExisten
        ]);
    }
}
