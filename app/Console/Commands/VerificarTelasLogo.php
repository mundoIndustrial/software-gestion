<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LogoCotizacionTelasPrenda;
use Illuminate\Support\Facades\DB;

class VerificarTelasLogo extends Command
{
    protected $signature = 'verificar:telas-logo';
    protected $description = 'Verifica cómo están guardadas las imágenes en logo_cotizacion_telas_prenda';

    public function handle()
    {
        $this->info(' VERIFICANDO TABLA: logo_cotizacion_telas_prenda');
        $this->newLine();

        $telas = LogoCotizacionTelasPrenda::all();

        $this->info(' Total de registros: ' . $telas->count());
        $this->newLine();

        if ($telas->count() > 0) {
            $this->info('📋 REGISTROS:');
            $this->line(str_repeat("=", 120));

            foreach ($telas as $index => $tela) {
                $this->newLine();
                $this->line("[$index] ID: {$tela->id}");
                $this->line("    Logo Cotización ID: {$tela->logo_cotizacion_id}");
                $this->line("    Prenda Cot ID: {$tela->prenda_cot_id}");
                $this->line("    Tela: {$tela->tela}");
                $this->line("    Color: {$tela->color}");
                $this->line("    Ref: {$tela->ref}");
                $this->line("    Img (RAW): {$tela->img}");

                if (strpos($tela->img, '/storage/') === 0) {
                    $this->line("     RUTA ABSOLUTA (comienza con /storage/)");
                } elseif (strpos($tela->img, 'storage/') === 0) {
                    $this->line("     RUTA RELATIVA (comienza con storage/)");
                    $this->line("    → Debería ser: /{$tela->img}");
                } else {
                    $this->line("     RUTA NO ESTÁNDAR");
                }
            }

            $this->newLine();
            $this->line(str_repeat("=", 120));

            $this->info(' QUERY DIRECTA SQL:');
            $resultado = DB::select("SELECT id, logo_cotizacion_id, prenda_cot_id, tela, color, ref, img FROM logo_cotizacion_telas_prenda");
            foreach ($resultado as $row) {
                $this->newLine();
                $this->line("ID: {$row->id} | Prenda: {$row->prenda_cot_id} | Tela: {$row->tela} | Color: {$row->color}");
                $this->line("  img: {$row->img}");
            }
        } else {
            $this->warn(' No hay registros en la tabla');
        }

        $this->info(' Script completado');
    }
}
