<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\Epp;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * PedidoProduccionRepositoryTest
 * 
 * Suite de tests para PedidoProduccionRepository
 * 
 * Valida:
 * - obtenerPorIdYAsesor() - Verificación de seguridad
 * - actualizarDatosBasicos() - Actualización de campos
 * - obtenerEppConImagenes() - Obtención con relaciones
 * - eliminarImagenesEpp() - Eliminación de archivos e imágenes
 * 
 * @package Tests\Feature\Repositories
 */
class PedidoProduccionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PedidoProduccionRepository $repository;
    private User $asesor;
    private PedidoProduccion $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear asesor
        $this->asesor = User::factory()->create();

        // Crear pedido
        $this->pedido = PedidoProduccion::create([
            'asesor_id' => $this->asesor->id,
            'numero_pedido' => 'TEST-001',
            'cliente' => 'Cliente Test',
            'estado' => 'Borrador',
        ]);

        // Obtener repositorio
        $this->repository = app(PedidoProduccionRepository::class);
    }

    /**
     * TEST 1: obtenerPorIdYAsesor - Encontrar pedido
     * 
     * Verifica que se encuentre un pedido si el asesor es correcto
     */
    public function test_obtener_por_id_y_asesor_exitoso()
    {
        // Ejecutar
        $resultado = $this->repository->obtenerPorIdYAsesor(
            $this->pedido->id,
            $this->asesor->id
        );

        // Validaciones
        $this->assertNotNull($resultado);
        $this->assertEquals($this->pedido->id, $resultado->id);
        $this->assertEquals($this->asesor->id, $resultado->asesor_id);
    }

    /**
     * TEST 2: obtenerPorIdYAsesor - Asesor incorrecto
     * 
     * Verifica que NO se encuentre si el asesor es diferente
     */
    public function test_obtener_por_id_y_asesor_incorrecto()
    {
        // Crear otro asesor
        $otroAsesor = User::factory()->create();

        // Ejecutar
        $resultado = $this->repository->obtenerPorIdYAsesor(
            $this->pedido->id,
            $otroAsesor->id
        );

        // Validaciones
        $this->assertNull($resultado);
    }

    /**
     * TEST 3: obtenerPorIdYAsesor - Pedido no existe
     * 
     * Verifica que se retorne NULL si el pedido no existe
     */
    public function test_obtener_por_id_y_asesor_no_existe()
    {
        // Ejecutar
        $resultado = $this->repository->obtenerPorIdYAsesor(
            99999,
            $this->asesor->id
        );

        // Validaciones
        $this->assertNull($resultado);
    }

    /**
     * TEST 4: actualizarDatosBasicos - Actualización correcta
     * 
     * Verifica que se actualicen los campos
     */
    public function test_actualizar_datos_basicos()
    {
        // Preparar datos
        $datos = [
            'cliente' => 'Cliente Actualizado',
            'forma_de_pago' => 'Crédito 30 días',
            'observaciones' => 'Nuevas notas',
        ];

        // Ejecutar
        $this->repository->actualizarDatosBasicos($this->pedido, $datos);

        // Verificar en BD
        $pedidoActualizado = PedidoProduccion::find($this->pedido->id);
        $this->assertEquals('Cliente Actualizado', $pedidoActualizado->cliente);
        $this->assertEquals('Crédito 30 días', $pedidoActualizado->forma_de_pago);
        $this->assertEquals('Nuevas notas', $pedidoActualizado->observaciones);
    }

    /**
     * TEST 5: actualizarDatosBasicos - Campos parciales
     * 
     * Verifica que se actualicen solo los campos especificados
     */
    public function test_actualizar_datos_basicos_parcial()
    {
        // Actualizar solo cliente
        $this->repository->actualizarDatosBasicos($this->pedido, [
            'cliente' => 'Nuevo Cliente',
        ]);

        // Verificar que solo cliente cambió
        $pedidoActualizado = PedidoProduccion::find($this->pedido->id);
        $this->assertEquals('Nuevo Cliente', $pedidoActualizado->cliente);
        // Los otros campos deben permanecer igual (o vacíos si no tenían valor)
    }

    /**
     * TEST 6: obtenerEppConImagenes - Encontrar con imágenes
     * 
     * Verifica que se obtenga EPP con sus imágenes
     */
    public function test_obtener_epp_con_imagenes()
    {
        // Crear EPP
        $epp = Epp::create([
            'nombre_completo' => 'EPP Test',
            'activo' => true,
        ]);

        // Crear PedidoEpp
        $pedidoEpp = PedidoEpp::create([
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $epp->id,
            'cantidad' => 5,
        ]);

        // Crear imágenes
        PedidoEppImagen::create([
            'pedido_epp_id' => $pedidoEpp->id,
            'ruta_original' => '/storage/test.webp',
            'ruta_web' => '/storage/test.webp',
            'principal' => 1,
        ]);

        // Ejecutar
        $resultado = $this->repository->obtenerEppConImagenes($this->pedido->id, $epp->id);

        // Validaciones
        $this->assertNotNull($resultado);
        $this->assertEquals($epp->id, $resultado->epp_id);
        $this->assertEquals(1, $resultado->imagenes->count());
    }

    /**
     * TEST 7: obtenerEppConImagenes - EPP no existe
     * 
     * Verifica que se retorne NULL si no existe
     */
    public function test_obtener_epp_con_imagenes_no_existe()
    {
        // Ejecutar
        $resultado = $this->repository->obtenerEppConImagenes(
            $this->pedido->id,
            99999
        );

        // Validaciones
        $this->assertNull($resultado);
    }

    /**
     * TEST 8: eliminarImagenesEpp - Eliminar imágenes
     * 
     * Verifica que se eliminen registros de BD
     */
    public function test_eliminar_imagenes_epp()
    {
        // Crear EPP y imágenes
        $epp = Epp::create([
            'nombre_completo' => 'EPP Delete Test',
            'activo' => true,
        ]);

        $pedidoEpp = PedidoEpp::create([
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $epp->id,
            'cantidad' => 1,
        ]);

        // Crear 3 imágenes
        for ($i = 1; $i <= 3; $i++) {
            PedidoEppImagen::create([
                'pedido_epp_id' => $pedidoEpp->id,
                'ruta_original' => "/storage/image{$i}.webp",
                'ruta_web' => "/storage/image{$i}.webp",
                'principal' => $i === 1 ? 1 : 0,
            ]);
        }

        $this->assertEquals(3, PedidoEppImagen::where('pedido_epp_id', $pedidoEpp->id)->count());

        // Ejecutar
        $cantidadEliminada = $this->repository->eliminarImagenesEpp($pedidoEpp->id);

        // Validaciones
        $this->assertEquals(3, $cantidadEliminada);
        $this->assertEquals(0, PedidoEppImagen::where('pedido_epp_id', $pedidoEpp->id)->count());
    }

    /**
     * TEST 9: eliminarImagenesEpp - Sin imágenes
     * 
     * Verifica que retorne 0 si no hay imágenes
     */
    public function test_eliminar_imagenes_epp_sin_imagenes()
    {
        // Crear EPP sin imágenes
        $epp = Epp::create([
            'nombre_completo' => 'EPP Sin Img',
            'activo' => true,
        ]);

        $pedidoEpp = PedidoEpp::create([
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $epp->id,
            'cantidad' => 1,
        ]);

        // Ejecutar
        $cantidadEliminada = $this->repository->eliminarImagenesEpp($pedidoEpp->id);

        // Validaciones
        $this->assertEquals(0, $cantidadEliminada);
    }
}
