<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerificarRelacionesImagenes extends Command
{
    protected $signature = 'db:verificar-relaciones-imagenes';
    protected $description = 'Verifica las relaciones entre tablas de imágenes';

    public function handle()
    {
        $this->info('🔍 VERIFICANDO RELACIONES DE IMÁGENES');
        $this->newLine();

        // Verificar tabla logo_fotos_cot
        $this->verificarTablaLogoFotos();

        // Verificar tabla prenda_tela_fotos_cot
        $this->verificarTablaPrendaTelaFotos();

        // Verificar tabla prenda_fotos_cot
        $this->verificarTablaPrendaFotos();

        // Verificar Foreign Keys
        $this->verificarForeignKeys();

        // Verificar datos
        $this->verificarDatos();

        $this->newLine();
        $this->info(' VERIFICACIÓN COMPLETADA');
    }

    private function verificarTablaLogoFotos()
    {
        $this->line(' Tabla: <fg=cyan>logo_fotos_cot</>');

        if (!Schema::hasTable('logo_fotos_cot')) {
            $this->error('    NO EXISTE');
            return;
        }

        $this->info('    EXISTE');

        $columnas = Schema::getColumns('logo_fotos_cot');
        $this->line('   📊 Columnas:');

        foreach ($columnas as $col) {
            $tipo = $col['type'];
            $this->line("      • {$col['name']}: {$tipo}");
        }

        // Verificar FK
        $this->line('   🔑 Foreign Keys:');
        $fks = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'logo_fotos_cot' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (empty($fks)) {
            $this->warn('      ⚠️ No hay Foreign Keys definidas');
        } else {
            foreach ($fks as $fk) {
                $this->line("      • {$fk->CONSTRAINT_NAME}");
                $this->line("        {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})");
            }
        }

        $cantidad = DB::table('logo_fotos_cot')->count();
        $this->line("   📈 Registros: {$cantidad}");
        $this->newLine();
    }

    private function verificarTablaPrendaTelaFotos()
    {
        $this->line(' Tabla: <fg=cyan>prenda_tela_fotos_cot</>');

        if (!Schema::hasTable('prenda_tela_fotos_cot')) {
            $this->error('    NO EXISTE');
            return;
        }

        $this->info('    EXISTE');

        $columnas = Schema::getColumns('prenda_tela_fotos_cot');
        $this->line('   📊 Columnas:');

        foreach ($columnas as $col) {
            $tipo = $col['type'];
            $this->line("      • {$col['name']}: {$tipo}");
        }

        // Verificar FK
        $this->line('   🔑 Foreign Keys:');
        $fks = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'prenda_tela_fotos_cot' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (empty($fks)) {
            $this->warn('      ⚠️ No hay Foreign Keys definidas');
        } else {
            foreach ($fks as $fk) {
                $this->line("      • {$fk->CONSTRAINT_NAME}");
                $this->line("        {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})");
            }
        }

        $cantidad = DB::table('prenda_tela_fotos_cot')->count();
        $this->line("   📈 Registros: {$cantidad}");
        $this->newLine();
    }

    private function verificarTablaPrendaFotos()
    {
        $this->line(' Tabla: <fg=cyan>prenda_fotos_cot</>');

        if (!Schema::hasTable('prenda_fotos_cot')) {
            $this->error('    NO EXISTE');
            return;
        }

        $this->info('    EXISTE');

        $columnas = Schema::getColumns('prenda_fotos_cot');
        $this->line('   📊 Columnas:');

        foreach ($columnas as $col) {
            $tipo = $col['type'];
            $this->line("      • {$col['name']}: {$tipo}");
        }

        // Verificar FK
        $this->line('   🔑 Foreign Keys:');
        $fks = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'prenda_fotos_cot' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (empty($fks)) {
            $this->warn('      ⚠️ No hay Foreign Keys definidas');
        } else {
            foreach ($fks as $fk) {
                $this->line("      • {$fk->CONSTRAINT_NAME}");
                $this->line("        {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})");
            }
        }

        $cantidad = DB::table('prenda_fotos_cot')->count();
        $this->line("   📈 Registros: {$cantidad}");
        $this->newLine();
    }

    private function verificarForeignKeys()
    {
        $this->line('🔗 VERIFICACIÓN DE FOREIGN KEYS');

        $fks = DB::select("
            SELECT 
                TABLE_NAME,
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME IN ('logo_fotos_cot', 'prenda_tela_fotos_cot', 'prenda_fotos_cot')
            AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME
        ");

        if (empty($fks)) {
            $this->warn('   ⚠️ No hay Foreign Keys encontradas');
        } else {
            foreach ($fks as $fk) {
                $this->line("    {$fk->TABLE_NAME}.{$fk->COLUMN_NAME}");
                $this->line("      → {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})");
            }
        }

        $this->newLine();
    }

    private function verificarDatos()
    {
        $this->line('📊 VERIFICACIÓN DE DATOS');

        // Logo fotos
        $logoFotos = DB::table('logo_fotos_cot')->count();
        $this->line("   • logo_fotos_cot: {$logoFotos} registros");

        // Prenda tela fotos
        $telaFotos = DB::table('prenda_tela_fotos_cot')->count();
        $this->line("   • prenda_tela_fotos_cot: {$telaFotos} registros");

        // Prenda fotos
        $prendaFotos = DB::table('prenda_fotos_cot')->count();
        $this->line("   • prenda_fotos_cot: {$prendaFotos} registros");

        // Verificar integridad referencial
        $this->line('   🔍 Integridad Referencial:');

        // Logo fotos sin logo
        $logoFotosSinLogo = DB::table('logo_fotos_cot')
            ->leftJoin('logo_cotizaciones', 'logo_fotos_cot.logo_cotizacion_id', '=', 'logo_cotizaciones.id')
            ->whereNull('logo_cotizaciones.id')
            ->count();

        if ($logoFotosSinLogo > 0) {
            $this->warn("      ⚠️ {$logoFotosSinLogo} foto(s) de logo sin logo asociado");
        } else {
            $this->line('       Todas las fotos de logo tienen logo asociado');
        }

        // Prenda tela fotos sin prenda
        $telaFotosSinPrenda = DB::table('prenda_tela_fotos_cot')
            ->leftJoin('prendas_cot', 'prenda_tela_fotos_cot.prenda_cot_id', '=', 'prendas_cot.id')
            ->whereNull('prendas_cot.id')
            ->count();

        if ($telaFotosSinPrenda > 0) {
            $this->warn("      ⚠️ {$telaFotosSinPrenda} foto(s) de tela sin prenda asociada");
        } else {
            $this->line('       Todas las fotos de tela tienen prenda asociada');
        }

        $this->newLine();
    }
}
