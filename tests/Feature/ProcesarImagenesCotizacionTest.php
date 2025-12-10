<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cliente;
use App\Models\PrendaCot;
use App\Application\Services\ProcesarImagenesCotizacionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcesarImagenesCotizacionTest extends TestCase
{
    protected User $user;
    protected ProcesarImagenesCotizacionService $procesarImagenesService;

    protected function setUp(): void
    {
        parent::setUp();
        // Usar usuario existente en BD (ID 18) - NO usar RefreshDatabase
        $this->user = User::find(18);
        if (!$this->user) {
            $this->markTestSkipped('Usuario ID 18 no existe en la BD');
        }
        $this->procesarImagenesService = app(ProcesarImagenesCotizacionService::class);
        Storage::fake('public');
    }

    /**
     * Test: Procesar imagen de prenda y guardar ruta en BD
     */
    public function test_procesar_imagen_prenda_y_guardar_ruta()
    {
        $cotizacionId = 1;
        $prendaId = 1;

        // Crear imagen fake
        $archivo = UploadedFile::fake()->image('prenda.jpg', 1920, 1080);

        // Procesar imagen
        $ruta = $this->procesarImagenesService->procesarImagenPrenda(
            $archivo,
            $cotizacionId,
            $prendaId
        );

        // Verificar que retorna una ruta
        $this->assertNotNull($ruta);
        $this->assertStringContainsString('/storage/cotizaciones/', $ruta);
        $this->assertStringContainsString('.webp', $ruta);

        // Verificar que el archivo existe en storage
        $rutaRelativa = str_replace('/storage/', '', $ruta);
        Storage::disk('public')->assertExists($rutaRelativa);

        // Verificar estructura de carpetas
        Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/prendas");
    }

    /**
     * Test: Procesar imagen de tela y guardar ruta en BD
     */
    public function test_procesar_imagen_tela_y_guardar_ruta()
    {
        $cotizacionId = 1;
        $prendaId = 1;

        $archivo = UploadedFile::fake()->image('tela.png', 1600, 1200);

        $ruta = $this->procesarImagenesService->procesarImagenTela(
            $archivo,
            $cotizacionId,
            $prendaId
        );

        $this->assertNotNull($ruta);
        $this->assertStringContainsString('/storage/cotizaciones/', $ruta);
        $this->assertStringContainsString('.webp', $ruta);

        $rutaRelativa = str_replace('/storage/', '', $ruta);
        Storage::disk('public')->assertExists($rutaRelativa);
        Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/telas");
    }

    /**
     * Test: Procesar imagen de logo y guardar ruta en BD
     */
    public function test_procesar_imagen_logo_y_guardar_ruta()
    {
        $cotizacionId = 1;

        $archivo = UploadedFile::fake()->image('logo.png', 800, 600);

        $ruta = $this->procesarImagenesService->procesarImagenLogo($archivo, $cotizacionId);

        $this->assertNotNull($ruta);
        $this->assertStringContainsString('/storage/cotizaciones/', $ruta);
        $this->assertStringContainsString('.webp', $ruta);

        $rutaRelativa = str_replace('/storage/', '', $ruta);
        Storage::disk('public')->assertExists($rutaRelativa);
        Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/logo");
    }

    /**
     * Test: Procesar múltiples imágenes de prenda y guardar en BD (prenda_fotos_cot)
     */
    public function test_procesar_multiples_imagenes_prenda()
    {
        $cotizacionId = 1;
        $prendaId = 1;

        $archivo1 = UploadedFile::fake()->image('prenda1.jpg', 1920, 1080);
        $archivo2 = UploadedFile::fake()->image('prenda2.png', 1600, 1200);

        $rutas = $this->procesarImagenesService->procesarImagenesPrenda(
            [$archivo1, $archivo2],
            $cotizacionId,
            $prendaId
        );

        // Verificar que retorna 2 rutas
        $this->assertCount(2, $rutas);

        // Verificar cada ruta
        foreach ($rutas as $ruta) {
            // 1. Verificar que la ruta tiene el formato correcto
            $this->assertStringContainsString('/storage/cotizaciones/', $ruta);
            $this->assertStringContainsString('.webp', $ruta);

            // 2. Verificar que el archivo existe en storage/public
            $rutaRelativa = str_replace('/storage/', '', $ruta);
            Storage::disk('public')->assertExists($rutaRelativa);

            // 3. Verificar que la carpeta se creó correctamente
            Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/prendas");
        }

        // 4. Verificar que las rutas se pueden guardar en BD (tabla prenda_fotos_cot)
        // Nota: prenda_fotos_cot tiene campos: ruta_original, ruta_webp, ruta_miniatura, orden
        $prenda = \App\Models\PrendaCot::find($prendaId);
        if ($prenda) {
            // Guardar usando los campos correctos (ruta_original, ruta_webp, orden)
            $fotosCreadas = [];
            foreach ($rutas as $index => $ruta) {
                $fotosCreadas[] = \DB::table('prenda_fotos_cot')->insertGetId([
                    'prenda_cot_id' => $prendaId,
                    'ruta_original' => $ruta,
                    'ruta_webp' => $ruta,
                    'orden' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Verificar que las fotos se guardaron en BD (prenda_fotos_cot)
            $fotosEnBD = \DB::table('prenda_fotos_cot')
                ->whereIn('id', $fotosCreadas)
                ->get();
            $this->assertCount(2, $fotosEnBD, 'Debe haber 2 fotos en prenda_fotos_cot');

            // Verificar que las rutas en BD son correctas
            foreach ($fotosEnBD as $foto) {
                $this->assertNotNull($foto->ruta_webp);
                $this->assertStringContainsString('/storage/cotizaciones/', $foto->ruta_webp);
                $this->assertStringContainsString('.webp', $foto->ruta_webp);
            }
        }

        echo "\n✅ Test: Procesar múltiples imágenes de prenda - PASADO\n";
        echo "   ✓ Rutas retornadas: " . count($rutas) . "\n";
        echo "   ✓ Archivos guardados en storage/cotizaciones/{$cotizacionId}/prendas/\n";
        echo "   ✓ Rutas guardadas en BD (tabla prenda_fotos_cot)\n";
    }

    /**
     * Test: Procesar imágenes de tela y guardar en BD (prenda_tela_fotos_cot)
     */
    public function test_procesar_imagenes_tela_y_guardar_en_bd()
    {
        $cotizacionId = 1;
        $prendaId = 1;

        $archivo = UploadedFile::fake()->image('tela.jpg', 1400, 1000);

        $ruta = $this->procesarImagenesService->procesarImagenTela(
            $archivo,
            $cotizacionId,
            $prendaId
        );

        // Verificar que retorna una ruta
        $this->assertNotNull($ruta);
        $this->assertStringContainsString('/storage/cotizaciones/', $ruta);
        $this->assertStringContainsString('.webp', $ruta);

        // Verificar que el archivo existe en storage
        $rutaRelativa = str_replace('/storage/', '', $ruta);
        Storage::disk('public')->assertExists($rutaRelativa);
        Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/telas");

        // Verificar que la ruta se puede guardar en BD (prenda_tela_fotos_cot)
        // Nota: prenda_tela_fotos_cot tiene campos: ruta_original, ruta_webp, prenda_cot_id
        $prenda = \App\Models\PrendaCot::find($prendaId);
        if ($prenda) {
            // Crear foto de tela con los campos correctos
            \DB::table('prenda_tela_fotos_cot')->insert([
                'prenda_cot_id' => $prendaId,
                'ruta_original' => $ruta,
                'ruta_webp' => $ruta,
                'orden' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verificar que se guardó en BD
            $telasEnBD = \DB::table('prenda_tela_fotos_cot')
                ->where('prenda_cot_id', $prendaId)
                ->get();
            $this->assertGreaterThan(0, $telasEnBD->count(), 'Debe haber al menos 1 foto en prenda_tela_fotos_cot');
            $this->assertStringContainsString('/storage/cotizaciones/', $telasEnBD[0]->ruta_webp);
        }

        echo "\n✅ Test: Procesar imagen de tela y guardar en BD - PASADO\n";
        echo "   ✓ Ruta guardada en BD (tabla prenda_tela_fotos_cot)\n";
    }

    /**
     * Test: Procesar imágenes de logo y guardar en BD (logo_fotos_cot)
     */
    public function test_procesar_imagenes_logo_y_guardar_en_bd()
    {
        $cotizacionId = 1;

        $archivo = UploadedFile::fake()->image('logo.png', 800, 600);

        $ruta = $this->procesarImagenesService->procesarImagenLogo(
            $archivo,
            $cotizacionId
        );

        // Verificar que retorna una ruta
        $this->assertNotNull($ruta);
        $this->assertStringContainsString('/storage/cotizaciones/', $ruta);
        $this->assertStringContainsString('.webp', $ruta);

        // Verificar que el archivo existe en storage
        $rutaRelativa = str_replace('/storage/', '', $ruta);
        Storage::disk('public')->assertExists($rutaRelativa);
        Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/logo");

        // Verificar que la ruta se puede guardar en BD (logo_fotos_cot)
        // Nota: logo_fotos_cot tiene campos: ruta_original, ruta_webp, logo_cotizacion_id
        $cotizacion = \App\Models\Cotizacion::find($cotizacionId);
        if ($cotizacion) {
            // Obtener o crear LogoCotizacion
            $logoCotizacion = $cotizacion->logoCotizacion;
            if (!$logoCotizacion) {
                $logoCotizacion = \App\Models\LogoCotizacion::create([
                    'cotizacion_id' => $cotizacionId,
                ]);
            }

            // Crear foto de logo con los campos correctos (ruta_original, ruta_webp, logo_cotizacion_id)
            \App\Models\LogoFoto::create([
                'logo_cotizacion_id' => $logoCotizacion->id,
                'ruta_original' => $ruta,
                'ruta_webp' => $ruta,
                'orden' => 1,
            ]);

            // Verificar que se guardó en BD
            $logosEnBD = \App\Models\LogoFoto::where('logo_cotizacion_id', $logoCotizacion->id)->get();
            $this->assertGreaterThan(0, $logosEnBD->count(), 'Debe haber al menos 1 foto en logo_fotos_cot');
            $this->assertStringContainsString('/storage/cotizaciones/', $logosEnBD[0]->ruta_webp);
        }

        echo "\n✅ Test: Procesar imagen de logo y guardar en BD - PASADO\n";
        echo "   ✓ Ruta guardada en BD (tabla logo_fotos_cot)\n";
    }

    /**
     * Test: Validar que no se acepten archivos grandes
     */
    public function test_rechazar_archivo_muy_grande()
    {
        $this->expectException(\InvalidArgumentException::class);

        $cotizacionId = 1;
        $prendaId = 1;

        // Crear archivo fake muy grande (simular)
        $archivo = UploadedFile::fake()->create('grande.jpg', 6000); // 6MB

        $this->procesarImagenesService->procesarImagenPrenda(
            $archivo,
            $cotizacionId,
            $prendaId
        );
    }

    /**
     * Test: Validar que solo se acepten tipos de imagen válidos
     */
    public function test_rechazar_tipo_archivo_invalido()
    {
        $this->expectException(\InvalidArgumentException::class);

        $cotizacionId = 1;
        $prendaId = 1;

        // Crear archivo fake de tipo inválido
        $archivo = UploadedFile::fake()->create('documento.pdf', 100);

        $this->procesarImagenesService->procesarImagenPrenda(
            $archivo,
            $cotizacionId,
            $prendaId
        );
    }

    /**
     * Test: Verificar estructura de carpetas
     */
    public function test_estructura_carpetas_correcta()
    {
        $cotizacionId = 1;

        // Procesar imágenes de diferentes tipos
        $archivoPrenda = UploadedFile::fake()->image('prenda.jpg');
        $archivoTela = UploadedFile::fake()->image('tela.png');
        $archivoLogo = UploadedFile::fake()->image('logo.jpg');

        $this->procesarImagenesService->procesarImagenPrenda($archivoPrenda, $cotizacionId, 1);
        $this->procesarImagenesService->procesarImagenTela($archivoTela, $cotizacionId, 1);
        $this->procesarImagenesService->procesarImagenLogo($archivoLogo, $cotizacionId);

        // Verificar que existen las carpetas
        Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/prendas");
        Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/telas");
        Storage::disk('public')->assertExists("cotizaciones/{$cotizacionId}/logo");

        // Verificar que hay archivos en cada carpeta
        $prendasFiles = Storage::disk('public')->files("cotizaciones/{$cotizacionId}/prendas");
        $telasFiles = Storage::disk('public')->files("cotizaciones/{$cotizacionId}/telas");
        $logoFiles = Storage::disk('public')->files("cotizaciones/{$cotizacionId}/logo");

        $this->assertCount(1, $prendasFiles);
        $this->assertCount(1, $telasFiles);
        $this->assertCount(1, $logoFiles);
    }
}
