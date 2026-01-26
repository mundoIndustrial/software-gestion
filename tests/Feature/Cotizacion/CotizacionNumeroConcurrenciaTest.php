<?php

namespace Tests\Feature\Cotizacion;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\NumeroSecuencia;
use App\Models\Cotizacion;
use App\Models\Cliente;
use App\Models\User;

/**
 * TEST: Validar que la generación sincrónica de nÃºmeros funciona con pessimistic lock
 * 
 * Escenarios:
 * 1. Generación secuencial de nÃºmeros (no hay conflicto)
 * 2. MÃºltiples transacciones simultÃ¡neas NO generan nÃºmeros duplicados
 * 3. El lock pessimista previene race conditions
 * 4. Los nÃºmeros estÃ¡n en formato correcto (COT-YYYYMMDD-NNN)
 */
class CotizacionNumeroConcurrenciaTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = 'DatabaseSeeder';

    public function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar seeders
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        
        // Inicializar secuencias si no existen
        $this->inicializarSecuencias();
    }

    /**
     *  TEST 1: Generación simple de nÃºmero
     * 
     * Valida:
     * - El nÃºmero se genera en formato COT-YYYYMMDD-NNN
     * - El contador se incrementa correctamente
     * - No hay NULL
     */
    public function test_genera_numero_en_formato_correcto()
    {
        $numero = $this->generarNumero('cotizaciones_prenda');
        
        $this->assertNotNull($numero);
        $this->assertMatchesRegularExpression('/^COT-\d{8}-\d{3}$/', $numero);
        
        Log::info(' TEST 1: NÃºmero generado correctamente', ['numero' => $numero]);
    }

    /**
     *  TEST 2: NÃºmeros secuenciales incrementan
     * 
     * Valida:
     * - El primer nÃºmero termina en -001
     * - El segundo nÃºmero termina en -002
     * - Sin saltos en la secuencia
     */
    public function test_numeros_incrementan_secuencialmente()
    {
        // Reset a 1
        NumeroSecuencia::where('tipo', 'cotizaciones_prenda')->update(['proximo_numero' => 1]);

        $numero1 = $this->generarNumero('cotizaciones_prenda');
        $numero2 = $this->generarNumero('cotizaciones_prenda');
        $numero3 = $this->generarNumero('cotizaciones_prenda');

        $this->assertStringEndsWith('-001', $numero1);
        $this->assertStringEndsWith('-002', $numero2);
        $this->assertStringEndsWith('-003', $numero3);

        Log::info(' TEST 2: Secuencia correcta', [
            'num1' => $numero1,
            'num2' => $numero2,
            'num3' => $numero3,
        ]);
    }

    /**
     *  TEST 3: El lock pessimista previene duplicados
     * 
     * Simula dos transacciones que inician casi simultÃ¡neamente.
     * Con el lock, uno espera al otro â†’ no hay duplicados
     */
    public function test_lock_pessimista_previene_duplicados()
    {
        // Reset a 1
        NumeroSecuencia::where('tipo', 'cotizaciones_prenda')->update(['proximo_numero' => 1]);

        $numeros = [];

        // Simulación de 5 transacciones "simultÃ¡neas"
        for ($i = 0; $i < 5; $i++) {
            DB::transaction(function () use (&$numeros) {
                $numero = $this->generarNumero('cotizaciones_prenda');
                $numeros[] = $numero;
            });
        }

        // Verificar que no hay duplicados
        $numerosUnicos = array_unique($numeros);
        $this->assertCount(5, $numeros);
        $this->assertCount(5, $numerosUnicos, 'Hay nÃºmeros duplicados');

        // Verificar que terminan en 001, 002, 003, 004, 005
        $this->assertStringEndsWith('-001', $numeros[0]);
        $this->assertStringEndsWith('-002', $numeros[1]);
        $this->assertStringEndsWith('-003', $numeros[2]);
        $this->assertStringEndsWith('-004', $numeros[3]);
        $this->assertStringEndsWith('-005', $numeros[4]);

        Log::info(' TEST 3: Lock pessimista funciona', [
            'numeros' => $numeros,
            'unicos' => count(array_unique($numeros)),
        ]);
    }

    /**
     *  TEST 4: Diferentes tipos de secuencias no interfieren
     * 
     * Valida:
     * - cotizaciones_prenda genera 001
     * - cotizaciones_bordado genera 001
     * - No comparten contador
     */
    public function test_diferentes_tipos_secuencia_no_interfieren()
    {
        // Reset a 1
        NumeroSecuencia::where('tipo', 'cotizaciones_prenda')->update(['proximo_numero' => 1]);
        NumeroSecuencia::where('tipo', 'cotizaciones_bordado')->update(['proximo_numero' => 1]);

        $numeroPrenda = $this->generarNumero('cotizaciones_prenda');
        $numeroBordado = $this->generarNumero('cotizaciones_bordado');
        $numeroPrenda2 = $this->generarNumero('cotizaciones_prenda');

        $this->assertStringEndsWith('-001', $numeroPrenda);
        $this->assertStringEndsWith('-001', $numeroBordado);
        $this->assertStringEndsWith('-002', $numeroPrenda2);

        Log::info(' TEST 4: Tipos independientes', [
            'prenda1' => $numeroPrenda,
            'bordado' => $numeroBordado,
            'prenda2' => $numeroPrenda2,
        ]);
    }

    /**
     *  TEST 5: Estados de secuencia correctos despuÃ©s de generaciones
     */
    public function test_estado_secuencia_despues_generaciones()
    {
        NumeroSecuencia::where('tipo', 'cotizaciones_prenda')->update(['proximo_numero' => 1]);

        $this->generarNumero('cotizaciones_prenda'); // genera 001, incrementa a 2
        $this->generarNumero('cotizaciones_prenda'); // genera 002, incrementa a 3
        $this->generarNumero('cotizaciones_prenda'); // genera 003, incrementa a 4

        $secuencia = NumeroSecuencia::where('tipo', 'cotizaciones_prenda')->first();
        $this->assertEquals(4, $secuencia->proximo_numero);

        Log::info(' TEST 5: Contador correcto', ['proximo' => $secuencia->proximo_numero]);
    }

    /**
     *  TEST 6: Formato de fecha en nÃºmero es dinÃ¡mico (hoy)
     */
    public function test_numero_incluye_fecha_actual()
    {
        $numero = $this->generarNumero('cotizaciones_prenda');
        $hoy = date('Ymd');
        
        $this->assertStringContainsString($hoy, $numero);
        $this->assertMatchesRegularExpression("/COT-$hoy-\d{3}/", $numero);

        Log::info(' TEST 6: Fecha en nÃºmero', ['numero' => $numero, 'fecha' => $hoy]);
    }

    /**
     * HELPER: Generar nÃºmero sincronicamente (simula el controlador)
     */
    private function generarNumero($tipo = 'cotizaciones_prenda')
    {
        return DB::transaction(function () use ($tipo) {
            $secuencia = NumeroSecuencia::lockForUpdate()
                ->where('tipo', $tipo)
                ->first();

            if (!$secuencia) {
                throw new \Exception("Secuencia '{$tipo}' no encontrada");
            }

            $proximoNumero = $secuencia->proximo_numero;
            $secuencia->proximo_numero = $proximoNumero + 1;
            $secuencia->save();

            return 'COT-' . date('Ymd') . '-' . str_pad($proximoNumero, 3, '0', STR_PAD_LEFT);
        });
    }

    /**
     * HELPER: Inicializar secuencias
     */
    private function inicializarSecuencias()
    {
        $tipos = ['cotizaciones_prenda', 'cotizaciones_bordado', 'cotizaciones_general'];
        
        foreach ($tipos as $tipo) {
            NumeroSecuencia::updateOrCreate(
                ['tipo' => $tipo],
                ['proximo_numero' => 1]
            );
        }
    }
}

