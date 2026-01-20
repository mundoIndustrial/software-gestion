<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarGuardadoCotizacion extends Command
{
    protected $signature = 'cotizacion:verificar {id}';
    protected $description = 'Verificar si una cotización se guardó correctamente en todas las tablas';

    public function handle()
    {
        $cotizacionId = $this->argument('id');

        $this->info(" Verificando cotización ID: {$cotizacionId}");
        $this->line('');

        // 1. Verificar cotización principal
        $this->verificarCotizacion($cotizacionId);

        // 2. Verificar prendas
        $this->verificarPrendas($cotizacionId);

        // 3. Verificar fotos
        $this->verificarFotos($cotizacionId);

        // 4. Verificar telas
        $this->verificarTelas($cotizacionId);

        // 5. Verificar tallas
        $this->verificarTallas($cotizacionId);

        // 6. Verificar variantes
        $this->verificarVariantes($cotizacionId);

        $this->info(' Verificación completada');
    }

    private function verificarCotizacion($cotizacionId)
    {
        $this->line(' COTIZACIÓN PRINCIPAL:');
        $cotizacion = DB::table('cotizaciones')
            ->where('id', $cotizacionId)
            ->first();

        if (!$cotizacion) {
            $this->error(" Cotización NO encontrada");
            return;
        }

        $this->info(" Cotización encontrada");
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $cotizacion->id],
                ['Asesor ID', $cotizacion->asesor_id],
                ['Cliente ID', $cotizacion->cliente_id ?? 'NULL'],
                ['Número', $cotizacion->numero_cotizacion],
                ['Tipo Venta', $cotizacion->tipo_venta],
                ['Es Borrador', $cotizacion->es_borrador ? 'Sí' : 'No'],
                ['Estado', $cotizacion->estado],
                ['Especificaciones', strlen($cotizacion->especificaciones ?? '') > 50 ? 'Presente' : 'Vacío'],
                ['Creado', $cotizacion->created_at],
            ]
        );
        $this->line('');
    }

    private function verificarPrendas($cotizacionId)
    {
        $this->line(' PRENDAS (prendas_cot):');
        $prendas = DB::table('prendas_cot')
            ->where('cotizacion_id', $cotizacionId)
            ->get();

        if ($prendas->isEmpty()) {
            $this->warn(" No hay prendas guardadas");
            $this->line('');
            return;
        }

        $this->info(" {$prendas->count()} prenda(s) encontrada(s)");
        $this->table(
            ['ID', 'Nombre', 'Descripción', 'Cantidad'],
            $prendas->map(function ($p) {
                return [
                    $p->id,
                    $p->nombre_producto,
                    substr($p->descripcion, 0, 30) . '...',
                    $p->cantidad
                ];
            })->toArray()
        );
        $this->line('');
    }

    private function verificarFotos($cotizacionId)
    {
        $this->line('📷 FOTOS (prenda_fotos_cot):');
        $fotos = DB::table('prenda_fotos_cot')
            ->whereIn('prenda_cot_id',
                DB::table('prendas_cot')
                    ->where('cotizacion_id', $cotizacionId)
                    ->pluck('id')
            )
            ->get();

        if ($fotos->isEmpty()) {
            $this->warn(" No hay fotos guardadas");
            $this->line('');
            return;
        }

        $this->info(" {$fotos->count()} foto(s) encontrada(s)");
        $this->table(
            ['ID', 'Prenda ID', 'Nombre', 'URL'],
            $fotos->map(function ($f) {
                return [
                    $f->id,
                    $f->prenda_id,
                    $f->nombre,
                    substr($f->url, 0, 40) . '...'
                ];
            })->toArray()
        );
        $this->line('');
    }

    private function verificarTelas($cotizacionId)
    {
        $this->line(' TELAS (prenda_telas_cot):');
        $telas = DB::table('prenda_telas_cot')
            ->whereIn('prenda_cot_id',
                DB::table('prendas_cot')
                    ->where('cotizacion_id', $cotizacionId)
                    ->pluck('id')
            )
            ->get();

        if ($telas->isEmpty()) {
            $this->warn(" No hay telas guardadas");
            $this->line('');
            return;
        }

        $this->info(" {$telas->count()} tela(s) encontrada(s)");
        $this->table(
            ['ID', 'Prenda ID', 'Color', 'Nombre Tela', 'Referencia'],
            $telas->map(function ($t) {
                return [
                    $t->id,
                    $t->prenda_id,
                    $t->color,
                    $t->nombre_tela,
                    $t->referencia
                ];
            })->toArray()
        );
        $this->line('');
    }

    private function verificarTallas($cotizacionId)
    {
        $this->line('📏 TALLAS (prenda_tallas_cot):');
        $tallas = DB::table('prenda_tallas_cot')
            ->whereIn('prenda_cot_id',
                DB::table('prendas_cot')
                    ->where('cotizacion_id', $cotizacionId)
                    ->pluck('id')
            )
            ->get();

        if ($tallas->isEmpty()) {
            $this->warn(" No hay tallas guardadas");
            $this->line('');
            return;
        }

        $this->info(" {$tallas->count()} talla(s) encontrada(s)");
        $this->table(
            ['ID', 'Prenda ID', 'Talla', 'Cantidad'],
            $tallas->map(function ($t) {
                return [
                    $t->id,
                    $t->prenda_id,
                    $t->talla,
                    $t->cantidad
                ];
            })->toArray()
        );
        $this->line('');
    }

    private function verificarVariantes($cotizacionId)
    {
        $this->line(' VARIANTES (prenda_variantes_cot):');
        $variantes = DB::table('prenda_variantes_cot')
            ->whereIn('prenda_cot_id',
                DB::table('prendas_cot')
                    ->where('cotizacion_id', $cotizacionId)
                    ->pluck('id')
            )
            ->get();

        if ($variantes->isEmpty()) {
            $this->warn(" No hay variantes guardadas");
            $this->line('');
            return;
        }

        $this->info(" {$variantes->count()} variante(s) encontrada(s)");
        $this->table(
            ['ID', 'Prenda ID', 'Tipo Prenda', 'Género', 'Bolsillos', 'Reflectivo'],
            $variantes->map(function ($v) {
                return [
                    $v->id,
                    $v->prenda_id,
                    $v->tipo_prenda,
                    $v->genero_id ?? '-',
                    $v->tiene_bolsillos ? 'Sí' : 'No',
                    $v->tiene_reflectivo ? 'Sí' : 'No'
                ];
            })->toArray()
        );
        $this->line('');
    }
}
