<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;

class TestCrearPedidoReflectivo extends Command
{
    protected $signature = 'test:crear-pedido-reflectivo';
    protected $description = 'Test: Simular creación de pedido desde cotización reflectivo';

    public function handle()
    {
        $this->info('🧪 TEST: CREAR PEDIDO DESDE COTIZACIÓN REFLECTIVO');
        $this->line(str_repeat('=', 70));

        // Buscar una cotización reflectivo aprobada
        $cotizacion = Cotizacion::whereHas('tipoCotizacion', function ($q) {
            $q->where('nombre', 'Reflectivo');
        })
        ->where('estado', 'APROBADA_COTIZACIONES')
        ->with('tipoCotizacion', 'cliente')
        ->first();

        if (!$cotizacion) {
            $this->error(' No hay cotizaciones REFLECTIVO aprobadas en la BD');
            return 1;
        }

        $this->info("\n Cotización encontrada:");
        $this->line("   ID: {$cotizacion->id}");
        $this->line("   Número: {$cotizacion->numero_cotizacion}");
        $this->line("   Tipo: {$cotizacion->tipoCotizacion->nombre}");
        $this->line("   Cliente: {$cotizacion->cliente->nombre}");

        // Simular datos que enviaría el frontend
        $prendas = [];
        if ($cotizacion->productos) {
            $productos = is_string($cotizacion->productos) ? json_decode($cotizacion->productos, true) : $cotizacion->productos;
            
            foreach ($productos as $index => $producto) {
                $prendas[] = [
                    'index' => $index,
                    'nombre_producto' => $producto['nombre_producto'] ?? 'Prenda ' . ($index + 1),
                    'cantidades' => ['M' => 100], // Cantidad simple
                    'color_id' => $producto['color_id'] ?? null,
                    'tela_id' => $producto['tela_id'] ?? null,
                ];
            }
        }

        if (empty($prendas)) {
            $this->warn('⚠️ No hay prendas en la cotización');
            return 1;
        }

        $this->info("\n Prendas a procesar: " . count($prendas));

        // Simular la solicitud
        $this->line("\n▶️ Simulando creación de pedido...");

        try {
            \DB::beginTransaction();

            // Crear pedido
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'numero_pedido' => 'PEP-' . str_pad(rand(1000, 9999), 6, '0', STR_PAD_LEFT),
                'cliente' => $cotizacion->cliente->nombre,
                'asesor_id' => auth()->id() ?? 1,
                'estado' => 'Pendiente',
                'fecha_de_creacion_de_orden' => now(),
            ]);

            $this->line(" Pedido creado: {$pedido->numero_pedido}");

            // Crear prendas y guardar sus IDs
            $prendasGuardadas = [];
            foreach ($prendas as $prenda) {
                $prendaPedido = PrendaPedido::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'nombre_prenda' => $prenda['nombre_producto'],
                    'cantidad' => 100,
                    'descripcion' => 'Descripción test',
                    'cantidad_talla' => json_encode($prenda['cantidades']),
                ]);
                
                $prendasGuardadas[] = $prendaPedido;
                $this->line(" Prenda creada: {$prenda['nombre_producto']}");
            }

            // Crear proceso inicial para cada prenda
            foreach ($prendasGuardadas as $prendaPedido) {
                ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prendaPedido->id,
                    'proceso' => 'Creación Orden',
                    'estado_proceso' => 'Completado',
                    'fecha_inicio' => now(),
                    'fecha_fin' => now(),
                ]);
            }

            \DB::commit();
            
            $this->info("\n Pedido guardado en BD");

            // Verificar procesos creados
            $this->line("\n🔍 VERIFICANDO PROCESOS CREADOS:");
            
            $procesos = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->distinct('proceso')
                ->get(['proceso', 'encargado', 'estado_proceso']);

            if ($procesos->isEmpty()) {
                $this->error(" No hay procesos (¿El listener se ejecutó?)");
            } else {
                foreach ($procesos as $p) {
                    $encargado = $p->encargado ? " ✓ {$p->encargado}" : " (Sin asignar)";
                    $this->line("    {$p->proceso}:{$encargado} [{$p->estado_proceso}]");
                }
            }

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->error(' Error: ' . $e->getMessage());
            return 1;
        }

        $this->line("\n Test completado");
        return 0;
    }
}
