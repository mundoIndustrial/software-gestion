<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;

class VerCotizacionEspecifica extends Command
{
    protected $signature = 'ver:cotizacion {numero}';
    protected $description = 'Ver detalles de una cotización específica';

    public function handle()
    {
        $numero = $this->argument('numero');
        
        $cot = Cotizacion::where('numero_cotizacion', $numero)
            ->orWhere('numero', $numero)
            ->orWhere('id', $numero)
            ->first();

        if (!$cot) {
            $this->error("Cotización '$numero' no encontrada");
            return;
        }

        $this->info("=== COTIZACIÓN: {$cot->numero_cotizacion} ===\n");
        $this->line("ID: {$cot->id}");
        $this->line("NÚMERO: {$cot->numero_cotizacion}");
        $this->line("ESTADO: {$cot->estado}");
        $this->line("ASESOR_ID: {$cot->asesor_id}");
        
        if ($cot->asesor) {
            $this->line("ASESOR: {$cot->asesor->name} ({$cot->asesor->email})");
        } else {
            $this->error("ASESOR: No encontrado");
        }

        $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
        $this->line("CLIENTE: $cliente");
        $this->line("PRENDAS: " . $cot->prendasCotizaciones->count());
        $this->line("CREADA: {$cot->created_at}");
    }
}
