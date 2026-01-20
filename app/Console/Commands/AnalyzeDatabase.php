<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyzeDatabase extends Command
{
    protected $signature = 'db:analyze';
    protected $description = 'Analiza las tablas de la base de datos y muestra cuáles faltan';

    public function handle()
    {
        $this->info(' Analizando base de datos...\n');

        // Obtener todas las tablas actuales
        $tablasActuales = DB::select('SHOW TABLES');
        $nombreTablas = array_map(function ($tabla) {
            $key = array_key_first((array)$tabla);
            return $tabla->$key;
        }, $tablasActuales);

        $this->info(" Tablas encontradas: " . count($nombreTablas) . "\n");

        // Tablas que deberían existir según el sistema
        $tablasEsperadas = [
            // Tablas base
            'users',
            'roles',
            'permisos',
            'clientes',
            'cotizaciones',
            'pedidos_produccion',
            'prendas',
            'prendas_pedido',
            'prenda_talla_pedido',
            
            // Tablas de EPP
            'epps',
            'epp_categorias',
            'epp_imagenes',
            'pedido_epp',
            'pedido_epp_imagenes',
            
            // Tablas de procesos
            'tipos_procesos',
            'procesos_prenda',
            'procesos_prenda_detalles',
            'procesos_prenda_imagenes',
            'pedidos_procesos_prenda_detalles',
            'pedidos_procesos_imagenes',
            
            // Tablas de logos
            'logos',
            'logo_pedidos',
            'logo_pedido_imagenes',
            'procesos_pedidos_logo',
            
            // Tablas de catálogos
            'catalogo_telas',
            'catalogo_colores',
            'catalogo_hilos',
            'tipos_prenda',
            'generos',
            'tallas',
            'colores_prenda',
            'tela_prenda',
            'prenda_reflectivo',
            'tipo_broche',
            'tipo_manga',
            
            // Tablas de inventario
            'inventario_tela',
            'materiales_orden_insumos',
            
            // Tablas de producción
            'maquinas',
            'registro_piso_corte',
            'registro_piso_polo',
            'registro_piso_produccion',
            'ordenes_asesor',
            'entrega_bodega_corte',
            'entrega_bodega_costura',
            'entrega_pedido_corte',
            'entrega_pedido_costura',
            
            // Tablas de auditoria
            'historial_cambios_pedido',
            'historial_cambios_cotizacion',
            'historial_cotizacion',
            
            // Tablas de empleados
            'personal',
            'horario_por_rol',
            'valor_hora_extra',
            'festivos',
            'registros_de_huella',
            'registro_horas_huella',
            
            // Otras tablas
            'news',
            'reportes',
            'reportes_personal',
            'operacion_balanceo',
            'balanceo',
        ];

        // Tablas que faltan
        $tablasFaltantes = array_diff($tablasEsperadas, $nombreTablas);
        $tablasExtras = array_diff($nombreTablas, $tablasEsperadas);

        if (!empty($tablasFaltantes)) {
            $this->error("\n TABLAS FALTANTES: " . count($tablasFaltantes));
            $this->table(['Tabla'], array_map(fn($t) => [$t], array_values($tablasFaltantes)));
        } else {
            $this->info("\n Todas las tablas esperadas existen");
        }

        if (!empty($tablasExtras)) {
            $this->warn("\n TABLAS EXTRAS (no esperadas): " . count($tablasExtras));
            $this->table(['Tabla'], array_map(fn($t) => [$t], array_values($tablasExtras)));
        }

        // Analizar columnas de tablas importantes
        $this->info("\n\n ANÁLISIS DETALLADO DE TABLAS CRÍTICAS:\n");

        $tablasAnalizar = ['epps', 'epp_categorias', 'pedido_epp', 'pedido_epp_imagenes', 'pedidos_produccion'];

        foreach ($tablasAnalizar as $tabla) {
            if (in_array($tabla, $nombreTablas)) {
                $this->info(" $tabla");
                
                $columnas = DB::select("DESCRIBE $tabla");
                foreach ($columnas as $col) {
                    $tipo = $col->Type;
                    $nullable = $col->Null === 'YES' ? '(nullable)' : '';
                    $this->line("   • {$col->Field} - {$tipo} {$nullable}");
                }
                $this->line('');
            } else {
                $this->error(" $tabla - NO EXISTE");
            }
        }

        // Resumen final
        $this->info("\n📈 RESUMEN:");
        $this->line("   • Tablas actuales: " . count($nombreTablas));
        $this->line("   • Tablas esperadas: " . count($tablasEsperadas));
        $this->line("   • Tablas faltantes: " . count($tablasFaltantes));
        $this->line("   • Tablas extras: " . count($tablasExtras));

        return 0;
    }
}
