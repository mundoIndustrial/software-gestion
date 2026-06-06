<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidosProcesosPrendaTalla;

class DebugSobremedida extends Command
{
    protected $signature = 'debug:sobremedida {--prenda_id=}';
    protected $description = 'Revisa los datos de sobremedida en la BD';

    public function handle()
    {
        $prendaId = $this->option('prenda_id');
        
        if ($prendaId) {
            // Si se proporciona prenda_id, obtener todos los procesos de esa prenda
            $this->line("\n🔍 Consultando procesos para prenda_id: {$prendaId}\n");
            
            $procesos = \DB::table('pedidos_procesos_prenda_detalles')
                ->where('prenda_pedido_id', $prendaId)
                ->get();
            
            $this->line("📊 Procesos encontrados: " . $procesos->count());
            
            foreach ($procesos as $proceso) {
                $this->line("\n  Proceso ID: {$proceso->id}");
                $this->revisarProcesoSobremedida($proceso->id);
            }
            return;
        }
        
        // Si no, usar proceso_prenda_detalle_id fijo
        $procesoId = 1455;
        
        $this->line("\n🔍 Consultando sobremedida para proceso_prenda_detalle_id: {$procesoId}\n");
        $this->revisarProcesoSobremedida($procesoId);
    }

    private function revisarProcesoSobremedida($procesoId)
    {
        $this->line("🔍 Consultando sobremedida para proceso_prenda_detalle_id: {$procesoId}");
        
        // Obtener registros RAW con query builder
        $registrosRaw = \DB::table('pedidos_procesos_prenda_tallas')
            ->where('proceso_prenda_detalle_id', $procesoId)
            ->get();
        
        if ($registrosRaw->count() === 0) {
            $this->line("  ❌ Sin tallas para este proceso");
            return;
        }
        
        $this->line("  📊 Registros desde Query Builder:");
        foreach ($registrosRaw as $reg) {
            $this->line("    ID: {$reg->id}");
            $this->line("    genero: {$reg->genero}");
            $this->line("    talla: {$reg->talla}");
            $this->line("    cantidad: {$reg->cantidad}");
            $this->line("    es_sobremedida: {$reg->es_sobremedida}");
            $this->line("    ---");
        }
        
        // Obtener registros con el modelo
        $registrosModelo = PedidosProcesosPrendaTalla::where('proceso_prenda_detalle_id', $procesoId)->get();
        
        $this->line("  📊 Registros desde Modelo:");
        foreach ($registrosModelo as $reg) {
            $this->line("    ID: {$reg->id}");
            $this->line("    genero: {$reg->genero}");
            $this->line("    talla: {$reg->talla}");
            $this->line("    cantidad: {$reg->cantidad}");
            $this->line("    es_sobremedida: {$reg->es_sobremedida}");
            $this->line("    ---");
        }
    }
}
