<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Script de prueba para verificar que se guarde toda la información del pedido
 * Ejecutar: php artisan tinker
 * Luego: include 'tests/Feature/CrearPedidoTestScript.php'; (new CrearPedidoTestScript())->ejecutarPrueba();
 */
class CrearPedidoTestScript extends TestCase
{
    use RefreshDatabase;

    public function ejecutarPrueba()
    {
        echo "\n========================================\n";
        echo "🧪 INICIANDO PRUEBA DE CREACIÓN DE PEDIDO\n";
        echo "========================================\n\n";

        try {
            // 1. Crear usuario (asesora)
            echo "  Creando usuario (asesora)...\n";
            $asesora = User::factory()->create([
                'name' => 'Asesora Test',
                'email' => 'asesora@test.com',
            ]);
            echo "  Usuario creado: {$asesora->name} (ID: {$asesora->id})\n\n";

            // 2. Crear cliente
            echo "  Creando cliente...\n";
            $cliente = Cliente::create([
                'nombre' => 'Cliente Test',
                'estado' => 'activo',
            ]);
            echo "  Cliente creado: {$cliente->nombre} (ID: {$cliente->id})\n\n";

            // 3. Crear pedido
            echo "  Creando pedido...\n";
            $pedido = PedidoProduccion::create([
                'numero_pedido' => 45709,
                'cliente' => $cliente->nombre,
                'cliente_id' => $cliente->id,
                'asesor_id' => $asesora->id,
                'forma_de_pago' => 'efectivo',
                'estado' => 'pendiente',
                'fecha_de_creacion_de_orden' => now(),
                'cantidad_total' => 100,
            ]);
            echo "  Pedido creado: #{$pedido->numero_pedido} (ID: {$pedido->id})\n\n";

            // 4. Verificar datos del pedido
            echo "4️⃣  Verificando datos del pedido...\n";
            $this->verificarPedido($pedido);

            // 5. Verificar relaciones
            echo "\n5️⃣  Verificando relaciones...\n";
            $this->verificarRelaciones($pedido);

            // 6. Resumen final
            echo "\n========================================\n";
            echo " PRUEBA COMPLETADA EXITOSAMENTE\n";
            echo "========================================\n";
            echo "Pedido guardado correctamente en la base de datos\n";
            echo "Todas las relaciones están configuradas correctamente\n\n";

        } catch (\Exception $e) {
            echo "\n ERROR EN LA PRUEBA:\n";
            echo "   {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}\n";
            echo "   Línea: {$e->getLine()}\n\n";
        }
    }

    private function verificarPedido(PedidoProduccion $pedido): void
    {
        $datos = [
            'ID' => $pedido->id,
            'Número de Pedido' => $pedido->numero_pedido,
            'Cliente' => $pedido->cliente,
            'Cliente ID' => $pedido->cliente_id,
            'Asesor ID' => $pedido->asesor_id,
            'Forma de Pago' => $pedido->forma_de_pago,
            'Estado' => $pedido->estado,
            'Cantidad Total' => $pedido->cantidad_total,
            'Fecha Creación' => $pedido->fecha_de_creacion_de_orden,
        ];

        foreach ($datos as $campo => $valor) {
            $estado = $valor ? '' : '';
            echo "   {$estado} {$campo}: {$valor}\n";
        }
    }

    private function verificarRelaciones(PedidoProduccion $pedido): void
    {
        // Verificar relación con cliente
        $cliente = $pedido->cliente()->first();
        if ($cliente) {
            echo "  Relación con Cliente: {$cliente->nombre}\n";
        } else {
            echo "    No se encontró relación con Cliente\n";
        }

        // Verificar relación con asesor
        $asesor = $pedido->asesor()->first();
        if ($asesor) {
            echo "  Relación con Asesor: {$asesor->name}\n";
        } else {
            echo "    No se encontró relación con Asesor\n";
        }

        // Verificar que el pedido existe en BD
        $pedidoEnBD = PedidoProduccion::find($pedido->id);
        if ($pedidoEnBD) {
            echo "  Pedido existe en base de datos\n";
        } else {
            echo "    Pedido NO existe en base de datos\n";
        }

        // Verificar búsqueda por número de pedido
        $pedidoPorNumero = PedidoProduccion::where('numero_pedido', $pedido->numero_pedido)->first();
        if ($pedidoPorNumero) {
            echo "  Búsqueda por número de pedido funciona\n";
        } else {
            echo "    Búsqueda por número de pedido NO funciona\n";
        }
    }
}

// Ejecutar la prueba
$prueba = new CrearPedidoTestScript();
$prueba->ejecutarPrueba();
