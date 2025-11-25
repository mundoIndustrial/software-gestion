<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;

class MapearAsesorasYClientesTablaOriginal extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mapear:asesoras-clientes-tabla-original {--dry-run}';

    /**
     * The console command description.
     */
    protected $description = 'Mapea asesoras de tabla_original a users y clientes a clientes tabla. Crea registros si no existen.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('=== MAPEO DE ASESORAS Y CLIENTES (tabla_original) ===');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: Sin cambios en base de datos');
            $this->newLine();
        }

        // 1. OBTENER TODAS LAS ASESORAS DE tabla_original
        $this->info('1️⃣  Procesando ASESORAS de tabla_original...');
        
        $asesorasOriginales = DB::table('tabla_original')
            ->whereNotNull('asesora')
            ->where('asesora', '!=', '')
            ->distinct('asesora')
            ->pluck('asesora')
            ->toArray();

        $this->line("   Encontradas: " . count($asesorasOriginales) . " asesoras únicas");

        // Mapear asesoras a users
        $asesorasMap = [];
        $creadas = 0;
        $existentes = 0;
        $skipped = 0;
        $problemas = [];

        foreach ($asesorasOriginales as $nombreAsesora) {
            // Normalizar nombre
            $nombreNormalizado = trim($nombreAsesora);
            
            // SKIP: Si parece ser una fecha o valor inválido
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $nombreNormalizado) || 
                in_array(strtoupper($nombreNormalizado), ['ANULADO', 'ANULADA', 'CREDITO', 'CONTADO', '-', '---'])) {
                $skipped++;
                continue;
            }
            
            // Buscar en users (case-insensitive)
            $usuario = User::whereRaw('LOWER(name) = ?', [strtolower($nombreNormalizado)])->first();

            if ($usuario) {
                $asesorasMap[$nombreAsesora] = $usuario->id;
                $existentes++;
            } else {
                // Crear usuario
                if (!$dryRun) {
                    try {
                        $usuario = User::create([
                            'name' => $nombreNormalizado,
                            'email' => strtolower(str_replace(' ', '.', $nombreNormalizado)) . '@mundoindustrial.local',
                            'password' => bcrypt(uniqid()),
                            'role_id' => 2,
                        ]);
                        $asesorasMap[$nombreAsesora] = $usuario->id;
                        $creadas++;
                    } catch (\Exception $e) {
                        $problemas[] = "Error creando asesora '$nombreAsesora': " . $e->getMessage();
                    }
                } else {
                    $this->line("   [DRY-RUN] Crear usuario: $nombreNormalizado");
                    $creadas++;
                }
            }
        }

        $this->line("   ✓ Existentes: $existentes | Crear: $creadas | Skipped: $skipped");

        if (!empty($problemas)) {
            $this->error("   Problemas encontrados:");
            foreach ($problemas as $problema) {
                $this->error("   - $problema");
            }
        }

        // 2. OBTENER TODOS LOS CLIENTES DE tabla_original
        $this->newLine();
        $this->info('2️⃣  Procesando CLIENTES de tabla_original...');
        
        $clientesOriginales = DB::table('tabla_original')
            ->whereNotNull('cliente')
            ->where('cliente', '!=', '')
            ->distinct('cliente')
            ->pluck('cliente')
            ->toArray();

        $this->line("   Encontrados: " . count($clientesOriginales) . " clientes únicos");

        // Mapear clientes
        $clientesMap = [];
        $creados = 0;
        $existentes = 0;
        $skipped = 0;
        $problemas = [];

        foreach ($clientesOriginales as $nombreCliente) {
            // Normalizar nombre
            $nombreNormalizado = trim($nombreCliente);
            
            // SKIP: Si es valor inválido
            if (in_array(strtoupper($nombreNormalizado), ['ANULADO', 'ANULADA', '-', '---']) || strlen($nombreNormalizado) < 2) {
                $skipped++;
                continue;
            }
            
            // Buscar en clientes (case-insensitive)
            $cliente = Cliente::whereRaw('LOWER(nombre) = ?', [strtolower($nombreNormalizado)])->first();

            if ($cliente) {
                $clientesMap[$nombreCliente] = $cliente->id;
                $existentes++;
            } else {
                // Crear cliente
                if (!$dryRun) {
                    try {
                        $cliente = Cliente::create([
                            'nombre' => $nombreNormalizado,
                            'email' => null,
                            'telefono' => null,
                            'ciudad' => null,
                            'notas' => 'Creado automaticamente desde tabla_original',
                        ]);
                        $clientesMap[$nombreCliente] = $cliente->id;
                        $creados++;
                    } catch (\Exception $e) {
                        $problemas[] = "Error creando cliente '$nombreCliente': " . $e->getMessage();
                    }
                } else {
                    $this->line("   [DRY-RUN] Crear cliente: $nombreNormalizado");
                    $creados++;
                }
            }
        }

        $this->line("   ✓ Existentes: $existentes | Crear: $creados | Skipped: $skipped");

        if (!empty($problemas)) {
            $this->error("   Problemas encontrados:");
            foreach ($problemas as $problema) {
                $this->error("   - $problema");
            }
        }

        // 3. ACTUALIZAR tabla_original CON user_id y cliente_id
        $this->newLine();
        $this->info('3️⃣  Actualizando tabla_original con mapeos...');

        $actualizados = 0;
        $sinMapeo = [];

        $registros = DB::table('tabla_original')->get();

        foreach ($registros as $registro) {
            $cambios = [];

            // Mapear asesora
            if ($registro->asesora && isset($asesorasMap[$registro->asesora])) {
                $cambios['asesora_id'] = $asesorasMap[$registro->asesora];
            }

            // Mapear cliente
            if ($registro->cliente && isset($clientesMap[$registro->cliente])) {
                $cambios['cliente_id_nuevo'] = $clientesMap[$registro->cliente];
            }

            if (!empty($cambios)) {
                if (!$dryRun) {
                    DB::table('tabla_original')
                        ->where('pedido', $registro->pedido)
                        ->update($cambios);
                }
                $actualizados++;
            }
        }

        $this->line("   ✓ Actualizados: $actualizados registros");

        // 4. RESUMEN
        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line("Asesoras procesadas: " . count($asesorasOriginales) . " (skipped: $skipped)");
        $this->line("Clientes procesados: " . count($clientesOriginales) . " (skipped: $skipped)");
        $this->line("Registros actualizados: $actualizados");

        if ($dryRun) {
            $this->info("\n✓ Dry-run completado. Sin cambios en base de datos.");
            $this->info("Ejecuta sin --dry-run para aplicar cambios.");
        } else {
            $this->info("\n✓ Mapeo completado exitosamente!");
        }
    }
}
