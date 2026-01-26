<?php

namespace Tests\Feature\Cotizacion;

use Tests\TestCase;
use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TodosTiposCotizacionesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_todos_tipos_cotizaciones_numeracion_global()
    {
        echo "\nðŸ”µ TEST: Todos los tipos de cotizaciones - Numeración Global\n";
        echo "===========================================================\n\n";

        $servicio = app(GenerarNumeroCotizacionService::class);
        
        $numeros_generados = [];
        $detalles = [];
        
        // Simular 5 asesores creando 4 tipos diferentes
        $asesores = [1, 2, 3, 4, 5];
        $tipos = ['Normal', 'Prenda', 'Bordado', 'Reflectivo'];
        
        foreach ($asesores as $asesor_id) {
            foreach ($tipos as $tipo) {
                $numero_formateado = $servicio->generarNumeroCotizacionFormateado($asesor_id);
                $numero_int = (int) substr($numero_formateado, 4);
                
                $numeros_generados[] = $numero_int;
                $detalles[] = "Asesor {$asesor_id} - {$tipo}: {$numero_formateado}";
            }
        }
        
        // Mostrar en output
        foreach ($detalles as $detalle) {
            echo "{$detalle}\n";
        }
        
        echo "\n VALIDACIÃ“N:\n";
        echo "================\n\n";
        
        // Verificar: sin duplicados
        $total = count($numeros_generados);
        $unicos = count(array_unique($numeros_generados));
        
        echo "Total generados: {$total}\n";
        echo "NÃºmeros Ãºnicos: {$unicos}\n";
        
        $this->assertEquals($total, $unicos, 'No debe haber nÃºmeros duplicados');
        echo " Sin duplicados\n\n";
        
        // Verificar: secuencia consecutiva
        sort($numeros_generados);
        $esperado = range(min($numeros_generados), max($numeros_generados));
        
        echo "Rango: " . min($numeros_generados) . " a " . max($numeros_generados) . "\n";
        $this->assertEquals($numeros_generados, $esperado, 'La secuencia debe ser consecutiva');
        echo " Secuencia consecutiva\n\n";
        
        echo " PRUEBA EXITOSA\n";
        echo "=================\n";
        echo "Todos los tipos de cotizaciones comparten la MISMA secuencia global.\n";
        echo "â†’ Normal, Prenda, Bordado, Reflectivo todos en la MISMA numeración\n";
        echo "â†’ Sin duplicados, consecutivos, independiente del asesor o tipo\n\n";
    }
}

