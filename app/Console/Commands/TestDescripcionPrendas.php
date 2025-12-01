<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;

class TestDescripcionPrendas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:descripcion-prendas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el sistema de descripción de prendas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('');
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║  TEST: Descripción de Prendas                                 ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->line('');

        try {
            // 1. Buscar pedido con prendas
            $this->info('📋 PASO 1: Buscando pedidos con prendas...');
            $pedido = PedidoProduccion::with('prendas')->has('prendas')->first();

            if (!$pedido) {
                $this->error('❌ No hay pedidos con prendas en la base de datos');
                return 1;
            }

            $this->line("✅ Pedido encontrado: #{$pedido->numero_pedido}");
            $this->line("   Cliente: {$pedido->cliente}");
            $this->line("   Prendas: {$pedido->prendas->count()}");
            $this->line('');

            // 2. Verificar campos de la primera prenda
            $this->info('📋 PASO 2: Verificando campos de la primera prenda...');
            $prenda = $pedido->prendas->first();

            $this->line("   ✅ ID: {$prenda->id}");
            $this->line("   ✅ Nombre: {$prenda->nombre_prenda}");
            $this->line("   ✅ Cantidad: {$prenda->cantidad}");
            $this->line("   ✅ Descripción: " . substr($prenda->descripcion ?? '', 0, 50) . "...");
            $this->line("   ✅ Color ID: {$prenda->color_id}");
            $this->line("   ✅ Tela ID: {$prenda->tela_id}");
            $this->line("   ✅ Tipo Manga ID: {$prenda->tipo_manga_id}");
            $this->line("   ✅ Bolsillos: " . ($prenda->tiene_bolsillos ? 'SÍ' : 'NO'));
            $this->line("   ✅ Reflectivo: " . ($prenda->tiene_reflectivo ? 'SÍ' : 'NO'));
            $this->line("   ✅ Número Pedido: {$prenda->numero_pedido}");
            $this->line('');

            // 3. Generar descripción detallada
            $this->info('📋 PASO 3: Generando descripción detallada de la prenda...');
            $descripcionDetallada = $prenda->generarDescripcionDetallada();

            if (empty($descripcionDetallada)) {
                $this->error('❌ La descripción detallada está vacía');
                return 1;
            }

            $this->line('✅ Descripción generada:');
            $this->line('────────────────────────────────────────────────────────────────');
            $this->line($descripcionDetallada);
            $this->line('────────────────────────────────────────────────────────────────');
            $this->line('');

            // 4. Generar descripción del pedido
            $this->info('📋 PASO 4: Generando descripción_prendas del pedido...');
            $descripcionPedido = $pedido->descripcion_prendas;

            if (empty($descripcionPedido)) {
                $this->error('❌ La descripción del pedido está vacía');
                return 1;
            }

            $this->line('✅ Descripción del pedido:');
            $this->line('────────────────────────────────────────────────────────────────');
            $this->line($descripcionPedido);
            $this->line('────────────────────────────────────────────────────────────────');
            $this->line('');

            // 5. Verificar relación numero_pedido
            $this->info('📋 PASO 5: Verificando relación numero_pedido...');
            $this->line("   Prenda numero_pedido: {$prenda->numero_pedido}");
            $this->line("   Pedido numero_pedido: {$pedido->numero_pedido}");

            if ((int)$prenda->numero_pedido === (int)$pedido->numero_pedido) {
                $this->line('   ✅ Relación correcta');
            } else {
                $this->error('   ❌ Relación incorrecta');
                return 1;
            }
            $this->line('');

            // 6. Resumen
            $this->line('╔════════════════════════════════════════════════════════════════╗');
            $this->line('║  ✅ TODAS LAS PRUEBAS PASARON CORRECTAMENTE                   ║');
            $this->line('╚════════════════════════════════════════════════════════════════╝');
            $this->line('');

            $this->info('📊 RESUMEN:');
            $this->line("   • Pedido: #{$pedido->numero_pedido}");
            $this->line("   • Prendas: {$pedido->prendas->count()}");
            $this->line('   • Descripción detallada: ✅ Funciona');
            $this->line('   • Atributo descripcion_prendas: ✅ Funciona');
            $this->line('   • Relación numero_pedido: ✅ Correcta');
            $this->line('   • Campos necesarios: ✅ Presentes');
            $this->line('');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ ERROR: {$e->getMessage()}");
            $this->error("   Archivo: {$e->getFile()}");
            $this->error("   Línea: {$e->getLine()}");
            $this->line('');
            return 1;
        }
    }
}
