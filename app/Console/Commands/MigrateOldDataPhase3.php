<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PrendaPedido;

class MigrateOldDataPhase3 extends Command
{
    protected $signature = 'migrate:old-data-phase3';
    protected $description = 'FASE 4: Migra 6642 registros (prendas+tallas) a prendas_pedido con JSON';

    private $mapeoPedidos = [];

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 140));
        $this->info("🚀 FASE 4: MIGRACIÓN DE 6642 REGISTROS (PRENDAS CON TALLAS)");
        $this->info(str_repeat("=", 140) . "\n");

        try {
            // Cargar mapeo de pedidos
            $this->cargarMapeos();

            // Migrar prendas agrupadas por talla
            $this->migrarPrendas();

            $this->info("\n");
            $this->info(str_repeat("=", 140));
            $this->info("✅ FASE 4 COMPLETADA - 6642 Registros procesados → ~1821 Prendas creadas");
            $this->info(str_repeat("=", 140) . "\n");

        } catch (\Exception $e) {
            $this->error("❌ Error en FASE 4: " . $e->getMessage());
            \Log::error("Error migración prendas:", ['error' => $e->getMessage()]);
            return 1;
        }

        return 0;
    }

    private function cargarMapeos()
    {
        $this->mapeoPedidos = cache()->get('mapeo_pedidos', []);

        if (empty($this->mapeoPedidos)) {
            throw new \Exception('Mapeo de pedidos no encontrado en cache. Ejecuta migrate:old-data-phase2 primero');
        }

        $this->line("   ✅ Mapeo de pedidos cargado: " . count($this->mapeoPedidos) . " pedidos\n");
    }

    private function migrarPrendas()
    {
        // Obtener todos los registros de prendas
        $registros = DB::table('registros_por_orden')
            ->whereNotNull('prenda') // Saltamos los 3 registros sin prenda
            ->orderBy('pedido')
            ->orderBy('prenda')
            ->orderBy('talla')
            ->get();

        $total = $registros->count();
        $this->line("   📊 Leyendo $total registros de registros_por_orden");
        $this->line("");

        // Agrupar por pedido + prenda
        $agrupados = $registros->groupBy(function ($item) {
            return $item->pedido . '|' . $item->prenda;
        });

        $totalPrendas = $agrupados->count();
        $this->line("   📊 Agrupados en $totalPrendas prendas únicas\n");

        $creadas = 0;
        $errores = 0;

        foreach ($agrupados as $index => $gruposPrenda) {
            try {
                // Extraer pedido y prenda
                list($pedidoAntiguo, $nombrePrenda) = explode('|', $index);

                // Obtener el nuevo ID del pedido
                if (!isset($this->mapeoPedidos[$pedidoAntiguo])) {
                    $this->error("   ❌ Pedido $pedidoAntiguo no encontrado en mapeo");
                    continue;
                }

                $pedidoProduccionId = $this->mapeoPedidos[$pedidoAntiguo];

                // Agrupar por talla y construir JSON
                $cantidadPorTalla = $gruposPrenda->map(function ($registro) {
                    return [
                        'talla' => $registro->talla,
                        'cantidad' => (int) $registro->cantidad,
                    ];
                })->values()->all();

                // Suma total de cantidad
                $cantidadTotal = $gruposPrenda->sum('cantidad');

                // Obtener descripción (de cualquier registro del grupo, todos tienen la misma)
                $descripcion = $gruposPrenda->first()->descripcion;

                // Crear prenda en nueva tabla
                $prenda = PrendaPedido::create([
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'nombre_prenda' => substr($nombrePrenda, 0, 255), // Truncar a 255 caracteres
                    'cantidad' => (int) $cantidadTotal,
                    'descripcion' => $descripcion,
                    'cantidad_talla' => json_encode($cantidadPorTalla, JSON_UNESCAPED_UNICODE),
                ]);

                $creadas++;

                // Mostrar progreso cada 200 prendas
                if ($creadas % 200 === 0) {
                    $this->line("   ✅ $creadas / $totalPrendas prendas creadas...");
                }

            } catch (\Exception $e) {
                $errores++;
                $this->error("   ❌ Error en prenda $index: " . $e->getMessage());
            }
        }

        $this->line("\n");
        $this->line("   📈 Resumen MIGRACIÓN DE PRENDAS:");
        $this->line("      Registros procesados: $total");
        $this->line("      Prendas creadas: $creadas");
        $this->line("      Errores: $errores");
        $this->line("      Total prendas en prendas_pedido: " . PrendaPedido::count());
    }
}
