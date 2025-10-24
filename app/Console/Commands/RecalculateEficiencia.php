<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;

class RecalculateEficiencia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalculate-eficiencia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula la eficiencia para todos los registros existentes usando la fórmula cantidad / meta';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recalculando eficiencia para registros de piso producción...');

        $registrosProduccion = RegistroPisoProduccion::all();
        $countProduccion = 0;

        foreach ($registrosProduccion as $registro) {
            $eficiencia = $registro->meta == 0 ? 0 : ($registro->cantidad / $registro->meta);
            $registro->eficiencia = $eficiencia;
            $registro->save();
            $countProduccion++;
        }

        $this->info("Actualizados {$countProduccion} registros de piso producción.");

        $this->info('Recalculando eficiencia para registros de piso polos...');

        $registrosPolos = RegistroPisoPolo::all();
        $countPolos = 0;

        foreach ($registrosPolos as $registro) {
            $eficiencia = $registro->meta == 0 ? 0 : ($registro->cantidad / $registro->meta);
            $registro->eficiencia = $eficiencia;
            $registro->save();
            $countPolos++;
        }

        $this->info("Actualizados {$countPolos} registros de piso polos.");

        $this->info('Recálculo completado exitosamente.');
    }
}
