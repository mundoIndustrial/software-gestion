<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;

class MigrateOldDataPhase2 extends Command
{
    protected $signature = 'migrate:old-data-phase2';
    protected $description = 'FASE 3: Migra 2256 pedidos de tabla_original a pedidos_produccion';

    private $mapeoAsesoras = [];
    private $mapeoClientes = [];
    private $mapeoPedidos = []; // tabla_original.pedido → pedidos_produccion.id

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 140));
        $this->info("🚀 FASE 3: MIGRACIÓN DE 2256 PEDIDOS");
        $this->info(str_repeat("=", 140) . "\n");

        try {
            // Cargar mapeos
            $this->cargarMapeos();

            // Migrar pedidos
            $this->migrarPedidos();

            // Guardar mapeo de pedidos para fase 4
            cache()->put('mapeo_pedidos', $this->mapeoPedidos, now()->addHours(24));

            $this->info("\n");
            $this->info(str_repeat("=", 140));
            $this->info("✅ FASE 3 COMPLETADA - 2256 Pedidos migrados");
            $this->info(str_repeat("=", 140) . "\n");

        } catch (\Exception $e) {
            $this->error("❌ Error en FASE 3: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function cargarMapeos()
    {
        $this->mapeoAsesoras = cache()->get('mapeo_asesoras', []);
        $this->mapeoClientes = cache()->get('mapeo_clientes', []);

        if (empty($this->mapeoAsesoras)) {
            throw new \Exception('Mapeo de asesoras no encontrado en cache. Ejecuta migrate:old-data primero');
        }

        if (empty($this->mapeoClientes)) {
            throw new \Exception('Mapeo de clientes no encontrado en cache. Ejecuta migrate:old-data primero');
        }

        $this->line("   ✅ Mapeos cargados del cache");
        $this->line("      - Asesoras: " . count($this->mapeoAsesoras));
        $this->line("      - Clientes: " . count($this->mapeoClientes) . "\n");
    }

    private function migrarPedidos()
    {
        $pedidos = DB::table('tabla_original')
            ->orderBy('pedido')
            ->get();

        $total = $pedidos->count();
        $this->line("   📊 Leyendo $total pedidos de tabla_original");
        $this->line("");

        $migrados = 0;
        $errores = 0;
        $skipped = 0;

        foreach ($pedidos as $index => $pedidoAntiguo) {
            try {
                // Obtener IDs de usuario y cliente
                $asesorId = $this->obtenerAsesorId($pedidoAntiguo->asesora);
                $clienteId = $this->obtenerClienteId($pedidoAntiguo->cliente);

                // Crear pedido en nueva tabla
                $pedidoNuevo = PedidoProduccion::create([
                    'numero_pedido' => $pedidoAntiguo->pedido,
                    'asesor_id' => $asesorId,
                    'cliente_id' => $clienteId,
                    'cliente' => $pedidoAntiguo->cliente, // Campo legacy para compatibilidad
                    'descripcion' => $pedidoAntiguo->descripcion,
                    'cantidad' => $pedidoAntiguo->cantidad,
                    'novedades' => $pedidoAntiguo->novedades, // Migrar novedades
                    'forma_de_pago' => $pedidoAntiguo->forma_de_pago, // Migrar forma de pago
                    'estado' => $this->mapearEstado($pedidoAntiguo->estado),
                    'fecha_de_creacion_de_orden' => $pedidoAntiguo->fecha_de_creacion_de_orden,
                ]);

                // Guardar mapeo para fase 4
                $this->mapeoPedidos[$pedidoAntiguo->pedido] = $pedidoNuevo->id;

                $migrados++;

                // Mostrar progreso cada 100 registros
                if ($migrados % 100 === 0) {
                    $this->line("   ✅ $migrados / $total pedidos migrados...");
                }

            } catch (\Exception $e) {
                $errores++;
                $this->error("   ❌ Error en pedido {$pedidoAntiguo->pedido}: " . $e->getMessage());
            }
        }

        $this->line("\n");
        $this->line("   📈 Resumen MIGRACIÓN DE PEDIDOS:");
        $this->line("      Pedidos migrados exitosamente: $migrados");
        $this->line("      Errores: $errores");
        $this->line("      Pedidos skipped: $skipped");
        $this->line("      Total pedidos en pedidos_produccion: " . PedidoProduccion::count());
        $this->line("      Mapeo guardado en cache: " . count($this->mapeoPedidos) . " pedidos");
    }

    private function obtenerAsesorId($nombreAsesora)
    {
        // Si es NULL, usar el ID del usuario especial SIN_ASESORA
        if ($nombreAsesora === null) {
            return $this->mapeoAsesoras[null] ?? null;
        }

        return $this->mapeoAsesoras[$nombreAsesora] ?? null;
    }

    private function obtenerClienteId($nombreCliente)
    {
        // Si es NULL, usar el ID del cliente especial SIN_CLIENTE
        if ($nombreCliente === null) {
            return $this->mapeoClientes[null] ?? null;
        }

        return $this->mapeoClientes[$nombreCliente] ?? null;
    }

    private function mapearEstado($estadoAntiguo)
    {
        // Mapear estados antiguos a nuevos si es necesario
        $mapaEstados = [
            'COMPLETADO' => 'completado',
            'EN PRODUCCION' => 'en_produccion',
            'CANCELADO' => 'cancelado',
            'PENDIENTE' => 'pendiente',
        ];

        return $mapaEstados[$estadoAntiguo] ?? $estadoAntiguo;
    }
}
