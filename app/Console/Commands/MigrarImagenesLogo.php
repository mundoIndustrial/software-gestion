<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarImagenesLogo extends Command
{
    protected $signature = 'db:migrar-imagenes-logo';
    protected $description = 'Migra imágenes de logos desde JSON a tabla separada';

    public function handle()
    {
        $this->info(' MIGRANDO IMÁGENES DE LOGOS');
        $this->newLine();

        try {
            // Obtener logos con imágenes
            $logos = DB::table('logo_cotizaciones')
                ->whereNotNull('imagenes')
                ->where('imagenes', '!=', 'null')
                ->get();

            $this->info(" Logos encontrados: " . count($logos));
            $this->newLine();

            $totalMigradas = 0;
            $errores = 0;

            foreach ($logos as $logo) {
                try {
                    $imagenes = json_decode($logo->imagenes, true);

                    if (!is_array($imagenes)) {
                        $this->warn(" Logo {$logo->id}: Imágenes no es un array válido");
                        $errores++;
                        continue;
                    }

                    // Verificar máximo de 5 imágenes
                    if (count($imagenes) > 5) {
                        $this->warn(" Logo {$logo->id}: Tiene " . count($imagenes) . " imágenes (máximo 5)");
                        $imagenes = array_slice($imagenes, 0, 5);
                    }

                    // Insertar cada imagen
                    foreach ($imagenes as $orden => $ruta) {
                        if (empty($ruta)) {
                            continue;
                        }

                        // Extraer información de la ruta
                        $rutaWebp = $ruta;
                        $rutaOriginal = $ruta; // Asumimos que es la misma

                        DB::table('logo_fotos_cot')->insert([
                            'logo_cotizacion_id' => $logo->id,
                            'ruta_original' => $rutaOriginal,
                            'ruta_webp' => $rutaWebp,
                            'ruta_miniatura' => null,
                            'orden' => $orden,
                            'ancho' => null,
                            'alto' => null,
                            'tamano' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $totalMigradas++;
                    }

                    $this->line(" Logo {$logo->id}: " . count($imagenes) . " imagen(es) migrada(s)");
                } catch (\Exception $e) {
                    $this->error(" Error migrando logo {$logo->id}: " . $e->getMessage());
                    $errores++;
                }
            }

            $this->newLine();
            $this->info("📈 RESUMEN:");
            $this->line("    Imágenes migradas: {$totalMigradas}");
            $this->line("    Errores: {$errores}");

            if ($errores === 0) {
                $this->info(" MIGRACIÓN COMPLETADA SIN ERRORES");
            } else {
                $this->warn(" MIGRACIÓN COMPLETADA CON ERRORES");
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
