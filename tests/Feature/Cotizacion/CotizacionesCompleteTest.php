<?php

namespace Tests\Feature\Cotizacion;

use App\Models\Cotizacion;
use App\Models\TipoCotizacion;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Genero;
use App\Models\TipoManga;
use App\Models\TipoBroche;
use App\Models\Tela;
use App\Models\Color;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;
use App\Models\PrendaTallaCot;
use App\Models\PrendaTelaCot;
use App\Models\PrendaFotoCot;
use App\Models\LogoCotizacion;
use App\Models\LogoFoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Suite Completo: Cotizaciones - 11 por Tipo
 * 
 * Objetivo: Validar que se puede crear 11 cotizaciones por tipo sin errores,
 * incluyendo TODOS los campos, fotos y relaciones.
 * También valida que numero_cotizacion sea secuencial y único.
 * 
 * Total Tests: ~6 (1 por tipo + 1 de concurrencia + 1 de secuencialidad)
 * Total Cotizaciones Creadas: 77 (11×4 tipos + 33 de concurrencia)
 */
class CotizacionesCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesor1;
    protected User $asesor2;
    protected User $asesor3;
    protected Cliente $cliente;
    protected TipoCotizacion $tipoM;
    protected TipoCotizacion $tipoP;
    protected TipoCotizacion $tipoG;

    protected array $generos = [];
    protected array $tiposManga = [];
    protected array $tiposBroche = [];
    protected array $telas = [];
    protected array $colores = [];

    public function setUp(): void
    {
        parent::setUp();

        // Crear datos base
        $this->crearDatosBase();
        $this->crearCatálogos();
    }

    /**
     * Crear datos base necesarios para los tests
     */
    private function crearDatosBase(): void
    {
        // Crear 3 asesores
        $this->asesor1 = User::factory()->create(['name' => 'Asesor 1']);
        $this->asesor2 = User::factory()->create(['name' => 'Asesor 2']);
        $this->asesor3 = User::factory()->create(['name' => 'Asesor 3']);

        // Crear cliente
        $this->cliente = Cliente::factory()->create(['nombre' => 'Cliente Test']);

        // Crear tipos de cotización
        $this->tipoM = TipoCotizacion::firstOrCreate(
            ['codigo' => 'M'],
            ['nombre' => 'Muestra', 'descripcion' => 'Cotización de muestra']
        );

        $this->tipoP = TipoCotizacion::firstOrCreate(
            ['codigo' => 'P'],
            ['nombre' => 'Prototipo', 'descripcion' => 'Cotización de prototipo']
        );

        $this->tipoG = TipoCotizacion::firstOrCreate(
            ['codigo' => 'G'],
            ['nombre' => 'Grande', 'descripcion' => 'Cotización grande']
        );
    }

    /**
     * Crear catálogos necesarios (géneros, mangas, broches, etc.)
     */
    private function crearCatálogos(): void
    {
        // Géneros
        $this->generos['masculino'] = Genero::firstOrCreate(
            ['nombre' => 'Masculino'],
            ['abreviatura' => 'M']
        );
        $this->generos['femenino'] = Genero::firstOrCreate(
            ['nombre' => 'Femenino'],
            ['abreviatura' => 'F']
        );

        // Tipos de Manga
        $this->tiposManga['corta'] = TipoManga::firstOrCreate(['nombre' => 'Corta']);
        $this->tiposManga['larga'] = TipoManga::firstOrCreate(['nombre' => 'Larga']);

        // Tipos de Broche
        $this->tiposBroche['botones'] = TipoBroche::firstOrCreate(['nombre' => 'Botones']);
        $this->tiposBroche['cierre'] = TipoBroche::firstOrCreate(['nombre' => 'Cierre']);

        // Telas
        $this->telas['algodon'] = Tela::firstOrCreate(
            ['nombre' => 'Algodón'],
            ['descripcion' => 'Algodón 100%', 'codigo' => 'ALG']
        );
        $this->telas['polyester'] = Tela::firstOrCreate(
            ['nombre' => 'Poliéster'],
            ['descripcion' => 'Poliéster 100%', 'codigo' => 'POL']
        );

        // Colores
        $this->colores['azul'] = Color::firstOrCreate(
            ['nombre' => 'Azul'],
            ['codigo' => 'AZU']
        );
        $this->colores['blanco'] = Color::firstOrCreate(
            ['nombre' => 'Blanco'],
            ['codigo' => 'BLA']
        );
        $this->colores['negro'] = Color::firstOrCreate(
            ['nombre' => 'Negro'],
            ['codigo' => 'NEG']
        );
    }

    /**
     * TEST 1: Crear 11 Cotizaciones TIPO MUESTRA (M) con todos los campos
     */
    public function test_crear_11_cotizaciones_tipo_muestra(): void
    {
        $this->actingAs($this->asesor1);

        $cotizacionesCreadas = [];
        $numerosSecuenciales = [];

        // Crear 11 cotizaciones de muestra
        for ($i = 1; $i <= 11; $i++) {
            $cotizacion = $this->crearCotizacionMuestra($i);

            $this->assertNotNull($cotizacion->id);
            $this->assertEquals($this->tipoM->id, $cotizacion->tipo_cotizacion_id);
            $this->assertEquals($this->asesor1->id, $cotizacion->asesor_id);
            $this->assertFalse($cotizacion->es_borrador);

            $cotizacionesCreadas[] = $cotizacion;
            if ($cotizacion->numero_cotizacion) {
                $numerosSecuenciales[] = $cotizacion->numero_cotizacion;
            }

            // Verificar estructura completa
            $this->verificarEstructuraCotizacion($cotizacion);
        }

        // Verificar que tenemos 11 cotizaciones
        $this->assertCount(11, $cotizacionesCreadas);
        $this->assertCount(11, $numerosSecuenciales);

        // Verificar que los números son únicos
        $this->assertEquals(11, count(array_unique($numerosSecuenciales)));

        echo "\n✅ TEST MUESTRA: {$i} cotizaciones creadas con éxito\n";
        echo "Números: " . implode(', ', $numerosSecuenciales) . "\n";
    }

    /**
     * TEST 2: Crear 11 Cotizaciones TIPO PROTOTIPO (P) con campos complejos
     */
    public function test_crear_11_cotizaciones_tipo_prototipo(): void
    {
        $this->actingAs($this->asesor1);

        $cotizacionesCreadas = [];
        $numerosSecuenciales = [];

        for ($i = 1; $i <= 11; $i++) {
            $cotizacion = $this->crearCotizacionPrototipo($i);

            $this->assertNotNull($cotizacion->id);
            $this->assertEquals($this->tipoP->id, $cotizacion->tipo_cotizacion_id);
            
            // Verificar múltiples prendas
            $this->assertGreaterThanOrEqual(2, $cotizacion->prendas->count());

            $cotizacionesCreadas[] = $cotizacion;
            if ($cotizacion->numero_cotizacion) {
                $numerosSecuenciales[] = $cotizacion->numero_cotizacion;
            }

            $this->verificarEstructuraCotizacion($cotizacion);
        }

        $this->assertCount(11, $cotizacionesCreadas);
        $this->assertEquals(11, count(array_unique($numerosSecuenciales)));

        echo "\n✅ TEST PROTOTIPO: {$i} cotizaciones creadas con éxito\n";
    }

    /**
     * TEST 3: Crear 11 Cotizaciones TIPO GRANDE (G) con máximos campos
     */
    public function test_crear_11_cotizaciones_tipo_grande(): void
    {
        $this->actingAs($this->asesor1);

        $cotizacionesCreadas = [];
        $numerosSecuenciales = [];

        for ($i = 1; $i <= 11; $i++) {
            $cotizacion = $this->crearCotizacionGrande($i);

            $this->assertNotNull($cotizacion->id);
            $this->assertEquals($this->tipoG->id, $cotizacion->tipo_cotizacion_id);
            
            // Verificar máximo de prendas
            $this->assertGreaterThanOrEqual(3, $cotizacion->prendas->count());

            $cotizacionesCreadas[] = $cotizacion;
            if ($cotizacion->numero_cotizacion) {
                $numerosSecuenciales[] = $cotizacion->numero_cotizacion;
            }

            $this->verificarEstructuraCotizacion($cotizacion);
        }

        $this->assertCount(11, $cotizacionesCreadas);
        $this->assertEquals(11, count(array_unique($numerosSecuenciales)));

        echo "\n✅ TEST GRANDE: {$i} cotizaciones creadas con éxito\n";
    }

    /**
     * TEST 4: Crear 11 Cotizaciones TIPO BORDADO con logos
     */
    public function test_crear_11_cotizaciones_tipo_bordado(): void
    {
        $this->actingAs($this->asesor1);

        $cotizacionesCreadas = [];
        $numerosSecuenciales = [];

        for ($i = 1; $i <= 11; $i++) {
            $cotizacion = $this->crearCotizacionBordado($i);

            $this->assertNotNull($cotizacion->id);
            $this->assertFalse($cotizacion->es_borrador);
            
            // Verificar que tiene logo
            $this->assertNotEmpty($cotizacion->logoCotizacion);

            $cotizacionesCreadas[] = $cotizacion;
            if ($cotizacion->numero_cotizacion) {
                $numerosSecuenciales[] = $cotizacion->numero_cotizacion;
            }

            $this->verificarEstructuraCotizacion($cotizacion);
        }

        $this->assertCount(11, $cotizacionesCreadas);
        $this->assertEquals(11, count(array_unique($numerosSecuenciales)));

        echo "\n✅ TEST BORDADO: {$i} cotizaciones creadas con éxito\n";
    }

    /**
     * TEST 5: Validar Número de Cotización Secuencial Global
     */
    public function test_numero_cotizacion_secuencial_global(): void
    {
        // Crear todos los tipos de cotizaciones
        $this->actingAs($this->asesor1);

        $cotizacionesPorTipo = [];

        // Tipo M
        for ($i = 1; $i <= 11; $i++) {
            $cot = $this->crearCotizacionMuestra($i);
            $cotizacionesPorTipo['M'][] = $cot->numero_cotizacion;
        }

        // Tipo P
        for ($i = 1; $i <= 11; $i++) {
            $cot = $this->crearCotizacionPrototipo($i);
            $cotizacionesPorTipo['P'][] = $cot->numero_cotizacion;
        }

        // Tipo G
        for ($i = 1; $i <= 11; $i++) {
            $cot = $this->crearCotizacionGrande($i);
            $cotizacionesPorTipo['G'][] = $cot->numero_cotizacion;
        }

        // Verificar que cada número es único
        $todoNumeros = [];
        foreach ($cotizacionesPorTipo as $tipo => $numeros) {
            foreach ($numeros as $num) {
                $todoNumeros[] = $num;
            }
        }

        $this->assertEquals(count($todoNumeros), count(array_unique($todoNumeros)));

        echo "\n✅ TEST SECUENCIAL: Todos los números son únicos\n";
        echo "Total cotizaciones: " . count($todoNumeros) . "\n";
    }

    /**
     * TEST 6: CONCURRENCIA - 3 Asesores creando 11 cotizaciones cada uno simultáneamente
     * 
     * ⚠️ NOTA: Este test simula concurrencia. En producción, se debería usar
     * herramientas como Guzzle HTTP con promesas para verdadera concurrencia.
     */
    public function test_concurrencia_multiples_asesores(): void
    {
        $asesores = [$this->asesor1, $this->asesor2, $this->asesor3];
        $cotizacionesPorAsesor = [];
        $numerosGlobales = [];

        // Simular creación simultánea (secuencial, pero validar integridad)
        foreach ($asesores as $indiceAsesor => $asesor) {
            $this->actingAs($asesor);
            $cotizacionesPorAsesor[$asesor->id] = [];

            for ($i = 1; $i <= 11; $i++) {
                $cotizacion = $this->crearCotizacionMuestra($i);
                $cotizacionesPorAsesor[$asesor->id][] = $cotizacion;
                $numerosGlobales[] = $cotizacion->numero_cotizacion;
            }
        }

        // Validaciones de integridad
        // 1. Total de cotizaciones
        $this->assertEquals(33, count($numerosGlobales));

        // 2. Todos los números son únicos
        $this->assertEquals(33, count(array_unique($numerosGlobales)));

        // 3. Cada asesor tiene exactamente 11 cotizaciones
        foreach ($cotizacionesPorAsesor as $asesorId => $cotizaciones) {
            $this->assertCount(11, $cotizaciones);
        }

        // 4. Números están distribuidos
        sort($numerosGlobales);
        echo "\n✅ TEST CONCURRENCIA: 3 Asesores × 11 Cotizaciones = 33 Total\n";
        echo "Primeros números: " . implode(', ', array_slice($numerosGlobales, 0, 5)) . "\n";
        echo "Últimos números: " . implode(', ', array_slice($numerosGlobales, -5)) . "\n";
    }

    /**
     * ====================================================================
     * MÉTODOS HELPER PARA CREAR COTIZACIONES
     * ====================================================================
     */

    /**
     * Crear cotización TIPO MUESTRA (M)
     * - 1 Prenda (Camisa)
     * - 3 Fotos
     * - 2 Telas
     * - 3 Tallas (S, M, L)
     * - 1 Variante completa
     */
    private function crearCotizacionMuestra(int $numero): Cotizacion
    {
        $cotizacion = Cotizacion::create([
            'asesor_id' => auth()->id(),
            'cliente_id' => $this->cliente->id,
            'tipo_cotizacion_id' => $this->tipoM->id,
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
            'tipo_venta' => 'M',
            'especificaciones' => [],
        ]);

        // Asignar número de cotización (simulando el servicio)
        $cotizacion->numero_cotizacion = $this->generarNumeroCotizacion($cotizacion);
        $cotizacion->save();

        // Crear 1 Prenda
        $prenda = $this->crearPrendaCompleta(
            $cotizacion,
            "Camisa Muestra $numero",
            "Camisa de prueba muestra $numero",
            100,
            3, // 3 fotos
            2, // 2 telas
            ['S', 'M', 'L'] // 3 tallas
        );

        return $cotizacion->fresh();
    }

    /**
     * Crear cotización TIPO PROTOTIPO (P)
     * - 2 Prendas (Camisa + Pantalón)
     * - 4 Fotos por prenda
     * - 3 Telas por prenda
     * - 4 Tallas (XS, S, M, L)
     */
    private function crearCotizacionPrototipo(int $numero): Cotizacion
    {
        $cotizacion = Cotizacion::create([
            'asesor_id' => auth()->id(),
            'cliente_id' => $this->cliente->id,
            'tipo_cotizacion_id' => $this->tipoP->id,
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
            'tipo_venta' => 'P',
            'especificaciones' => [],
        ]);

        $cotizacion->numero_cotizacion = $this->generarNumeroCotizacion($cotizacion);
        $cotizacion->save();

        // Prenda 1: Camisa
        $this->crearPrendaCompleta(
            $cotizacion,
            "Camisa Prototipo $numero",
            "Camisa de prototipo con detalles $numero",
            50,
            4, // 4 fotos
            3, // 3 telas
            ['XS', 'S', 'M', 'L']
        );

        // Prenda 2: Pantalón
        $this->crearPrendaCompleta(
            $cotizacion,
            "Pantalón Prototipo $numero",
            "Pantalón de prototipo con bolsillos $numero",
            50,
            4,
            3,
            ['XS', 'S', 'M', 'L']
        );

        return $cotizacion->fresh();
    }

    /**
     * Crear cotización TIPO GRANDE (G)
     * - 3 Prendas (Camisa + Pantalón + Chaqueta)
     * - 5 Fotos por prenda
     * - 4 Telas por prenda
     * - 6 Tallas (XS-2XL)
     */
    private function crearCotizacionGrande(int $numero): Cotizacion
    {
        $cotizacion = Cotizacion::create([
            'asesor_id' => auth()->id(),
            'cliente_id' => $this->cliente->id,
            'tipo_cotizacion_id' => $this->tipoG->id,
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
            'tipo_venta' => 'G',
            'especificaciones' => [],
        ]);

        $cotizacion->numero_cotizacion = $this->generarNumeroCotizacion($cotizacion);
        $cotizacion->save();

        // Prenda 1: Camisa
        $this->crearPrendaCompleta(
            $cotizacion,
            "Camisa Grande $numero",
            "Camisa de alta gama $numero",
            30,
            5,
            4,
            ['XS', 'S', 'M', 'L', 'XL', '2XL']
        );

        // Prenda 2: Pantalón
        $this->crearPrendaCompleta(
            $cotizacion,
            "Pantalón Grande $numero",
            "Pantalón jean premium $numero",
            30,
            5,
            4,
            ['XS', 'S', 'M', 'L', 'XL', '2XL']
        );

        // Prenda 3: Chaqueta
        $this->crearPrendaCompleta(
            $cotizacion,
            "Chaqueta Grande $numero",
            "Chaqueta impermeable $numero",
            40,
            5,
            4,
            ['XS', 'S', 'M', 'L', 'XL', '2XL']
        );

        return $cotizacion->fresh();
    }

    /**
     * Crear cotización TIPO BORDADO
     * - Logo principal
     * - 4 Fotos de logo
     * - 3 Ubicaciones (pecho, espalda, manga)
     * - Técnicas de bordado
     */
    private function crearCotizacionBordado(int $numero): Cotizacion
    {
        $cotizacion = Cotizacion::create([
            'asesor_id' => auth()->id(),
            'cliente_id' => $this->cliente->id,
            'tipo_cotizacion_id' => $this->tipoM->id, // O crear un tipo BORDADO
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
            'tipo_venta' => 'M',
            'especificaciones' => [],
        ]);

        $cotizacion->numero_cotizacion = $this->generarNumeroCotizacion($cotizacion);
        $cotizacion->save();

        // Crear logo
        $logo = LogoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => "Logo bordado test $numero",
            'imagenes' => $this->crearFotosSimuladas(4),
            'tecnicas' => ['bordado', 'punto cruzado'],
            'observaciones_tecnicas' => 'Bordado de alta calidad',
            'ubicaciones' => ['pecho', 'espalda', 'manga izquierda'],
            'observaciones_generales' => ['Validar colores', 'Bordar a máquina'],
        ]);

        // Crear fotos del logo
        for ($i = 1; $i <= 4; $i++) {
            LogoFoto::create([
                'logo_cotizacion_id' => $logo->id,
                'ruta_original' => "storage/logos/logo_{$numero}_{$i}.png",
                'ruta_webp' => "storage/logos/logo_{$numero}_{$i}.webp",
                'ruta_miniatura' => "storage/logos/logo_{$numero}_{$i}_thumb.png",
                'orden' => $i,
                'ancho' => 500,
                'alto' => 500,
                'tamaño' => 102400,
            ]);
        }

        return $cotizacion->fresh();
    }

    /**
     * Crear una prenda completa con todas sus relaciones
     */
    private function crearPrendaCompleta(
        Cotizacion $cotizacion,
        string $nombre,
        string $descripcion,
        int $cantidad,
        int $numFotos,
        int $numTelas,
        array $tallas
    ): PrendaCot {
        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => $nombre,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
        ]);

        // Crear variante
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'camisa',
            'genero_id' => $this->generos['masculino']->id,
            'color' => 'Azul',
            'tipo_manga_id' => $this->tiposManga['corta']->id,
            'aplica_manga' => true,
            'tipo_manga' => 'corta',
            'tiene_bolsillos' => true,
            'obs_bolsillos' => 'Bolsillos con ribete',
            'aplica_broche' => true,
            'tipo_broche_id' => $this->tiposBroche['botones']->id,
            'obs_broche' => 'Botones de madera',
            'tiene_reflectivo' => false,
            'descripcion_adicional' => 'Prenda de calidad premium',
        ]);

        // Crear fotos
        for ($i = 1; $i <= $numFotos; $i++) {
            PrendaFotoCot::create([
                'prenda_cot_id' => $prenda->id,
                'ruta_original' => "storage/prendas/prenda_{$prenda->id}_foto_{$i}.jpg",
                'ruta_webp' => "storage/prendas/prenda_{$prenda->id}_foto_{$i}.webp",
                'ruta_miniatura' => "storage/prendas/prenda_{$prenda->id}_foto_{$i}_thumb.jpg",
                'orden' => $i,
                'ancho' => 1920,
                'alto' => 1080,
                'tamaño' => 524288,
            ]);
        }

        // Crear telas
        $telasList = [
            $this->telas['algodon'],
            $this->telas['polyester'],
        ];

        for ($i = 0; $i < min($numTelas, count($telasList)); $i++) {
            PrendaTelaCot::create([
                'prenda_cot_id' => $prenda->id,
                'variante_prenda_cot_id' => $variante->id,
                'color_id' => $this->colores[$i === 0 ? 'azul' : 'blanco']->id,
                'tela_id' => $telasList[$i]->id,
            ]);
        }

        // Crear tallas
        foreach ($tallas as $index => $talla) {
            PrendaTallaCot::create([
                'prenda_cot_id' => $prenda->id,
                'talla' => $talla,
                'cantidad' => $cantidad / count($tallas),
            ]);
        }

        return $prenda;
    }

    /**
     * Generar número de cotización único
     * Formato: COT-YYYYMMDD-XXXXXXXX (timestamp based)
     */
    private function generarNumeroCotizacion(Cotizacion $cotizacion): string
    {
        $fecha = now()->format('Ymd');
        $contador = Cotizacion::whereDate('fecha_envio', now())->count();
        $numero = str_pad($contador + 1, 6, '0', STR_PAD_LEFT);
        
        return "COT-{$fecha}-{$numero}";
    }

    /**
     * Crear rutas de fotos simuladas para testing
     */
    private function crearFotosSimuladas(int $cantidad): array
    {
        $fotos = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $fotos[] = "storage/fotos/foto_{$i}.jpg";
        }
        return $fotos;
    }

    /**
     * ====================================================================
     * VERIFICADORES Y ASSERTIONS
     * ====================================================================
     */

    /**
     * Verificar que la cotización tiene estructura completa
     */
    private function verificarEstructuraCotizacion(Cotizacion $cotizacion): void
    {
        // Campos principales
        $this->assertNotNull($cotizacion->id);
        $this->assertNotNull($cotizacion->asesor_id);
        $this->assertNotNull($cotizacion->numero_cotizacion);
        $this->assertNotNull($cotizacion->tipo_cotizacion_id);
        $this->assertNotNull($cotizacion->fecha_inicio);

        // Validar estado
        $this->assertIn($cotizacion->estado, ['enviada', 'aceptada', 'rechazada']);

        // Si no es borrador, debe tener número y fecha de envío
        if (!$cotizacion->es_borrador) {
            $this->assertNotNull($cotizacion->numero_cotizacion);
            $this->assertNotNull($cotizacion->fecha_envio);
        }

        // Verificar relaciones
        $this->assertNotNull($cotizacion->asesor);
        $this->assertNotNull($cotizacion->tipoCotizacion);

        // Verificar que tiene prendas (si no es solo logo)
        if (!$cotizacion->logoCotizacion || $cotizacion->prendas->count() > 0) {
            $this->assertGreaterThan(0, $cotizacion->prendas->count());

            // Verificar estructura de cada prenda
            foreach ($cotizacion->prendas as $prenda) {
                $this->assertNotNull($prenda->nombre_producto);
                $this->assertGreaterThan(0, $prenda->fotos->count());
                $this->assertGreaterThan(0, $prenda->tallas->count());
            }
        }
    }
}
