<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\PrendaFotoPedido;
use App\Models\PedidoProduccion;

class RepararImagenesPrendas extends Command
{
    protected $signature = 'reparar:imagenes-prendas {--pedido-id= : ID del pedido a reparar} {--fix : Aplicar correcciones}';
    protected $description = 'Diagnóstica y repara rutas de imágenes de prendas en pedidos';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO Y REPARACIÓN DE IMÁGENES DE PRENDAS');
        $this->info('════════════════════════════════════════════════════');

        $pedidoId = $this->option('pedido-id');
        $fix = $this->option('fix');

        if (!$pedidoId) {
            $this->error('❌ Usa: php artisan reparar:imagenes-prendas --pedido-id=2765 [--fix]');
            return 1;
        }

        // Obtener pedido
        $pedido = PedidoProduccion::with('prendas')->find($pedidoId);
        if (!$pedido) {
            $this->error("❌ Pedido #{$pedidoId} no encontrado");
            return 1;
        }

        $this->line("\n📋 Información del Pedido:");
        $this->line("├─ ID: {$pedido->id}");
        $this->line("├─ Número: {$pedido->numero_pedido}");
        $this->line("├─ Cliente: {$pedido->cliente}");
        $this->line("└─ Total Prendas: {$pedido->prendas->count()}");

        $totalProblemas = 0;

        // Diagnosticar cada prenda
        foreach ($pedido->prendas as $prenda) {
            $this->line("\n🧥 Prenda #{$prenda->numero_prenda}: {$prenda->nombre_prenda}");
            $this->line("├─ ID: {$prenda->id}");

            // Obtener fotos de esta prenda
            $fotos = PrendaFotoPedido::where('prenda_pedido_id', $prenda->id)->get();
            $this->line("├─ Fotos registradas: {$fotos->count()}");

            if ($fotos->isEmpty()) {
                $this->line("└─ ✅ Sin fotos");
                continue;
            }

            foreach ($fotos as $foto) {
                $problemas = $this->diagnosticarFoto($foto, $pedidoId);
                $totalProblemas += count($problemas);

                if ($fix && !empty($problemas)) {
                    $this->repararFoto($foto, $pedidoId);
                }
            }
        }

        $this->line("\n════════════════════════════════════════════════════");

        if ($totalProblemas === 0) {
            $this->info("✅ DIAGNÓSTICO COMPLETADO - SIN PROBLEMAS");
        } else {
            if ($fix) {
                $this->info("✅ REPARACIÓN COMPLETADA");
                $this->info("🔧 Se ejecutaron las correcciones automáticas");
            } else {
                $this->warn("⚠️ Se encontraron {$totalProblemas} problemas");
                $this->info("   Usa --fix para reparar automáticamente");
                $this->info("   php artisan reparar:imagenes-prendas --pedido-id={$pedidoId} --fix");
            }
        }

        return 0;
    }

    /**
     * Diagnosticar una foto individual
     */
    private function diagnosticarFoto(PrendaFotoPedido $foto, int $pedidoId): array
    {
        $problemas = [];

        $this->line("\n   📷 Foto ID: {$foto->id}");
        $this->line("   ├─ ruta_webp: " . ($foto->ruta_webp ?? '(vacío)'));
        $this->line("   └─ ruta_original: " . ($foto->ruta_original ?? '(vacío)'));

        // ❌ PROBLEMA 1: ruta_original no está registrada
        if (empty($foto->ruta_original)) {
            $problemas[] = "ruta_original_vacia";
            $this->line("      ❌ ruta_original ESTÁ VACÍA");
        }

        // ❌ PROBLEMA 2: Archivo WebP no existe
        if (!empty($foto->ruta_webp)) {
            $existeWebp = Storage::disk('public')->exists($foto->ruta_webp);
            $existeConStorage = Storage::disk('public')->exists(str_replace('/storage/', '', $foto->ruta_webp));

            if (!$existeWebp && !$existeConStorage) {
                $problemas[] = "archivo_webp_no_existe";
                $this->line("      ❌ Archivo WebP NO EXISTE");
            } else {
                $this->line("      ✅ Archivo WebP existe");
            }
        }

        // ❌ PROBLEMA 3: ruta_webp contiene /storage/
        if (!empty($foto->ruta_webp) && str_contains($foto->ruta_webp, '/storage/')) {
            $problemas[] = "ruta_webp_con_storage";
            $this->line("      ❌ ruta_webp contiene /storage/");
        }

        return $problemas;
    }

    /**
     * Reparar una foto
     */
    private function repararFoto(PrendaFotoPedido $foto, int $pedidoId): void
    {
        $this->line("      🔧 REPARANDO...");

        // Caso 1: Limpiar /storage/ de la ruta
        $rutaWebpLimpia = str_replace('/storage/', '', $foto->ruta_webp);

        // Caso 2: Si ruta_original está vacía, usar la ruta WebP como original
        if (empty($foto->ruta_original)) {
            $foto->update([
                'ruta_webp' => $rutaWebpLimpia,
                'ruta_original' => $rutaWebpLimpia,
            ]);
            $this->line("      ✅ ruta_original completada desde WebP");
        } else {
            // Solo limpiar /storage/ si existe
            $foto->update([
                'ruta_webp' => $rutaWebpLimpia,
            ]);
            $this->line("      ✅ ruta_webp limpiada");
        }
    }
}
