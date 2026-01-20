<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RevisarAsesor extends Command
{
    protected $signature = 'revisar:asesor';
    protected $description = 'Revisa quién es el asesor actual y a qué cotizaciones tiene acceso';

    public function handle()
    {
        $this->info('=== REVISANDO ASESOR ACTUAL ===\n');

        // Obtener el asesor actual (el primero que sea asesor)
        $asesores = User::where('role', 'asesor')->get();
        $this->info("Total de asesores en BD: " . $asesores->count());
        
        foreach ($asesores as $asesor) {
            $this->line("  - {$asesor->id}: {$asesor->name} ({$asesor->email})");
        }

        if ($asesores->count() > 0) {
            $asesor = $asesores->first();
            $this->info("\n=== COTIZACIONES DEL ASESOR: {$asesor->name} ===");
            
            $cotizaciones = Cotizacion::where('asesor_id', $asesor->id)->get();
            $this->info("Total: " . $cotizaciones->count());
            
            foreach ($cotizaciones as $cot) {
                $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
                $this->line("  ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | ESTADO: {$cot->estado} | CLIENTE: $cliente");
            }

            $aprobadas = Cotizacion::where('asesor_id', $asesor->id)
                ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
                ->get();
            
            $this->info("\nCotizaciones APROBADAS de {$asesor->name}: " . $aprobadas->count());
            foreach ($aprobadas as $cot) {
                $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
                $this->line("   ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | CLIENTE: $cliente");
            }
        }

        // Ahora verifica la cotización encontrada
        $this->info("\n=== COTIZACIÓN COT-00329 ===");
        $cot = Cotizacion::where('numero_cotizacion', 'COT-00329')->first();
        if ($cot) {
            $this->line("ID: {$cot->id}");
            $this->line("ASESOR_ID: {$cot->asesor_id}");
            if ($cot->asesor) {
                $this->line("ASESOR NOMBRE: {$cot->asesor->name}");
            }
            $this->line("ESTADO: {$cot->estado}");
            $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
            $this->line("CLIENTE: $cliente");
        } else {
            $this->error("Cotización no encontrada");
        }
    }
}
