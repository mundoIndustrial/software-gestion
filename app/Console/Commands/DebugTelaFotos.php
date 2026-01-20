<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Models\PrendaCot;
use Illuminate\Support\Facades\DB;

class DebugTelaFotos extends Command
{
    protected $signature = 'debug:tela-fotos {cotizacion_id?}';
    protected $description = 'Debug: Verificar fotos de telas en cotización';

    public function handle()
    {
        $cotId = $this->argument('cotizacion_id') ?? 1;
        
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("VERIFICANDO FOTOS DE TELAS - COT ID: $cotId");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        // Obtener cotización
        $cotizacion = Cotizacion::find($cotId);
        if (!$cotizacion) {
            $this->error("Cotización $cotId no encontrada");
            return;
        }

        $this->info(" Cotización encontrada: {$cotizacion->numero_cotizacion}\n");

        // Obtener prendas
        $prendas = PrendaCot::where('cotizacion_id', $cotId)
            ->with(['telaFotos'])
            ->get();

        $this->info("Prendas en cotización: {$prendas->count()}\n");

        foreach ($prendas as $prenda) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line(" Prenda ID: {$prenda->id} - {$prenda->nombre_producto}");
            
            $telaFotos = $prenda->telaFotos()->get();
            $this->line("   Fotos de telas: {$telaFotos->count()}");
            
            foreach ($telaFotos as $foto) {
                $this->line("     - ID: {$foto->id}");
                $this->line("       Original: {$foto->ruta_original}");
                $this->line("       WebP: {$foto->ruta_webp}");
                $this->line("       Orden: {$foto->orden}");
            }
        }

        // Verificar BD directamente
        $this->info("\n═══════════════════════════════════════════════════════════════");
        $this->info("CONSULTA DIRECTA A BD");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        $fotosDirectas = DB::table('prenda_tela_fotos_cot')
            ->whereIn('prenda_cot_id', function($q) use ($cotId) {
                $q->select('id')->from('prendas_cot')->where('cotizacion_id', $cotId);
            })
            ->get();

        $this->line("Fotos de telas encontradas en BD: {$fotosDirectas->count()}");
        foreach ($fotosDirectas as $foto) {
            $this->line("  - ID: {$foto->id}, Prenda: {$foto->prenda_cot_id}, Ruta: {$foto->ruta_original}");
        }
    }
}
