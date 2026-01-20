<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalizarEstructuraTablas extends Command
{
    protected $signature = 'analizar:tablas {tabla?}';
    protected $description = 'Analizar estructura de tablas en la BD (prenda_fotos_cot, prenda_telas_cot, logo_fotos_cot)';

    public function handle()
    {
        $tablas = [
            'prenda_fotos_cot',
            'prenda_telas_cot',
            'prenda_tela_fotos_cot',
            'logo_fotos_cot',
        ];

        $tablaEspecifica = $this->argument('tabla');

        if ($tablaEspecifica) {
            $tablas = [$tablaEspecifica];
        }

        foreach ($tablas as $tabla) {
            $this->analizarTabla($tabla);
        }
    }

    private function analizarTabla($tabla)
    {
        $this->info("\n" . str_repeat('=', 80));
        $this->info("TABLA: {$tabla}");
        $this->info(str_repeat('=', 80));

        try {
            // Verificar si la tabla existe
            $existe = DB::select("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [
                env('DB_DATABASE'),
                $tabla
            ]);

            if (empty($existe)) {
                $this->error(" La tabla '{$tabla}' NO EXISTE en la BD");
                return;
            }

            $this->info(" La tabla '{$tabla}' EXISTE");

            // Obtener estructura de columnas
            $columnas = DB::select("DESCRIBE {$tabla}");

            $this->info("\n COLUMNAS:");
            $this->info(str_repeat('-', 80));

            foreach ($columnas as $columna) {
                $tipo = $columna->Type;
                $nulo = $columna->Null === 'YES' ? '✓ NULL' : '✗ NOT NULL';
                $default = $columna->Default !== null ? "DEFAULT: {$columna->Default}" : 'SIN DEFAULT';
                $extra = $columna->Extra ? "({$columna->Extra})" : '';

                $this->line(sprintf(
                    "  • %-25s | %-20s | %s | %s %s",
                    $columna->Field,
                    $tipo,
                    $nulo,
                    $default,
                    $extra
                ));
            }

            // Obtener claves
            $this->info("\n🔑 CLAVES:");
            $this->info(str_repeat('-', 80));

            $claves = DB::select("
                SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ", [env('DB_DATABASE'), $tabla]);

            if (!empty($claves)) {
                foreach ($claves as $clave) {
                    if ($clave->REFERENCED_TABLE_NAME) {
                        $this->line("  • FK: {$clave->CONSTRAINT_NAME} → {$clave->REFERENCED_TABLE_NAME}({$clave->REFERENCED_COLUMN_NAME})");
                    } else {
                        $this->line("  • PK: {$clave->CONSTRAINT_NAME} ({$clave->COLUMN_NAME})");
                    }
                }
            } else {
                $this->line("  (Sin claves foráneas)");
            }

            // Obtener índices
            $this->info("\n📑 ÍNDICES:");
            $this->info(str_repeat('-', 80));

            $indices = DB::select("SHOW INDEX FROM {$tabla}");

            if (!empty($indices)) {
                foreach ($indices as $indice) {
                    $this->line("  • {$indice->Key_name}: {$indice->Column_name}");
                }
            } else {
                $this->line("  (Sin índices)");
            }

            // Obtener cantidad de registros
            $this->info("\n📊 DATOS:");
            $this->info(str_repeat('-', 80));

            $count = DB::table($tabla)->count();
            $this->line("  • Total de registros: {$count}");

            if ($count > 0) {
                $primerRegistro = DB::table($tabla)->first();
                $this->line("\n  📌 Primer registro:");
                foreach ((array) $primerRegistro as $campo => $valor) {
                    $valor = is_null($valor) ? '(NULL)' : substr($valor, 0, 50);
                    $this->line("     - {$campo}: {$valor}");
                }
            }

        } catch (\Exception $e) {
            $this->error(" Error al analizar tabla '{$tabla}': " . $e->getMessage());
        }
    }
}
