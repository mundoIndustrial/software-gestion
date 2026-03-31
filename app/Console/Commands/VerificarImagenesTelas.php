<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoTelaPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerificarImagenesTelas extends Command
{
    protected $signature = 'diagnostico:telas {pedido_id? : ID del pedido a verificar}';
    protected $description = 'Verifica imágenes de telas en un pedido';

    public function handle()
    {
        $pedidoId = $this->argument('pedido_id');

        if (!$pedidoId) {
            $this->info('Ingrese el ID del pedido:');
            $pedidoId = (int)$this->ask('Pedido ID');
        }

        $this->verificarPedido($pedidoId);
    }

    private function verificarPedido(int $pedidoId)
    {
        $pedido = PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            $this->error(" Pedido {$pedidoId} no encontrado");
            return;
        }

        $this->info("
╔════════════════════════════════════════════════════════════╗
║          VERIFICACIÓN DE IMÁGENES DE TELAS                ║
║          Pedido: {$pedido->numero_pedido}                 ║
╚════════════════════════════════════════════════════════════╝
        ");

        $prendas = $pedido->prendas;
        $this->info(" Total de prendas: {$prendas->count()}\n");

        $totalTelas = 0;
        $totalImagenes = 0;
        $imagenesEnDisco = 0;

        foreach ($prendas as $prendaIdx => $prenda) {
            $prendaNum = $prendaIdx + 1;
            $this->info("
┌─ PRENDA #{$prendaNum} (ID: {$prenda->id})");
            $this->line("│  Nombre: {$prenda->nombre_prenda}");
            $this->line("│  Descripción: {$prenda->descripcion}");

            $coloresTelas = $prenda->coloresTelas;
            $this->info("│  Telas: {$coloresTelas->count()}");

            foreach ($coloresTelas as $telaIdx => $colorTela) {
                $color = DB::table('colores_prenda')->find($colorTela->color_id);
                $tela = DB::table('telas_prenda')->find($colorTela->tela_id);

                $colorNombre = $color?->nombre ?? "ID:{$colorTela->color_id}";
                $telaNombre = $tela?->nombre ?? "ID:{$colorTela->tela_id}";

                $telaNum = $telaIdx + 1;
                $this->line("│  ├─ Tela #{$telaNum} (ID: {$colorTela->id})");
                $this->line("│  │  Color: {$colorNombre}");
                $this->line("│  │  Tela: {$telaNombre}");

                $fotos = $colorTela->fotos;
                $this->info("│  │  Imágenes: {$fotos->count()}");

                $totalTelas++;
                $totalImagenes += $fotos->count();

                foreach ($fotos as $fotoIdx => $foto) {
                    $fotoNum = $fotoIdx + 1;
                    $this->line("│  │  ├─ Foto #{$fotoNum} (Orden: {$foto->orden})");
                    $this->line("│  │  │  Ruta: {$foto->ruta_webp}");

                    // Verificar en disco
                    $rutaCompleta = "public/{$foto->ruta_webp}";
                    if (Storage::exists($rutaCompleta)) {
                        $tamano = Storage::size($rutaCompleta);
                        $this->line("│  │  │   En disco ({$tamano} bytes)");
                        $imagenesEnDisco++;
                    } else {
                        $this->error("│  │  │   NO en disco");
                    }
                }

                $this->line("│  │");
            }

            $this->line("└─\n");
        }

        // Resumen
        $this->info("
╔════════════════════════════════════════════════════════════╗
║                      RESUMEN                               ║
╚════════════════════════════════════════════════════════════╝
        ");

        $this->line(" Total de telas (color-tela): {$totalTelas}");
        $this->line(" Total de imágenes en BD: {$totalImagenes}");
        $this->line(" Total de imágenes en disco: {$imagenesEnDisco}");

        if ($totalImagenes === $imagenesEnDisco) {
            $this->info(" TODAS las imágenes están en disco");
        } else {
            $this->warn(" Imágenes desincronizadas (BD: {$totalImagenes}, Disco: {$imagenesEnDisco})");
        }

        // Verificar carpeta
        $carpetaTelas = "public/pedidos/{$pedidoId}/telas";
        if (Storage::exists($carpetaTelas)) {
            $archivos = Storage::files($carpetaTelas);
            $this->info("\n📁 Archivos en {$carpetaTelas}:");
            $this->line("   Total: " . count($archivos));
            foreach (array_slice($archivos, 0, 5) as $archivo) {
                $this->line("   - " . basename($archivo));
            }
            if (count($archivos) > 5) {
                $this->line("   ... y " . (count($archivos) - 5) . " más");
            }
        } else {
            $this->warn("\n📁 Carpeta {$carpetaTelas} no existe");
        }

        // Query SQL alternativa
        $this->verificarViaSQL($pedidoId);
    }

    private function verificarViaSQL(int $pedidoId)
    {
        $this->info("
╔════════════════════════════════════════════════════════════╗
║                   VERIFICACIÓN SQL                         ║
╚════════════════════════════════════════════════════════════╝
        ");

        $query = "
        SELECT
            pp.id as prenda_id,
            pp.nombre_prenda,
            pct.id as color_tela_id,
            cp.nombre as color,
            tp.nombre as tela,
            COUNT(pft.id) as cantidad_fotos
        FROM prendas_pedido pp
        LEFT JOIN prenda_pedido_colores_telas pct ON pp.id = pct.prenda_pedido_id
        LEFT JOIN colores_prenda cp ON pct.color_id = cp.id
        LEFT JOIN telas_prenda tp ON pct.tela_id = tp.id
        LEFT JOIN prenda_fotos_tela_pedido pft ON pct.id = pft.prenda_pedido_colores_telas_id
        WHERE pp.pedido_produccion_id = {$pedidoId}
        GROUP BY pp.id, pct.id, cp.id, tp.id
        ORDER BY pp.id, pct.id;
        ";

        $resultados = DB::select($query);

        if (empty($resultados)) {
            $this->warn("Sin datos para mostrar");
            return;
        }

        foreach ($resultados as $row) {
            $this->line("
        Prenda: {$row->nombre_prenda} (ID: {$row->prenda_id})
        └─ Color: {$row->color} | Tela: {$row->tela} (ID: {$row->color_tela_id})
           Fotos: {$row->cantidad_fotos}
            ");
        }
    }
}
