<?php

namespace Tests\Feature;

use App\Models\PedidoProduccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificarDescripcionPedido45471Test extends TestCase
{
    /**
     * Test que verifica la descripción del pedido 45471
     */
    public function test_descripcion_pedido_45471()
    {
        // Buscar el pedido 45471
        $pedido = PedidoProduccion::where('numero_pedido', '45471')
            ->with([
                'prendas' => function ($q) {
                    $q->with(['color', 'tela', 'tipoManga']);
                }
            ])
            ->first();

        if (!$pedido) {
            $this->markTestSkipped('Pedido 45471 no encontrado en la base de datos');
            return;
        }

        // Verificar que tiene prendas
        $this->assertNotEmpty($pedido->prendas, 'El pedido no tiene prendas');

        // Obtener la descripción dinámica
        $descripcion = $pedido->descripcion_prendas;

        // Mostrar información del pedido
        echo "\n";
        echo "========================================\n";
        echo "PEDIDO: {$pedido->numero_pedido}\n";
        echo "CLIENTE: {$pedido->cliente}\n";
        echo "TOTAL PRENDAS: {$pedido->prendas->count()}\n";
        echo "CANTIDAD TOTAL: {$pedido->cantidad_total}\n";
        echo "ESTADO: {$pedido->estado}\n";
        echo "FORMA DE PAGO: {$pedido->forma_de_pago}\n";
        echo "========================================\n\n";

        // Mostrar información de cada prenda
        foreach ($pedido->prendas as $index => $prenda) {
            echo "--- PRENDA " . ($index + 1) . " ---\n";
            echo "Nombre: {$prenda->nombre_prenda}\n";
            echo "Color ID: {$prenda->color_id}\n";
            if ($prenda->relationLoaded('color') && $prenda->color) {
                echo "Color: {$prenda->color->nombre}\n";
            }
            echo "Tela ID: {$prenda->tela_id}\n";
            if ($prenda->relationLoaded('tela') && $prenda->tela) {
                echo "Tela: {$prenda->tela->nombre} ({$prenda->tela->referencia})\n";
            }
            echo "Manga ID: {$prenda->tipo_manga_id}\n";
            if ($prenda->relationLoaded('tipoManga') && $prenda->tipoManga) {
                echo "Manga: {$prenda->tipoManga->nombre}\n";
            }
            echo "Cantidad: {$prenda->cantidad}\n";
            echo "Tallas: " . json_encode($prenda->cantidad_talla) . "\n";
            echo "Descripción: " . substr($prenda->descripcion ?? 'N/A', 0, 100) . "...\n";
            echo "Variaciones: " . substr($prenda->descripcion_variaciones ?? 'N/A', 0, 100) . "...\n\n";
        }

        // Mostrar descripción generada
        echo "========================================\n";
        echo "DESCRIPCIÓN GENERADA:\n";
        echo "========================================\n";
        echo $descripcion;
        echo "\n========================================\n";

        // Hacer assert que la descripción no esté vacía
        $this->assertNotEmpty($descripcion, 'La descripción generada está vacía');

        // Verificar que contiene información de las prendas
        $this->assertStringContainsString('PRENDA', $descripcion);
    }
}
