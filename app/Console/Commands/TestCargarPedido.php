<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;

class TestCargarPedido extends Command
{
    protected $signature = 'test:cargar-pedido {numero_pedido=45767}';
    protected $description = 'Prueba cargar todos los datos de un pedido para edición (por número de pedido)';

    public function handle()
    {
        $numeroPedido = $this->argument('numero_pedido');
        
        $this->info("=== PRUEBA DE CARGA DE DATOS DEL PEDIDO ===\n");
        $this->info("Número de Pedido: $numeroPedido");
        $this->info("Timestamp: " . date('Y-m-d H:i:s') . "\n");

        try {
            // Buscar pedido por número
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if (!$orden) {
                $this->error("❌ Pedido no encontrado con número: $numeroPedido");
                return;
            }
            
            // Cargar todas las relaciones (igual que en el controlador)
            $orden->load([
                'prendas' => function($query) {
                    $query->with([
                        'fotos',
                        'coloresTelas' => function($q) {
                            $q->with(['color', 'tela', 'fotos']);
                        },
                        'fotosTelas',
                        'variantes',
                        'procesos' => function($q) {
                            $q->with(['imagenes', 'tipoProceso']);
                        }
                    ]);
                },
                'asesora'
            ]);

            $this->line('✅ Pedido cargado correctamente');
            $this->line("Número de pedido: " . $orden->numero_pedido);
            $this->line("Cliente: " . $orden->cliente);
            $this->line("Prendas: " . $orden->prendas->count() . "\n");

            // Verificar cada prenda
            foreach ($orden->prendas as $idx => $prenda) {
                $this->line("--- PRENDA " . ($idx + 1) . " ---");
                $this->line("Nombre: " . $prenda->nombre_prenda);
                $this->line("Descripción: " . $prenda->descripcion);
                
                // Variantes
                $this->line("\n📋 VARIANTES: " . $prenda->variantes->count());
                if ($prenda->variantes->count() > 0) {
                    foreach ($prenda->variantes as $var) {
                        $this->line("  ✓ Variante ID: " . $var->id);
                        $this->line("    Manga: " . ($var->tipoManga?->nombre ?? 'N/A'));
                        $this->line("    Broche: " . ($var->tipoBroche?->nombre ?? 'N/A'));
                        $this->line("    Bolsillos: " . ($var->tiene_bolsillos ? 'Sí' : 'No'));
                        $this->line("    manga_obs: " . $var->manga_obs);
                        $this->line("    broche_boton_obs: " . $var->broche_boton_obs);
                    }
                } else {
                    $this->warn("  ⚠️  Sin variantes");
                }
                
                // Colores-Telas
                $this->line("\n🎨 COLORES-TELAS: " . $prenda->coloresTelas->count());
                if ($prenda->coloresTelas->count() > 0) {
                    foreach ($prenda->coloresTelas as $ct) {
                        $this->line("  ✓ ID: " . $ct->id);
                        $this->line("    Color: " . ($ct->color?->nombre ?? 'N/A'));
                        $this->line("    Tela: " . ($ct->tela?->nombre ?? 'N/A'));
                    }
                } else {
                    $this->warn("  ⚠️  Sin colores-telas");
                }
                
                // Telas
                $this->line("\n🧵 TELAS: " . $prenda->fotosTelas->count());
                if ($prenda->fotosTelas->count() > 0) {
                    foreach ($prenda->fotosTelas as $tela) {
                        $this->line("  ✓ Ruta: " . $tela->ruta_webp);
                    }
                } else {
                    $this->warn("  ⚠️  Sin telas");
                }
                
                // Fotos de prenda
                $this->line("\n📸 FOTOS DE PRENDA: " . $prenda->fotos->count());
                if ($prenda->fotos->count() > 0) {
                    foreach ($prenda->fotos as $foto) {
                        $this->line("  ✓ Ruta: " . $foto->ruta_foto);
                    }
                } else {
                    $this->warn("  ⚠️  Sin fotos");
                }
                
                // Logos (si existen)
                $logosCount = $prenda->fotosLogo ? $prenda->fotosLogo->count() : 0;
                $this->line("\n🏷️  LOGOS: " . $logosCount);
                if ($logosCount > 0) {
                    foreach ($prenda->fotosLogo as $logo) {
                        $this->line("  ✓ Ruta: " . $logo->ruta_foto);
                    }
                } else {
                    $this->warn("  ⚠️  Sin logos");
                }
                
                // Procesos
                $this->line("\n⚙️  PROCESOS: " . $prenda->procesos->count());
                if ($prenda->procesos->count() > 0) {
                    foreach ($prenda->procesos as $proceso) {
                        $this->line("  ✓ Tipo: " . ($proceso->tipo_proceso ?? 'N/A'));
                        $this->line("    Observaciones: " . ($proceso->observaciones ?? 'N/A'));
                        
                        // Ubicaciones pueden ser JSON string o array
                        $ubicaciones = $proceso->ubicaciones;
                        if (is_string($ubicaciones)) {
                            $ubicaciones = json_decode($ubicaciones, true) ?? [];
                        }
                        $ubicacionesTexto = is_array($ubicaciones) ? implode(', ', $ubicaciones) : 'N/A';
                        $this->line("    Ubicaciones: " . $ubicacionesTexto);
                        
                        $this->line("    Imágenes: " . $proceso->imagenes->count());
                        
                        if ($proceso->imagenes->count() > 0) {
                            foreach ($proceso->imagenes as $img) {
                                $this->line("      • " . ($img->ruta_webp ?? $img->ruta_original ?? 'N/A'));
                            }
                        }
                    }
                } else {
                    $this->warn("  ⚠️  Sin procesos");
                }
                
                $this->line("");
            }
            
            $this->info("\n=== RESUMEN ===");
            $this->line("✅ Todas las relaciones se cargaron correctamente");
            $this->line("✅ Datos listos para enviar al frontend");
            
        } catch (\Exception $e) {
            $this->error("❌ ERROR: " . $e->getMessage());
            $this->error("Stack trace:");
            $this->error($e->getTraceAsString());
        }

        $this->info("\n=== FIN DE PRUEBA ===");
    }
}
