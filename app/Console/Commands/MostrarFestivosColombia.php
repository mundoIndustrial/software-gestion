<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FestivosColombiaService;
use Carbon\Carbon;

class MostrarFestivosColombia extends Command
{
    protected $signature = 'festivos:colombia {year?}';
    protected $description = 'Mostrar festivos de Colombia para un año específico';

    public function handle()
    {
        $year = $this->argument('year') ?? now()->year;
        
        $this->info("🇨🇴 Festivos de Colombia para el año {$year}");
        $this->newLine();
        
        $festivos = FestivosColombiaService::obtenerFestivos($year);
        
        if (empty($festivos)) {
            $this->error("No se pudieron obtener festivos para el año {$year}");
            return 1;
        }
        
        $this->table(
            ['Fecha', 'Día de la Semana', 'Días desde hoy'],
            collect($festivos)->map(function ($fecha) {
                $carbon = Carbon::parse($fecha);
                $diasDesdeHoy = now()->diffInDays($carbon, false);
                $signo = $diasDesdeHoy > 0 ? '+' : '';
                
                return [
                    $carbon->format('Y-m-d'),
                    $carbon->locale('es')->isoFormat('dddd'),
                    $signo . $diasDesdeHoy . ' días'
                ];
            })->toArray()
        );
        
        $this->newLine();
        $this->info("Total: " . count($festivos) . " festivos");
        
        // Próximo festivo
        $proximoFestivo = collect($festivos)
            ->map(fn($f) => Carbon::parse($f))
            ->filter(fn($f) => $f->isFuture())
            ->sortBy(fn($f) => $f->timestamp)
            ->first();
            
        if ($proximoFestivo) {
            $this->newLine();
            $this->info("🎉 Próximo festivo: " . $proximoFestivo->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'));
            $this->info("   Faltan " . now()->diffInDays($proximoFestivo) . " días");
        }
        
        return 0;
    }
}
