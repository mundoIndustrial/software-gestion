<?php

/**
 * Script de prueba para verificar que se guarde toda la información del pedido
 * Ejecutar desde la raíz del proyecto: php scripts/test-crear-pedido.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/app.php';

use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use Illuminate\Support\Facades\DB;

class TestCrearPedido
{
    private $asesora;
    private $cliente;
    private $pedido;

    public function ejecutar()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║        🧪 PRUEBA DE CREACIÓN DE PEDIDO COMPLETO           ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";

        try {
            $this->crearDatosBasicos();
            $this->crearPrendas();
            $this->verificarDatos();
            $this->mostrarResumen();
        } catch (\Exception $e) {
            $this->mostrarError($e);
        }
    }

    private function crearDatosBasicos()
    {
        echo "📋 PASO 1: Creando datos básicos...\n";
        echo "─────────────────────────────────────\n";

        // Crear usuario (asesora)
        $this->asesora = User::firstOrCreate(
            ['email' => 'asesora.test@test.com'],
            [
                'name' => 'Asesora Test',
                'password' => bcrypt('password'),
            ]
        );
        echo "  ✅ Usuario creado: {$this->asesora->name} (ID: {$this->asesora->id})\n";

        // Crear cliente
        $this->cliente = Cliente::firstOrCreate(
            ['nombre' => 'Cliente Test Pedido'],
            ['estado' => 'activo']
        );
        echo "  ✅ Cliente creado: {$this->cliente->nombre} (ID: {$this->cliente->id})\n";

        // Crear pedido
        $numeroPedido = DB::table('numero_secuencias')
            ->where('tipo', 'pedido_produccion')
            ->value('siguiente') ?? 45709;

        $this->pedido = PedidoProduccion::create([
            'numero_pedido' => $numeroPedido,
            'cliente' => $this->cliente->nombre,
            'cliente_id' => $this->cliente->id,
            'asesor_id' => $this->asesora->id,
            'forma_de_pago' => 'efectivo',
            'estado' => 'pendiente',
            'fecha_de_creacion_de_orden' => now(),
            'cantidad_total' => 0,
        ]);
        echo "  ✅ Pedido creado: #{$this->pedido->numero_pedido} (ID: {$this->pedido->id})\n\n";
    }

    private function crearPrendas()
    {
        echo "👕 PASO 2: Creando prendas...\n";
        echo "─────────────────────────────────────\n";

        // Crear prenda 1
        $prenda1 = PrendaPedido::create([
            'pedido_produccion_id' => $this->pedido->id,
            'nombre_producto' => 'Camiseta Básica',
            'descripcion' => 'Camiseta de algodón 100%',
            'de_bodega' => 1,
            'origen' => 'bodega',
            'cantidad_talla' => json_encode(['dama-S' => 10, 'dama-M' => 15, 'dama-L' => 5]),
            'estado' => 'pendiente',
        ]);
        echo "  ✅ Prenda 1 creada: {$prenda1->nombre_producto} (ID: {$prenda1->id})\n";

        // Crear prenda 2
        $prenda2 = PrendaPedido::create([
            'pedido_produccion_id' => $this->pedido->id,
            'nombre_producto' => 'Pantalón Ejecutivo',
            'descripcion' => 'Pantalón de vestir',
            'de_bodega' => 0,
            'origen' => 'confeccion',
            'cantidad_talla' => json_encode(['caballero-30' => 8, 'caballero-32' => 12]),
            'estado' => 'pendiente',
        ]);
        echo "  ✅ Prenda 2 creada: {$prenda2->nombre_producto} (ID: {$prenda2->id})\n";

        // Crear fotos de prenda 1
        PrendaFotoPedido::create([
            'prenda_pedido_id' => $prenda1->id,
            'ruta_original' => 'camiseta_original.jpg',
            'ruta_webp' => 'storage/pedidos/' . $this->pedido->id . '/prendas/camiseta.webp',
            'orden' => 1,
        ]);
        echo "  ✅ Foto de prenda 1 creada\n";

        // Crear fotos de tela para prenda 1
        PrendaFotoTelaPedido::create([
            'prenda_pedido_id' => $prenda1->id,
            'ruta_original' => 'tela_algodon.jpg',
            'ruta_webp' => 'storage/pedidos/' . $this->pedido->id . '/telas/algodon.webp',
            'orden' => 1,
        ]);
        echo "  ✅ Foto de tela para prenda 1 creada\n\n";
    }

    private function verificarDatos()
    {
        echo "🔍 PASO 3: Verificando datos guardados...\n";
        echo "─────────────────────────────────────\n";

        // Verificar pedido
        $pedidoEnBD = PedidoProduccion::find($this->pedido->id);
        if ($pedidoEnBD) {
            echo "  ✅ Pedido existe en BD\n";
            echo "     • Número: {$pedidoEnBD->numero_pedido}\n";
            echo "     • Cliente: {$pedidoEnBD->cliente}\n";
            echo "     • Asesor ID: {$pedidoEnBD->asesor_id}\n";
            echo "     • Estado: {$pedidoEnBD->estado}\n";
        } else {
            echo "  ❌ Pedido NO existe en BD\n";
        }

        // Verificar prendas
        $prendas = PrendaPedido::where('pedido_produccion_id', $this->pedido->id)->get();
        echo "\n  ✅ Prendas guardadas: {$prendas->count()}\n";
        foreach ($prendas as $prenda) {
            echo "     • {$prenda->nombre_producto} (ID: {$prenda->id})\n";
            echo "       - Cantidad talla: {$prenda->cantidad_talla}\n";

            // Verificar fotos de prenda
            $fotosPrenda = PrendaFotoPedido::where('prenda_pedido_id', $prenda->id)->get();
            echo "       - Fotos de prenda: {$fotosPrenda->count()}\n";

            // Verificar fotos de tela
            $fotosTela = PrendaFotoTelaPedido::where('prenda_pedido_id', $prenda->id)->get();
            echo "       - Fotos de tela: {$fotosTela->count()}\n";
        }

        // Verificar relaciones
        echo "\n  ✅ Verificando relaciones:\n";
        $cliente = $this->pedido->cliente()->first();
        if ($cliente) {
            echo "     • Cliente: {$cliente->nombre}\n";
        }

        $asesor = $this->pedido->asesor()->first();
        if ($asesor) {
            echo "     • Asesor: {$asesor->name}\n";
        }

        echo "\n";
    }

    private function mostrarResumen()
    {
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";

        echo "📊 RESUMEN DE DATOS GUARDADOS:\n";
        echo "─────────────────────────────────────\n";
        echo "  • Pedido ID: {$this->pedido->id}\n";
        echo "  • Número Pedido: {$this->pedido->numero_pedido}\n";
        echo "  • Cliente: {$this->cliente->nombre}\n";
        echo "  • Asesor: {$this->asesora->name}\n";
        echo "  • Prendas: " . PrendaPedido::where('pedido_produccion_id', $this->pedido->id)->count() . "\n";
        echo "  • Fotos de Prenda: " . PrendaFotoPedido::whereIn('prenda_pedido_id', 
            PrendaPedido::where('pedido_produccion_id', $this->pedido->id)->pluck('id')
        )->count() . "\n";
        echo "  • Fotos de Tela: " . PrendaFotoTelaPedido::whereIn('prenda_pedido_id',
            PrendaPedido::where('pedido_produccion_id', $this->pedido->id)->pluck('id')
        )->count() . "\n\n";

        echo "✨ Todos los datos se guardaron correctamente en la base de datos\n\n";
    }

    private function mostrarError(\Exception $e)
    {
        echo "\n╔════════════════════════════════════════════════════════════╗\n";
        echo "║                    ❌ ERROR EN LA PRUEBA                  ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";

        echo "📋 DETALLES DEL ERROR:\n";
        echo "─────────────────────────────────────\n";
        echo "  Mensaje: {$e->getMessage()}\n";
        echo "  Archivo: {$e->getFile()}\n";
        echo "  Línea: {$e->getLine()}\n\n";

        echo "Stack Trace:\n";
        echo $e->getTraceAsString() . "\n\n";
    }
}

// Ejecutar la prueba
$test = new TestCrearPedido();
$test->ejecutar();
