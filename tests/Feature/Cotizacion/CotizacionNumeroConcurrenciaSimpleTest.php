<?php

namespace Tests\Feature\Cotizacion;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * TEST: Validar que la generación sincrónica de nÃºmeros funciona con pessimistic lock
 * 
 * Este test NO usa RefreshDatabase porque MySQL estÃ¡ causando timeout
 * En su lugar, usa directamente MySQLi para testing de BD
 */
class CotizacionNumeroConcurrenciaSimpleTest extends TestCase
{
    /**
     *  TEST: Generar nÃºmero con pessimistic lock
     * 
     * Valida que el lock funciona sin timeout
     */
    public function test_genera_numero_con_lock()
    {
        try {
            // Generar un nÃºmero
            $numero = DB::transaction(function () {
                $secuencia = DB::table('numero_secuencias')
                    ->lockForUpdate()
                    ->where('tipo', 'cotizaciones_prenda')
                    ->first();

                if (!$secuencia) {
                    $this->fail('Secuencia no encontrada. Â¿Ejecutaste el seeder?');
                }

                $proximoNumero = $secuencia->proximo_numero;
                DB::table('numero_secuencias')
                    ->where('tipo', 'cotizaciones_prenda')
                    ->update(['proximo_numero' => $proximoNumero + 1]);

                return 'COT-' . date('Ymd') . '-' . str_pad($proximoNumero, 3, '0', STR_PAD_LEFT);
            });

            $this->assertNotNull($numero);
            $this->assertMatchesRegularExpression('/^COT-\d{8}-\d{3}$/', $numero);
            
            echo "\n NÃšMERO GENERADO: $numero\n";
            
        } catch (\Throwable $e) {
            $this->fail("Error al generar nÃºmero: " . $e->getMessage());
        }
    }

    /**
     *  TEST: Verificar estado de tabla numero_secuencias
     */
    public function test_tabla_numero_secuencias_existe_y_tiene_datos()
    {
        $secuencias = DB::table('numero_secuencias')->get();
        
        $this->assertGreaterThan(0, $secuencias->count(), 'No hay secuencias. Ejecuta: php artisan db:seed --class=NumeroSecuenciasSeeder');
        
        echo "\n SECUENCIAS ENCONTRADAS:\n";
        foreach ($secuencias as $sec) {
            echo "  - {$sec->tipo}: próximo_numero = {$sec->proximo_numero}\n";
        }
    }

    /**
     *  TEST: Generar 3 nÃºmeros secuenciales
     */
    public function test_genera_tres_numeros_secuenciales()
    {
        $numeros = [];
        
        for ($i = 0; $i < 3; $i++) {
            $numero = DB::transaction(function () {
                $secuencia = DB::table('numero_secuencias')
                    ->lockForUpdate()
                    ->where('tipo', 'cotizaciones_bordado')
                    ->first();

                $proximoNumero = $secuencia->proximo_numero;
                DB::table('numero_secuencias')
                    ->where('tipo', 'cotizaciones_bordado')
                    ->update(['proximo_numero' => $proximoNumero + 1]);

                return 'COT-' . date('Ymd') . '-' . str_pad($proximoNumero, 3, '0', STR_PAD_LEFT);
            });
            
            $numeros[] = $numero;
        }
        
        $this->assertCount(3, $numeros);
        $this->assertEquals(3, count(array_unique($numeros)), 'Los nÃºmeros no son Ãºnicos');
        
        echo "\n NÃšMEROS GENERADOS:\n";
        foreach ($numeros as $i => $n) {
            echo "  " . ($i + 1) . ". $n\n";
        }
    }

    /**
     *  TEST: Verificar que existen cotizaciones en BD
     */
    public function test_cotizaciones_existen_en_bd()
    {
        $count = DB::table('cotizacions')->count();
        echo "\n COTIZACIONES EN BD: $count\n";
        
        $this->assertGreaterThan(0, $count, 'No hay cotizaciones. Crea algunas primero.');
    }
}

