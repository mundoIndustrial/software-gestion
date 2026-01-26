<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\PrendaFotoPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidosProcessImagenes;

class DiagnosticarImagenesPrendas extends Command
{
    protected $signature = 'diagnosticar:imagenes-prendas {--pedido-id= : ID del pedido a diagnosticar} {--reparar : Ejecutar reparaciones}';
    protected $description = 'Diagnostica y repara rutas de imágenes de prendas en pedidos';

    public function handle()
    {
        $this->info('🔍 INICIANDO DIAGNÓSTICO DE IMÁGENES DE PRENDAS');
        $this->info('═════════════════════════════════════════════════');

        $pedidoId = $this->option('pedido-id');
        $reparar = $this->option('reparar');

        if (!$pedidoId) {
            $this->error('❌ Debes proporcionar --pedido-id');
            return 1;
        }

        // Obtener pedido
        $pedido = PedidoProduccion::with('prendas.fotos')->find($pedidoId);
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
        $totalReparadas = 0;

        // Diagnosticar cada prenda
        foreach ($pedido->prendas as $prenda) {
            $this->line("\n🧥 Prenda #{$prenda->numero_prenda}: {$prenda->nombre_prenda}");
            $this->line("├─ ID: {$prenda->id}");
            $this->line("├─ Fotos registradas: {$prenda->fotos->count()}");

            foreach ($prenda->fotos as $idx => $foto) {
                $this->diagnosticarFoto($foto, $idx, $reparar, $pedidoId);
            }
        }

        $this->info("\n═════════════════════════════════════════════════");
        $this->info("✅ DIAGNÓSTICO COMPLETADO");
        if ($reparar) {
            $this->info("🔧 Reparaciones ejecutadas");
        }

        return 0;
    }

    private function diagnosticarFoto($foto, $idx, $reparar, $pedidoId)
    {
        $rutaWebp = $foto->ruta_webp;
        $rutaOriginal = $foto->ruta_original;

        $this->line("\n   Foto #{$idx}:");
        $this->line("   ├─ ID BD: {$foto->id}");
        $this->line("   ├─ ruta_webp: {$rutaWebp}");
        $this->line("   └─ ruta_original: {$rutaOriginal}");

        // Diagnosticar
        $problemas = [];

        // ❌ PROBLEMA 1: Rutas undefined
        if (empty($rutaWebp) && empty($rutaOriginal)) {
            $problemas[] = "❌ Ambas rutas están VACÍAS";
        }

        // ❌ PROBLEMA 2: Rutas con /storage/ duplicado
        if (str_contains($rutaWebp, '/storage/storage/') || str_contains($rutaOriginal, '/storage/storage/')) {
            $problemas[] = "❌ Duplicación de /storage/";
        }

        // ✅ PROBLEMA 3: Verificar si archivo existe
        $archivoWebpExiste = Storage::disk('public')->exists($rutaWebp);
        $archivoOriginalExiste = Storage::disk('public')->exists($rutaOriginal);

        $this->line("   ├─ WebP existe: " . ($archivoWebpExiste ? '✅' : '❌'));
        $this->line("   └─ Original existe: " . ($archivoOriginalExiste ? '✅' : '❌'));

        if (!$archivoWebpExiste && !empty($rutaWebp)) {
            $problemas[] = "❌ Archivo WebP NO EXISTE: {$rutaWebp}";
        }

        if (!$archivoOriginalExiste && !empty($rutaOriginal)) {
            $problemas[] = "❌ Archivo Original NO EXISTE: {$rutaOriginal}";
        }

        // ❌ PROBLEMA 4: Buscar en temp/
        $rutaEnTemp = $this->buscarEnTemp($rutaWebp, $rutaOriginal);
        if ($rutaEnTemp) {
            $problemas[] = "⚠️ Archivo ENCONTRADO EN TEMP: {$rutaEnTemp}";
        }

        if (!empty($problemas)) {
            $this->line("   \n   🚨 PROBLEMAS DETECTADOS:");
            foreach ($problemas as $problema) {
                $this->line("      {$problema}");
            }

            if ($reparar) {
                $this->repararFoto($foto, $rutaWebp, $rutaOriginal, $pedidoId);
            }
        } else {
            $this->line("   ✅ SIN PROBLEMAS");
        }
    }

    private function buscarEnTemp($rutaWebp, $rutaOriginal)
    {
        $nombreArchivo = basename($rutaWebp ?: $rutaOriginal);

        // Buscar en temp
        if (Storage::disk('public')->exists("temp/{$nombreArchivo}")) {
            return "temp/{$nombreArchivo}";
        }

        // Buscar en pedidos/0/
        if (Storage::disk('public')->exists("pedidos/0/{$nombreArchivo}")) {
            return "pedidos/0/{$nombreArchivo}";
        }

        return null;
    }

    private function repararFoto($foto, $rutaWebp, $rutaOriginal, $pedidoId)
    {
        $this->line("\n      🔧 REPARANDO FOTO #{$foto->id}...");

        // Caso 1: Rutas vacías - buscar en temp
        if (empty($rutaWebp) && empty($rutaOriginal)) {
            $this->line("      ├─ Caso: Rutas vacías");
            // TODO: Buscar archivo huérfano en temp y moverlo
            return;
        }

        // Caso 2: Archivo en temp pero ruta incorrecta
        $rutaTemp = $this->buscarEnTemp($rutaWebp, $rutaOriginal);
        if ($rutaTemp) {
            $this->line("      ├─ Caso: Archivo encontrado en {$rutaTemp}");
            $nombreArchivo = basename($rutaTemp);
            $rutaNueva = "pedidos/{$pedidoId}/prendas/{$nombreArchivo}";

            // Crear directorio
            $dirDestino = dirname(storage_path("app/public/{$rutaNueva}"));
            if (!is_dir($dirDestino)) {
                mkdir($dirDestino, 0755, true);
            }

            // Mover archivo
            if (Storage::disk('public')->move($rutaTemp, $rutaNueva)) {
                $foto->update(['ruta_webp' => $rutaNueva]);
                $this->line("      ├─ ✅ Archivo movido a: {$rutaNueva}");
                $this->line("      └─ ✅ BD actualizada");
            } else {
                $this->error("      └─ ❌ Error al mover archivo");
            }
            return;
        }

        // Caso 3: Archivo no existe pero ruta tiene estructura incorrecta
        if (!Storage::disk('public')->exists($rutaWebp)) {
            $this->line("      ├─ Caso: Ruta incorrecta");

            // Intentar variaciones
            $variaciones = [
                str_replace('/storage/', '', $rutaWebp),
                'pedidos/' . $pedidoId . '/prendas/' . basename($rutaWebp),
            ];

            foreach ($variaciones as $ruta) {
                if (Storage::disk('public')->exists($ruta)) {
                    $foto->update(['ruta_webp' => $ruta]);
                    $this->line("      ├─ ✅ Ruta corregida: {$ruta}");
                    $this->line("      └─ ✅ BD actualizada");
                    return;
                }
            }

            $this->error("      └─ ❌ No se encontró archivo en variaciones");
        }
    }
}
