<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\User;
use App\Models\PedidoProduccion;
use App\Domain\Pedidos\Services\PedidoWebService;
use App\Domain\Pedidos\Services\ImagenRelocalizadorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Test de flujo de imágenes: Upload → Temp → Relocalización → BD
 * 
 * EJECUCIÓN:
 * php artisan test --filter=ImagenesFlujoPedidoTest
 */
class ImagenesFlujoPedidoTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $pedidoWebService;
    private $imagenRelocalizador;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Usar disk public para tests
        Storage::fake('public');
        
        // Crear usuario de prueba
        $this->user = User::factory()->create([
            'email' => 'asesor@test.com'
        ]);

        $this->pedidoWebService = app(PedidoWebService::class);
        $this->imagenRelocalizador = app(ImagenRelocalizadorService::class);
    }

    /**
     * TEST 1: Upload de imágenes a carpeta temporal
     * 
     * ✓ Imágenes se guardan en: prendas/temp/{uuid}/
     * ✓ Se crean versiones: original, webp, thumbnail
     * ✓ Response contiene temp_uuid
     */
    public function test_upload_temporal_crea_carpeta_uuid()
    {
        // Arrancar
        $file = UploadedFile::fake()->image('prenda1.jpg');
        $tempUuid = Str::uuid()->toString();

        // Actuar - Simular upload
        $response = $this->actingAs($this->user)
            ->postJson('/asesores/pedidos-editable/subir-imagenes-prenda', [
                'imagenes' => [$file],
            ]);

        // Verificar response
        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['temp_uuid']);
        
        // Verificar que archivos se crearon en temp
        $uuid = $data['temp_uuid'];
        
        Storage::disk('public')->assertExists("prendas/temp/{$uuid}/webp/");
        Storage::disk('public')->assertExists("prendas/temp/{$uuid}/original/");
        Storage::disk('public')->assertExists("prendas/temp/{$uuid}/thumbnails/");
        
        $this->assertEquals(1, count($data['imagenes']));
        $this->assertStringContainsString("prendas/temp/{$uuid}/webp/", $data['imagenes'][0]['ruta_webp']);
    }

    /**
     * TEST 2: Relocalizar imágenes de temp a pedidos/{id}/tipo/
     * 
     * ✓ ImagenRelocalizadorService mueve archivos
     * ✓ Estructura final es: pedidos/{id}/prendas/
     * ✓ Carpeta temp se limpia
     */
    public function test_relocalizar_imagenes_temp_a_pedidos()
    {
        // Arrancar - Crear archivo temporal
        $tempUuid = Str::uuid()->toString();
        $rutaTemp = "prendas/temp/{$tempUuid}/webp/prenda_test.webp";
        
        Storage::disk('public')->put($rutaTemp, 'fake image content');
        
        // Verificar que archivo temporal existe
        Storage::disk('public')->assertExists($rutaTemp);

        // Actuar - Relocalizar
        $pedidoId = 42;
        $rutasFinales = $this->imagenRelocalizador->relocalizarImagenes(
            $pedidoId,
            [$rutaTemp]
        );

        // Verificar resultado
        $this->assertCount(1, $rutasFinales);
        $rutaFinal = $rutasFinales[0];
        
        // Debe estar en estructura correcta
        $this->assertStringContainsString("pedidos/{$pedidoId}/prendas/", $rutaFinal);
        
        // Archivo debe existir en ubicación final
        Storage::disk('public')->assertExists($rutaFinal);
        
        // Archivo temporal debe estar eliminado
        Storage::disk('public')->assertMissing($rutaTemp);
    }

    /**
     * TEST 3: Extracción de tipo desde ruta temporal
     * 
     * ✓ Extrae 'prendas' de: prendas/temp/{uuid}/webp/...
     * ✓ Extrae 'telas' de: telas/temp/{uuid}/webp/...
     * ✓ Extrae 'procesos' de: procesos/temp/{uuid}/webp/...
     */
    public function test_extrae_tipo_correctamente()
    {
        $testCases = [
            'prendas/temp/uuid-123/webp/file.webp' => 'prendas',
            'telas/temp/uuid-456/webp/file.webp' => 'telas',
            'procesos/temp/uuid-789/webp/file.webp' => 'procesos',
            'logos/temp/uuid-abc/webp/file.webp' => 'logos',
        ];

        foreach ($testCases as $ruta => $tipoEsperado) {
            // Usar reflection para acceder a método privado
            $reflection = new \ReflectionClass($this->imagenRelocalizador);
            $method = $reflection->getMethod('extraerTipo');
            $method->setAccessible(true);
            
            $tipo = $method->invoke($this->imagenRelocalizador, $ruta);
            
            $this->assertEquals($tipoEsperado, $tipo, 
                "Fallo extrayendo tipo de: {$ruta}");
        }
    }

    /**
     * TEST 4: Múltiples imágenes se relocalizan juntas
     * 
     * ✓ Se relocaliza array de rutas
     * ✓ Retorna array de rutas finales
     * ✓ Todas en el mismo pedido
     */
    public function test_relocalizar_multiples_imagenes()
    {
        // Arrancar - Crear múltiples archivos temporales
        $tempUuid = Str::uuid()->toString();
        $rutasTemp = [
            "prendas/temp/{$tempUuid}/webp/prenda_0.webp",
            "prendas/temp/{$tempUuid}/webp/prenda_1.webp",
            "prendas/temp/{$tempUuid}/webp/prenda_2.webp",
        ];
        
        foreach ($rutasTemp as $ruta) {
            Storage::disk('public')->put($ruta, 'fake content');
        }

        // Actuar
        $pedidoId = 99;
        $rutasFinales = $this->imagenRelocalizador->relocalizarImagenes(
            $pedidoId,
            $rutasTemp
        );

        // Verificar - Todas se reloca lizaron
        $this->assertCount(3, $rutasFinales);
        
        // Todas están en el mismo pedido
        foreach ($rutasFinales as $ruta) {
            $this->assertStringContainsString("pedidos/{$pedidoId}/", $ruta);
        }

        // Temporales eliminadas
        foreach ($rutasTemp as $ruta) {
            Storage::disk('public')->assertMissing($ruta);
        }
    }

    /**
     * TEST 5: Limpieza automática de carpetas vacías
     * 
     * ✓ Después de mover archivos, carpeta temp se elimina
     */
    public function test_limpia_carpeta_temp_vacia()
    {
        // Arrancar
        $tempUuid = Str::uuid()->toString();
        $carpetaTemp = "prendas/temp/{$tempUuid}";
        $rutaTemp = "{$carpetaTemp}/webp/prenda.webp";
        
        Storage::disk('public')->put($rutaTemp, 'content');
        Storage::disk('public')->assertExists($carpetaTemp);

        // Actuar - Relocalizar único archivo
        $this->imagenRelocalizador->relocalizarImagenes(1, [$rutaTemp]);

        // Verificar - Carpeta temp debe estar limpia
        Storage::disk('public')->assertMissing($carpetaTemp);
    }

    /**
     * TEST 6: Manejo de errores - Archivo que no existe
     * 
     * ✓ No rompe el flujo
     * ✓ Retorna array vacío o skip esa ruta
     */
    public function test_maneja_archivo_inexistente_gracefully()
    {
        // Arrancar - Ruta que no existe
        $rutaInexistente = "prendas/temp/uuid-noexiste/webp/noexiste.webp";

        // Actuar - No debe lanzar exception
        $rutasFinales = $this->imagenRelocalizador->relocalizarImagenes(
            123,
            [$rutaInexistente]
        );

        // Verificar - Retorna array vacío (el archivo se skipea)
        $this->assertCount(0, $rutasFinales);
    }

    /**
     * TEST 7: Limpieza por UUID
     * 
     * ✓ limpiarCarpetaTempPorUuid() elimina todos los archivos de ese UUID
     */
    public function test_limpia_carpeta_por_uuid()
    {
        // Arrancar - Crear archivos en diferentes tipos
        $uuid = Str::uuid()->toString();
        $archivos = [
            "prendas/temp/{$uuid}/webp/file1.webp",
            "telas/temp/{$uuid}/webp/file2.webp",
            "procesos/temp/{$uuid}/webp/file3.webp",
        ];

        foreach ($archivos as $ruta) {
            Storage::disk('public')->put($ruta, 'content');
        }

        // Verificar que existen
        foreach ($archivos as $ruta) {
            Storage::disk('public')->assertExists($ruta);
        }

        // Actuar - Limpiar por UUID
        $this->imagenRelocalizador->limpiarCarpetaTempPorUuid($uuid);

        // Verificar - Todos eliminados
        foreach ($archivos as $ruta) {
            Storage::disk('public')->assertMissing($ruta);
        }
    }
}
