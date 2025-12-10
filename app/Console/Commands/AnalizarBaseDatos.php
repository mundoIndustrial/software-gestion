<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalizarBaseDatos extends Command
{
    protected $signature = 'db:analizar {tabla?}';
    protected $description = 'Analizar estructura y datos de la base de datos';

    public function handle()
    {
        $tabla = $this->argument('tabla');

        if ($tabla) {
            $this->analizarTabla($tabla);
        } else {
            $this->analizarTodasLasTablas();
        }
    }

    /**
     * Analizar todas las tablas
     */
    private function analizarTodasLasTablas()
    {
        $this->info('📊 ANÁLISIS COMPLETO DE LA BASE DE DATOS');
        $this->line('');

        // Obtener todas las tablas
        $tablas = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");

        foreach ($tablas as $tabla) {
            $nombreTabla = $tabla->TABLE_NAME;
            $this->analizarTabla($nombreTabla);
            $this->line('');
        }

        $this->info('✅ Análisis completado');
    }

    /**
     * Analizar una tabla específica
     */
    private function analizarTabla($nombreTabla)
    {
        $this->info("📋 Tabla: {$nombreTabla}");
        $this->line(str_repeat('-', 80));

        // Verificar si la tabla existe
        if (!Schema::hasTable($nombreTabla)) {
            $this->error("❌ La tabla '{$nombreTabla}' no existe");
            return;
        }

        // Obtener estructura
        $columnas = DB::select("DESCRIBE {$nombreTabla}");
        $this->line('🔧 ESTRUCTURA:');
        $this->table(
            ['Campo', 'Tipo', 'Nulo', 'Clave', 'Por Defecto', 'Extra'],
            array_map(function ($col) {
                return [
                    $col->Field,
                    $col->Type,
                    $col->Null === 'YES' ? '✓' : '✗',
                    $col->Key ?: '-',
                    $col->Default ?? '-',
                    $col->Extra ?: '-'
                ];
            }, $columnas)
        );

        // Contar registros
        $count = DB::table($nombreTabla)->count();
        $this->line('');
        $this->info("📈 REGISTROS: {$count}");

        // Mostrar primeros registros
        if ($count > 0) {
            $this->line('');
            $this->line('📝 PRIMEROS 5 REGISTROS:');
            $registros = DB::table($nombreTabla)->limit(5)->get();

            if ($registros->isNotEmpty()) {
                $headers = array_keys((array) $registros[0]);
                $datos = $registros->map(function ($reg) use ($headers) {
                    return array_map(function ($header) use ($reg) {
                        $valor = $reg->{$header};
                        if (is_null($valor)) {
                            return 'NULL';
                        }
                        if (strlen($valor) > 50) {
                            return substr($valor, 0, 47) . '...';
                        }
                        return $valor;
                    }, $headers);
                })->toArray();

                $this->table($headers, $datos);
            }
        }

        // Información de índices
        $indices = DB::select("SHOW INDEXES FROM {$nombreTabla}");
        if (!empty($indices)) {
            $this->line('');
            $this->line('🔑 ÍNDICES:');
            $this->table(
                ['Tabla', 'Columna', 'Nombre Índice', 'Único', 'Tipo'],
                array_map(function ($idx) {
                    return [
                        $idx->Table,
                        $idx->Column_name,
                        $idx->Key_name,
                        $idx->Non_unique === 0 ? '✓' : '✗',
                        $idx->Index_type
                    ];
                }, $indices)
            );
        }

        // Foreign keys
        $fks = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$nombreTabla]);

        if (!empty($fks)) {
            $this->line('');
            $this->line('🔗 FOREIGN KEYS:');
            $this->table(
                ['Restricción', 'Columna', 'Tabla Referenciada', 'Columna Referenciada'],
                array_map(function ($fk) {
                    return [
                        $fk->CONSTRAINT_NAME,
                        $fk->COLUMN_NAME,
                        $fk->REFERENCED_TABLE_NAME,
                        $fk->REFERENCED_COLUMN_NAME
                    ];
                }, $fks)
            );
        }
    }
}
