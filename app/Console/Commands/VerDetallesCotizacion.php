<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Models\LogoCotizacion;

class VerDetallesCotizacion extends Command
{
    protected $signature = 'ver:cotizacion-detalles {id}';
    protected $description = 'Ver detalles completos de una cotización';

    public function handle()
    {
        $id = $this->argument('id');
        
        $cot = Cotizacion::find($id);
        if (!$cot) {
            $this->error("Cotización no encontrada: $id");
            return;
        }

        $this->info("=== COTIZACIÓN ID: {$cot->id} ===\n");
        $this->line("Número: {$cot->numero_cotizacion}");
        $this->line("Estado: {$cot->estado}");
        $this->line("Es Borrador: " . ($cot->es_borrador ? 'SÍ' : 'NO'));
        $this->line("Tipo Cotización ID: {$cot->tipo_cotizacion_id}");
        $this->line("Asesor ID: {$cot->asesor_id}");
        
        if ($cot->asesor) {
            $this->line("Asesor Nombre: {$cot->asesor->name}");
        }
        
        if ($cot->cliente) {
            $this->line("Cliente ID: {$cot->cliente->id}");
            $this->line("Cliente Nombre: {$cot->cliente->nombre}");
        } else {
            $this->warn("Cliente: NO ASIGNADO");
        }
        
        $this->line("Tipo de Venta: {$cot->tipo_venta}");
        $this->line("Fecha Inicio: {$cot->fecha_inicio}");
        $this->line("Fecha Envío: {$cot->fecha_envio}");
        $this->line("Creada: {$cot->created_at->format('Y-m-d H:i:s')}");
        $this->line("Actualizada: {$cot->updated_at->format('Y-m-d H:i:s')}");
        
        // Especificaciones
        $this->info("\n=== ESPECIFICACIONES ===");
        if ($cot->especificaciones) {
            $this->line(json_encode($cot->especificaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->warn("Sin especificaciones");
        }

        // Prendas
        $this->info("\n=== PRENDAS ===");
        $prendas = $cot->prendasCotizaciones()->get();
        $this->line("Total de prendas: " . $prendas->count());
        foreach ($prendas as $prenda) {
            $this->line("  - ID: {$prenda->id} | {$prenda->nombre_producto}");
        }

        // Logo
        $this->info("\n=== LOGO ===");
        $logo = LogoCotizacion::where('cotizacion_id', $cot->id)->first();
        if ($logo) {
            $this->line("Logo ID: {$logo->id}");
            $this->line("Descripción: {$logo->descripcion}");
            $this->line("Fotos: " . $logo->fotos()->count());
            
            $tecnicas = $logo->tecnicas()->get();
            $this->line("Técnicas: " . $tecnicas->count());
            foreach ($tecnicas as $tecnica) {
                $this->line("  - {$tecnica->tipo_logo->nombre}");
            }
        } else {
            $this->warn("Sin Logo");
        }
    }
}
