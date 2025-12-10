<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Remover columnas de variantes de prendas_cot
        Schema::table('prendas_cot', function (Blueprint $table) {
            $columnsToRemove = [
                'tipo_prenda',
                'es_jean_pantalon',
                'tipo_jean_pantalon',
                'genero',
                'color',
                'tiene_bolsillos',
                'obs_bolsillos',
                'aplica_manga',
                'tipo_manga',
                'obs_manga',
                'aplica_broche',
                'tipo_broche_id',
                'obs_broche',
                'tiene_reflectivo',
                'obs_reflectivo',
                'descripcion_adicional',
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('prendas_cot', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // 2. Actualizar prenda_variantes_cot para incluir todos los campos de variantes
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            // Campos básicos
            if (!Schema::hasColumn('prenda_variantes_cot', 'tipo_prenda')) {
                $table->string('tipo_prenda')->nullable()->after('prenda_cot_id')->comment('Tipo de prenda: CAMISA, PANTALON, POLO, etc.');
            }
            
            // Campos específicos para JEAN/PANTALÓN
            if (!Schema::hasColumn('prenda_variantes_cot', 'es_jean_pantalon')) {
                $table->boolean('es_jean_pantalon')->default(false)->after('tipo_prenda')->comment('¿Es jean o pantalón?');
            }
            if (!Schema::hasColumn('prenda_variantes_cot', 'tipo_jean_pantalon')) {
                $table->string('tipo_jean_pantalon')->nullable()->after('es_jean_pantalon')->comment('JEAN, PANTALON, OTRO');
            }
            
            // Campos de variantes principales
            if (!Schema::hasColumn('prenda_variantes_cot', 'genero')) {
                $table->string('genero')->nullable()->after('tipo_jean_pantalon')->comment('dama, caballero, niño, unisex');
            }
            if (!Schema::hasColumn('prenda_variantes_cot', 'color')) {
                $table->string('color')->nullable()->after('genero')->comment('Color principal');
            }
            
            // Campos de opciones
            if (!Schema::hasColumn('prenda_variantes_cot', 'tiene_bolsillos')) {
                $table->boolean('tiene_bolsillos')->default(false)->after('color')->comment('¿Tiene bolsillos?');
            }
            if (!Schema::hasColumn('prenda_variantes_cot', 'obs_bolsillos')) {
                $table->text('obs_bolsillos')->nullable()->after('tiene_bolsillos')->comment('Observaciones de bolsillos');
            }
            
            if (!Schema::hasColumn('prenda_variantes_cot', 'aplica_manga')) {
                $table->boolean('aplica_manga')->default(false)->after('obs_bolsillos')->comment('¿Aplica manga?');
            }
            if (!Schema::hasColumn('prenda_variantes_cot', 'tipo_manga')) {
                $table->string('tipo_manga')->nullable()->after('aplica_manga')->comment('Tipo de manga');
            }
            if (!Schema::hasColumn('prenda_variantes_cot', 'obs_manga')) {
                $table->text('obs_manga')->nullable()->after('tipo_manga')->comment('Observaciones de manga');
            }
            
            if (!Schema::hasColumn('prenda_variantes_cot', 'aplica_broche')) {
                $table->boolean('aplica_broche')->default(false)->after('obs_manga')->comment('¿Aplica broche?');
            }
            if (!Schema::hasColumn('prenda_variantes_cot', 'tipo_broche_id')) {
                $table->unsignedBigInteger('tipo_broche_id')->nullable()->after('aplica_broche')->comment('ID del tipo de broche');
            }
            if (!Schema::hasColumn('prenda_variantes_cot', 'obs_broche')) {
                $table->text('obs_broche')->nullable()->after('tipo_broche_id')->comment('Observaciones de broche');
            }
            
            if (!Schema::hasColumn('prenda_variantes_cot', 'tiene_reflectivo')) {
                $table->boolean('tiene_reflectivo')->default(false)->after('obs_broche')->comment('¿Tiene reflectivo?');
            }
            if (!Schema::hasColumn('prenda_variantes_cot', 'obs_reflectivo')) {
                $table->text('obs_reflectivo')->nullable()->after('tiene_reflectivo')->comment('Observaciones de reflectivo');
            }
            
            // Campo de descripción adicional
            if (!Schema::hasColumn('prenda_variantes_cot', 'descripcion_adicional')) {
                $table->text('descripcion_adicional')->nullable()->after('obs_reflectivo')->comment('Descripción adicional de variantes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover columnas de prenda_variantes_cot
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            $columnsToRemove = [
                'tipo_prenda',
                'es_jean_pantalon',
                'tipo_jean_pantalon',
                'genero',
                'color',
                'tiene_bolsillos',
                'obs_bolsillos',
                'aplica_manga',
                'tipo_manga',
                'obs_manga',
                'aplica_broche',
                'tipo_broche_id',
                'obs_broche',
                'tiene_reflectivo',
                'obs_reflectivo',
                'descripcion_adicional',
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('prenda_variantes_cot', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Restaurar columnas en prendas_cot
        Schema::table('prendas_cot', function (Blueprint $table) {
            $table->string('tipo_prenda')->nullable();
            $table->boolean('es_jean_pantalon')->default(false);
            $table->string('tipo_jean_pantalon')->nullable();
            $table->string('genero')->nullable();
            $table->string('color')->nullable();
            $table->boolean('tiene_bolsillos')->default(false);
            $table->text('obs_bolsillos')->nullable();
            $table->boolean('aplica_manga')->default(false);
            $table->string('tipo_manga')->nullable();
            $table->text('obs_manga')->nullable();
            $table->boolean('aplica_broche')->default(false);
            $table->unsignedBigInteger('tipo_broche_id')->nullable();
            $table->text('obs_broche')->nullable();
            $table->boolean('tiene_reflectivo')->default(false);
            $table->text('obs_reflectivo')->nullable();
            $table->text('descripcion_adicional')->nullable();
        });
    }
};
