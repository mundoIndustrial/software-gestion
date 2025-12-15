<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateNumeroCotizacionSync extends Command
{
    protected $signature = 'validate:numero-sync';
    protected $description = 'Validar que la generación sincrónica de números funciona correctamente';

    public function handle()
    {
        $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("✅ VALIDACIÓN: Generación Sincrónica de Números");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        try {
            // TEST 1: Verificar tabla existe
            $this->info("TEST 1: Verificar tabla numero_secuencias");
            $secuencias = DB::table('numero_secuencias')->get();
            $this->info("✅ Secuencias encontradas: " . $secuencias->count());
            foreach ($secuencias as $s) {
                $this->line("   - {$s->tipo}: siguiente = {$s->siguiente}");
            }
            $this->line("");

            // TEST 2: Generar 3 números
            $this->info("TEST 2: Generar 3 números secuenciales con lock");
            $numeros = [];
            for ($i = 0; $i < 3; $i++) {
                $numero = DB::transaction(function () {
                    $sec = DB::table('numero_secuencias')
                        ->lockForUpdate()
                        ->where('tipo', 'cotizaciones_prenda')
                        ->first();
                    
                    $siguiente = $sec->siguiente;
                    DB::table('numero_secuencias')
                        ->where('tipo', 'cotizaciones_prenda')
                        ->update(['siguiente' => $siguiente + 1]);
                    
                    return 'COT-' . date('Ymd') . '-' . str_pad($siguiente, 3, '0', STR_PAD_LEFT);
                });
                
                $numeros[] = $numero;
                $this->line("   " . ($i + 1) . ". $numero");
            }
            $this->line("");

            // TEST 3: Verificar duplicados
            $this->info("TEST 3: Verificar números únicos");
            $unicos = array_unique($numeros);
            $this->line("   Total: " . count($numeros));
            $this->line("   Únicos: " . count($unicos));

            if (count($unicos) === count($numeros)) {
                $this->line("   ✅ ¡NO HAY DUPLICADOS!");
            } else {
                $this->error("   ❌ ERROR: Hay duplicados");
                return 1;
            }
            $this->line("");

            // TEST 4: Verificar formato
            $this->info("TEST 4: Verificar formato COT-YYYYMMDD-NNN");
            $patron = '/^COT-\d{8}-\d{3}$/';
            $todosValidos = true;
            foreach ($numeros as $num) {
                if (!preg_match($patron, $num)) {
                    $this->error("   ❌ Inválido: $num");
                    $todosValidos = false;
                }
            }
            if ($todosValidos) {
                $this->line("   ✅ Todos los formatos son correctos");
            } else {
                return 1;
            }
            $this->line("");

            // TEST 5: Diferentes tipos no interfieren
            $this->info("TEST 5: Diferentes tipos de secuencia");
            $numeroPrenda = DB::transaction(function () {
                $sec = DB::table('numero_secuencias')
                    ->lockForUpdate()
                    ->where('tipo', 'cotizaciones_prenda')
                    ->first();
                
                $siguiente = $sec->siguiente;
                DB::table('numero_secuencias')
                    ->where('tipo', 'cotizaciones_prenda')
                    ->update(['siguiente' => $siguiente + 1]);
                
                return 'COT-' . date('Ymd') . '-' . str_pad($siguiente, 3, '0', STR_PAD_LEFT);
            });

            $numeroBordado = DB::transaction(function () {
                $sec = DB::table('numero_secuencias')
                    ->lockForUpdate()
                    ->where('tipo', 'cotizaciones_bordado')
                    ->first();
                
                $siguiente = $sec->siguiente;
                DB::table('numero_secuencias')
                    ->where('tipo', 'cotizaciones_bordado')
                    ->update(['siguiente' => $siguiente + 1]);
                
                return 'COT-' . date('Ymd') . '-' . str_pad($siguiente, 3, '0', STR_PAD_LEFT);
            });

            $this->line("   Prenda:  $numeroPrenda");
            $this->line("   Bordado: $numeroBordado");

            if ($numeroPrenda !== $numeroBordado) {
                $this->line("   ✅ Diferentes tipos no interfieren");
            } else {
                $this->error("   ❌ ERROR: Tipos interfieren");
                return 1;
            }
            $this->line("");

            // TEST 6: Estado final
            $this->info("TEST 6: Estado final de secuencias");
            $secuenciasFinales = DB::table('numero_secuencias')->get();
            foreach ($secuenciasFinales as $s) {
                $this->line("   - {$s->tipo}: siguiente = {$s->siguiente}");
            }

            $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✅ TODOS LOS TESTS COMPLETADOS CON ÉXITO");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

            return 0;

        } catch (\Exception $e) {
            $this->error("\n❌ ERROR: " . $e->getMessage());
            $this->error($e->getFile() . ":" . $e->getLine() . "\n");
            return 1;
        }
    }
}
