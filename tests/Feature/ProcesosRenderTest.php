<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PedidoProduccion;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;

class ProcesosRenderTest extends TestCase
{
    /**
     * Test: Verificar que procesos incluyen campos 'nombre' y 'tipo'
     * para compatibilidad con frontend (ReceiptManager.js)
     */
    public function test_obtenerDatosRecibos_incluye_campos_nombre_tipo()
    {
        // Arrange: Obtener un pedido con procesos
        $pedido = PedidoProduccion::with(['prendas.procesos'])
            ->whereHas('prendas', function ($query) {
                $query->whereHas('procesos', function ($q) {
                    $q->where('estado', '!=', 'eliminado');
                });
            })
            ->first();

        if (!$pedido) {
            $this->markTestSkipped('No hay pedidos con procesos para probar');
        }

        // Act: Obtener datos de recibos
        $repository = new PedidoProduccionRepository();
        $datos = $repository->obtenerDatosRecibos($pedido->id);

        // Assert: Verificar estructura
        $this->assertNotEmpty($datos['prendas'], 'Debe haber prendas');
        $this->assertNotEmpty($datos['prendas'][0]['procesos'], 'Primera prenda debe tener procesos');

        $primerProceso = $datos['prendas'][0]['procesos'][0];

        // Campos esperados para frontend
        $this->assertArrayHasKey('nombre', $primerProceso, 'Debe tener campo "nombre" para ReceiptManager');
        $this->assertArrayHasKey('tipo', $primerProceso, 'Debe tener campo "tipo" para ReceiptManager');

        // Campos originales para compatibilidad backwards
        $this->assertArrayHasKey('nombre_proceso', $primerProceso, 'Debe tener "nombre_proceso" para compatibilidad');
        $this->assertArrayHasKey('tipo_proceso', $primerProceso, 'Debe tener "tipo_proceso" para compatibilidad');

        // Otros campos necesarios
        $this->assertArrayHasKey('tallas', $primerProceso, 'Debe tener "tallas"');
        $this->assertArrayHasKey('imagenes', $primerProceso, 'Debe tener "imagenes"');
        $this->assertArrayHasKey('ubicaciones', $primerProceso, 'Debe tener "ubicaciones"');
        $this->assertArrayHasKey('observaciones', $primerProceso, 'Debe tener "observaciones"');

        // Valores no vacíos (si tiene procesos, debe tener nombre)
        $this->assertNotEmpty($primerProceso['nombre'], 'Campo "nombre" no debe estar vacío');
        $this->assertNotEmpty($primerProceso['tipo'], 'Campo "tipo" no debe estar vacío');
    }

    /**
     * Test: Verificar que obtenerDatosFactura también incluye los campos
     */
    public function test_obtenerDatosFactura_incluye_campos_nombre_tipo()
    {
        // Arrange
        $pedido = PedidoProduccion::with(['prendas.procesos'])
            ->whereHas('prendas', function ($query) {
                $query->whereHas('procesos');
            })
            ->first();

        if (!$pedido) {
            $this->markTestSkipped('No hay pedidos con procesos para probar');
        }

        // Act
        $repository = new PedidoProduccionRepository();
        $datos = $repository->obtenerDatosFactura($pedido->id);

        // Assert
        $this->assertNotEmpty($datos['prendas'], 'Debe haber prendas');
        $this->assertNotEmpty($datos['prendas'][0]['procesos'] ?? [], 'Primera prenda debe tener procesos');

        if (count($datos['prendas'][0]['procesos'] ?? []) > 0) {
            $primerProceso = $datos['prendas'][0]['procesos'][0];

            // Verificar campos existentes
            $this->assertArrayHasKey('nombre', $primerProceso, 'Debe tener "nombre" para ReceiptManager');
            $this->assertArrayHasKey('tipo', $primerProceso, 'Debe tener "tipo" para ReceiptManager');
        }
    }

    /**
     * Test: Verificar que imágenes de procesos se incluyen
     */
    public function test_procesos_incluyen_imagenes()
    {
        $pedido = PedidoProduccion::with(['prendas.procesos.imagenes'])
            ->whereHas('prendas', function ($query) {
                $query->whereHas('procesos', function ($q) {
                    $q->whereHas('imagenes');
                });
            })
            ->first();

        if (!$pedido) {
            $this->markTestSkipped('No hay procesos con imágenes para probar');
        }

        $repository = new PedidoProduccionRepository();
        $datos = $repository->obtenerDatosRecibos($pedido->id);

        // Buscar un proceso con imágenes
        $procesoConImagenes = null;
        foreach ($datos['prendas'] as $prenda) {
            foreach ($prenda['procesos'] as $proc) {
                if (!empty($proc['imagenes'])) {
                    $procesoConImagenes = $proc;
                    break 2;
                }
            }
        }

        if ($procesoConImagenes) {
            $this->assertNotEmpty($procesoConImagenes['imagenes'], 'Las imágenes del proceso no deben estar vacías');
            $this->assertTrue(is_array($procesoConImagenes['imagenes']), 'Las imágenes deben ser array');
        }
    }

    /**
     * Test: Verificar que tallas se estructura correctamente
     */
    public function test_procesos_incluyen_tallas_estructura()
    {
        $pedido = PedidoProduccion::with(['prendas.procesos'])
            ->whereHas('prendas', function ($query) {
                $query->whereHas('procesos');
            })
            ->first();

        if (!$pedido) {
            $this->markTestSkipped('No hay procesos para probar');
        }

        $repository = new PedidoProduccionRepository();
        $datos = $repository->obtenerDatosRecibos($pedido->id);

        if (count($datos['prendas'][0]['procesos'] ?? []) > 0) {
            $primerProceso = $datos['prendas'][0]['procesos'][0];

            $this->assertArrayHasKey('tallas', $primerProceso, 'Debe tener "tallas"');
            $this->assertTrue(is_array($primerProceso['tallas']), 'Tallas debe ser array');

            // Estructura esperada: { dama: {...}, caballero: {...}, unisex: {...} }
            $expectedGeneros = ['dama', 'caballero', 'unisex'];
            foreach ($expectedGeneros as $genero) {
                $this->assertArrayHasKey($genero, $primerProceso['tallas'], 
                    "Tallas debe incluir género: $genero");
            }
        }
    }
}
