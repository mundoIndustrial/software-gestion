<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Application\UseCases\Receipts\GetSewingReceiptsUseCase;

class DebugRecibosVistaData extends Command
{
    protected $signature = 'debug:recibos-vista-data';
    protected $description = 'Debuggea qué datos se pasan a la vista de recibos';

    public function handle(GetSewingReceiptsUseCase $useCase)
    {
        $this->info("\n====== DATOS QUE SE PASAN A LA VISTA ======\n");

        // Simular un request
        $request = new Request();
        $request->merge([
            'page' => 1,
            'per_page' => 10
        ]);

        // Ejecutar el use case
        $datos = $useCase->execute($request);

        $this->line("1. ESTRUCTURA DE DATOS:");
        $this->line("   - Total recibos: " . count($datos['recibos']));
        
        if (count($datos['recibos']) > 0) {
            $this->line("\n2. PRIMER RECIBO:");
            $primerRecibo = $datos['recibos'][0];
            
            // Mostrar estructura
            foreach ($primerRecibo as $key => $value) {
                if (is_array($value)) {
                    $this->line("   - {$key}: Array con " . count($value) . " keys");
                    if ($key === 'pedido_info' || $key === 'prenda') {
                        foreach ($value as $subkey => $subvalue) {
                            $display = is_array($subvalue) ? 'Array' : (strlen($subvalue) > 50 ? substr($subvalue, 0, 50) . '...' : $subvalue);
                            $this->line("       - {$subkey}: {$display}");
                        }
                    }
                } else {
                    $display = is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
                    $this->line("   - {$key}: {$display}");
                }
            }

            // Verificar específicamente el area
            $this->line("\n3. VERIFICACIÓN DE CAMPOS CRÍTICOS:");
            if (isset($primerRecibo['area'])) {
                $this->info("   ✓ Campo 'area' presente: {$primerRecibo['area']}");
            } else {
                $this->error("   ❌ Campo 'area' NO presente");
            }

            if (isset($primerRecibo['pedido_info']['area'])) {
                $this->line("   - pedido_info.area: {$primerRecibo['pedido_info']['area']}");
            }

            // Mostrar todos los recibos
            $this->line("\n4. LISTADO DE TODOS LOS RECIBOS:");
            foreach ($datos['recibos'] as $idx => $recibo) {
                $area = $recibo['area'] ?? 'NO TIENE';
                $pedidoArea = $recibo['pedido_info']['area'] ?? 'SIN PEDIDO';
                $this->line("   [{$idx}] Recibo #{$recibo['consecutivo_actual']}: area={$area}, pedido_area={$pedidoArea}");
            }
        }

        $this->line("\n====== FIN DEL ANÁLISIS ======\n");

        return 0;
    }
}
