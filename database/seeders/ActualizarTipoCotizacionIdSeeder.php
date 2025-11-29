<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\TipoCotizacion;
use Illuminate\Database\Seeder;

class ActualizarTipoCotizacionIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Actualiza el campo tipo_cotizacion_id en todas las cotizaciones existentes
     * basándose en su contenido (prendas y logo)
     */
    public function run(): void
    {
        $cotizaciones = Cotizacion::with(['prendasCotizaciones', 'logoCotizacion'])->get();

        $this->command->info("Procesando " . $cotizaciones->count() . " cotizaciones...");

        foreach ($cotizaciones as $cotizacion) {
            $tipo = $this->determinarTipo($cotizacion);
            $tipoCotizacion = TipoCotizacion::where('codigo', $tipo)->first();

            if (!$tipoCotizacion) {
                $this->command->error("❌ Tipo de cotización no encontrado: {$tipo}");
                continue;
            }

            try {
                $cotizacion->update([
                    'tipo_cotizacion_id' => $tipoCotizacion->id
                ]);
                $this->command->info("✅ Cotización #{$cotizacion->id} - Tipo: {$tipoCotizacion->nombre}");
            } catch (\Exception $e) {
                $this->command->error("❌ Error en cotización #{$cotizacion->id}: " . $e->getMessage());
            }
        }

        $this->command->info('✅ Todas las cotizaciones han sido procesadas');
    }

    /**
     * Determina el tipo de cotización basado en los datos
     */
    private function determinarTipo(Cotizacion $cotizacion): string
    {
        $tienePrendas = $cotizacion->prendasCotizaciones && $cotizacion->prendasCotizaciones->count() > 0;
        $tieneLogo = $cotizacion->logoCotizacion && (
            ($cotizacion->logoCotizacion->tecnicas && is_array($cotizacion->logoCotizacion->tecnicas) && count($cotizacion->logoCotizacion->tecnicas) > 0) ||
            ($cotizacion->logoCotizacion->imagenes && is_array($cotizacion->logoCotizacion->imagenes) && count($cotizacion->logoCotizacion->imagenes) > 0) ||
            ($cotizacion->logoCotizacion->observaciones_tecnicas && !empty($cotizacion->logoCotizacion->observaciones_tecnicas))
        );

        if ($tienePrendas && $tieneLogo) {
            return 'prenda_logo';
        } elseif ($tieneLogo) {
            return 'logo';
        } else {
            return 'general';
        }
    }
}
