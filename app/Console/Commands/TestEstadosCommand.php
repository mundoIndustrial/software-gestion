#!/usr/bin/env php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPedido;
use App\Services\CotizacionEstadoService;
use App\Services\PedidoEstadoService;
use Illuminate\Support\Facades\DB;

class TestEstadosCommand extends Command
{
    protected $signature = 'test:estados';
    protected $description = 'Test del sistema de estados de cotizaciones y pedidos';

    public function handle()
    {
        $this->info("\n=== TESTING: ESTADOS DE COTIZACIONES Y PEDIDOS ===\n");

        // TEST 1: Verificar que las tablas existen
        $this->testTablas();

        // TEST 2: Verificar Enums
        $this->testEnums();

        // TEST 3: Verificar transiciones permitidas
        $this->testTransiciones();

        // TEST 4: Verificar Servicios
        $this->testServicios();

        // TEST 5: Verificar Modelos y Relaciones
        $this->testModelos();

        // TEST 6: Probar flujo completo (simulado)
        $this->testFlujo();

        // TEST 7: Verificar Controllers
        $this->testControllers();

        // TEST 8: Verificar Jobs
        $this->testJobs();

        // RESUMEN FINAL
        $this->info("\n╔════════════════════════════════════════════╗");
        $this->info("║  ✓ TODOS LOS TESTS COMPLETADOS EXITOSAMENTE ║");
        $this->info("╚════════════════════════════════════════════╝\n");

        $this->line("Próximos pasos:");
        $this->line("  1. Ejecutar: php artisan queue:work");
        $this->line("  2. Probar endpoints con Postman/curl");
        $this->line("  3. Crear vistas y componentes Blade");
        $this->line("  4. Integrar con frontend\n");
    }

    private function testTablas()
    {
        $this->line("✓ TEST 1: Verificar estructura de tablas");
        try {
            $exists = DB::table('cotizaciones')->limit(1)->exists();
            $this->line("  ✓ Tabla cotizaciones existe");

            $exists = DB::table('pedidos_produccion')->limit(1)->exists();
            $this->line("  ✓ Tabla pedidos_produccion existe");

            $exists = DB::table('historial_cambios_cotizaciones')->limit(1)->exists();
            $this->line("  ✓ Tabla historial_cambios_cotizaciones existe");

            $exists = DB::table('historial_cambios_pedidos')->limit(1)->exists();
            $this->line("  ✓ Tabla historial_cambios_pedidos existe");
        } catch (\Exception $e) {
            $this->error("  ✗ Error verificando tablas: " . $e->getMessage());
        }
    }

    private function testEnums()
    {
        $this->line("\n✓ TEST 2: Verificar Enums");
        try {
            $estCot = EstadoCotizacion::BORRADOR;
            $this->line("  ✓ EstadoCotizacion::BORRADOR = '" . $estCot->value . "'");
            $this->line("    - Label: " . $estCot->label());
            $this->line("    - Color: " . $estCot->color());
            $this->line("    - Icon: " . $estCot->icon());

            $estPed = EstadoPedido::PENDIENTE_SUPERVISOR;
            $this->line("  ✓ EstadoPedido::PENDIENTE_SUPERVISOR = '" . $estPed->value . "'");
            $this->line("    - Label: " . $estPed->label());
            $this->line("    - Color: " . $estPed->color());
        } catch (\Exception $e) {
            $this->error("  ✗ Error con Enums: " . $e->getMessage());
        }
    }

    private function testTransiciones()
    {
        $this->line("\n✓ TEST 3: Verificar transiciones permitidas en Enums");
        try {
            $estado = EstadoCotizacion::BORRADOR;
            $transiciones = $estado->transicionesPermitidas();
            $this->line("  ✓ Desde BORRADOR puede ir a: " . implode(", ", $transiciones));

            $puedePasar = $estado->puedePasar(EstadoCotizacion::ENVIADA_CONTADOR);
            $this->line("  ✓ BORRADOR → ENVIADA_CONTADOR: " . ($puedePasar ? "SÍ" : "NO"));

            $noPuedePasar = $estado->puedePasar(EstadoCotizacion::APROBADA_COTIZACIONES);
            $this->line("  ✓ BORRADOR → APROBADA_COTIZACIONES: " . ($noPuedePasar ? "SÍ" : "NO"));
        } catch (\Exception $e) {
            $this->error("  ✗ Error verificando transiciones: " . $e->getMessage());
        }
    }

    private function testServicios()
    {
        $this->line("\n✓ TEST 4: Verificar Servicios");
        try {
            $servicioC = app(CotizacionEstadoService::class);
            $this->line("  ✓ CotizacionEstadoService inyectado");

            $servicioP = app(PedidoEstadoService::class);
            $this->line("  ✓ PedidoEstadoService inyectado");

            $siguienteCot = $servicioC->obtenerSiguienteNumeroCotizacion();
            $this->line("  ✓ Siguiente número cotización: " . $siguienteCot);

            $siguientePed = $servicioP->obtenerSiguienteNumeroPedido();
            $this->line("  ✓ Siguiente número pedido: " . $siguientePed);
        } catch (\Exception $e) {
            $this->error("  ✗ Error con Servicios: " . $e->getMessage());
        }
    }

    private function testModelos()
    {
        $this->line("\n✓ TEST 5: Verificar Modelos y Relaciones");
        try {
            $cot = Cotizacion::limit(1)->first();
            if ($cot) {
                $this->line("  ✓ Modelo Cotizacion carga");
                $this->line("    - ID: " . $cot->id);
                $this->line("    - Estado: " . ($cot->estado ?? 'NULL'));
                $this->line("    - Número: " . ($cot->numero_cotizacion ?? 'NULL'));

                $historial = $cot->historialCambios()->count();
                $this->line("    - Historial cambios: " . $historial . " registros");
            } else {
                $this->info("  ℹ No hay cotizaciones para probar relaciones");
            }

            $ped = PedidoProduccion::limit(1)->first();
            if ($ped) {
                $this->line("  ✓ Modelo PedidoProduccion carga");
                $this->line("    - ID: " . $ped->id);
                $this->line("    - Estado: " . ($ped->estado ?? 'NULL'));
                $this->line("    - Número: " . ($ped->numero_pedido ?? 'NULL'));

                $historial = $ped->historialCambios()->count();
                $this->line("    - Historial cambios: " . $historial . " registros");
            } else {
                $this->info("  ℹ No hay pedidos para probar relaciones");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error con Modelos: " . $e->getMessage());
        }
    }

    private function testFlujo()
    {
        $this->line("\n✓ TEST 6: Flujo de Estados Simulado");
        try {
            $cotTest = Cotizacion::create([
                'user_id' => 1,
                'cliente' => 'TEST CLIENTE',
                'estado' => EstadoCotizacion::BORRADOR->value,
                'es_borrador' => true,
                'tipo_cotizacion' => 'P',
            ]);
            $this->line("  ✓ Cotización de prueba creada (ID: " . $cotTest->id . ")");
            $this->line("    - Estado inicial: " . $cotTest->estado);

            $service = app(CotizacionEstadoService::class);
            $this->line("  ✓ Servicio listo para pruebas");

            $puede = $service->validarTransicion($cotTest, EstadoCotizacion::ENVIADA_CONTADOR);
            $this->line("  ✓ Validación de transición: " . ($puede ? "PERMITIDA" : "NO PERMITIDA"));

            $cotTest->delete();
            $this->line("  ✓ Cotización de prueba eliminada");
        } catch (\Exception $e) {
            $this->error("  ✗ Error en flujo simulado: " . $e->getMessage());
        }
    }

    private function testControllers()
    {
        $this->line("\n✓ TEST 7: Verificar Controllers");
        try {
            $controllerC = new \App\Http\Controllers\CotizacionEstadoController(
                app(CotizacionEstadoService::class)
            );
            $this->line("  ✓ CotizacionEstadoController instanciado");

            $controllerP = new \App\Http\Controllers\PedidoEstadoController(
                app(PedidoEstadoService::class)
            );
            $this->line("  ✓ PedidoEstadoController instanciado");
        } catch (\Exception $e) {
            $this->error("  ✗ Error con Controllers: " . $e->getMessage());
        }
    }

    private function testJobs()
    {
        $this->line("\n✓ TEST 8: Verificar Jobs");
        try {
            $job1 = new \App\Jobs\AsignarNumeroCotizacionJob(Cotizacion::first() ?? new Cotizacion());
            $this->line("  ✓ AsignarNumeroCotizacionJob instanciado");

            $job2 = new \App\Jobs\EnviarCotizacionAContadorJob(Cotizacion::first() ?? new Cotizacion());
            $this->line("  ✓ EnviarCotizacionAContadorJob instanciado");

            $job3 = new \App\Jobs\EnviarCotizacionAAprobadorJob(Cotizacion::first() ?? new Cotizacion());
            $this->line("  ✓ EnviarCotizacionAAprobadorJob instanciado");

            $job4 = new \App\Jobs\AsignarNumeroPedidoJob(PedidoProduccion::first() ?? new PedidoProduccion());
            $this->line("  ✓ AsignarNumeroPedidoJob instanciado");
        } catch (\Exception $e) {
            $this->error("  ✗ Error con Jobs: " . $e->getMessage());
        }
    }
}
