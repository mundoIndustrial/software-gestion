<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConsecutivoReciboPedido;
use App\Application\UseCases\Receipts\GetSewingReceiptsUseCase;

class DebugRecibosArea extends Command
{
    protected $signature = 'debug:recibos-area {--recibo=36}';
    protected $description = 'Debuggea el campo area de un recibo específico';

    public function handle()
    {
        $numeroRecibo = $this->option('recibo');
        
        $this->info("\n====== ANÁLISIS DEL ÁREA DEL RECIBO {$numeroRecibo} ======\n");

        // 1. Obtener directamente del modelo
        $this->line("1. CONSULTANDO MODELO DIRECTAMENTE:");
        $reciboModelo = ConsecutivoReciboPedido::where('consecutivo_actual', $numeroRecibo)
            ->first();

        if (!$reciboModelo) {
            $this->error("❌ No se encontró recibo con consecutivo {$numeroRecibo}");
            return 1;
        }

        $this->line("   Recibo ID: {$reciboModelo->id}");
        $this->line("   Consecutivo: {$reciboModelo->consecutivo_actual}");
        $this->line("   Área (directo): {$reciboModelo->area}");
        
        // 2. Como array
        $this->line("\n2. CONVERTIDO A ARRAY:");
        $reciboArray = $reciboModelo->toArray();
        
        if (isset($reciboArray['area'])) {
            $this->line("   ✓ Campo 'area' aparece en array: {$reciboArray['area']}");
        } else {
            $this->error("   ❌ Campo 'area' NO aparece en array");
            $this->line("   Campos disponibles: " . json_encode(array_keys($reciboArray)));
        }

        // 3. Listar todos los campos del array
        $this->line("\n3. CAMPOS EN EL ARRAY:");
        foreach ($reciboArray as $key => $value) {
            $display = is_array($value) ? 'Array' : (is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value));
            $this->line("   - {$key}: {$display}");
        }

        // 4. Verificar el modelo por atributos ocultos
        $this->line("\n4. ATRIBUTOS OCULTOS DEL MODELO:");
        $hidden = $reciboModelo->getHidden();
        if (!empty($hidden)) {
            $this->error("   ⚠ Atributos ocultos: " . json_encode($hidden));
        } else {
            $this->line("   ✓ No hay atributos ocultos");
        }

        // 5. Verificar atributos adicionales (appends)
        $this->line("\n5. ATRIBUTOS ADICIONALES (APPENDS):");
        $appends = $reciboModelo->getAppends();
        if (!empty($appends)) {
            $this->line("   Appends: " . json_encode($appends));
        } else {
            $this->line("   ✓ No hay appends");
        }

        $this->line("\n====== FIN DEL ANÁLISIS ======\n");

        return 0;
    }
}
