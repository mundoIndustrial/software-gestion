<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Modifica la tabla epps con los campos acordados:
     * - nombre_completo: texto completo del artículo
     * - marca: marca del producto
     * - categoria_id: relación con epp_categorias
     * - tipo: PRODUCTO o SERVICIO
     * - talla: talla/tamaño específico
     * - color: color del artículo
     * 
     * Elimina: tallas_disponibles (json obsoleto)
     */
    public function up(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            // Agregar nuevo campo nombre_completo
            if (!Schema::hasColumn('epps', 'nombre_completo')) {
                $table->string('nombre_completo', 500)->nullable()->after('codigo');
            }
            
            // Agregar marca
            if (!Schema::hasColumn('epps', 'marca')) {
                $table->string('marca', 100)->nullable()->after('nombre_completo');
            }
            
            // Cambiar categoria a categoria_id con relación
            if (Schema::hasColumn('epps', 'categoria') && !Schema::hasColumn('epps', 'categoria_id')) {
                // Temporalmente agregamos categoria_id
                $table->unsignedBigInteger('categoria_id')->nullable()->after('marca');
            }
            
            // Agregar tipo (PRODUCTO o SERVICIO)
            if (!Schema::hasColumn('epps', 'tipo')) {
                $table->enum('tipo', ['PRODUCTO', 'SERVICIO'])->default('PRODUCTO')->after('categoria_id');
            }
            
            // Agregar talla
            if (!Schema::hasColumn('epps', 'talla')) {
                $table->string('talla', 100)->nullable()->after('tipo');
            }
            
            // Agregar color
            if (!Schema::hasColumn('epps', 'color')) {
                $table->string('color', 100)->nullable()->after('talla');
            }
            
            // Eliminar tallas_disponibles (json obsoleto)
            if (Schema::hasColumn('epps', 'tallas_disponibles')) {
                $table->dropColumn('tallas_disponibles');
            }
            
            // Agregar foreign key a epp_categorias
            if (!Schema::hasColumn('epps', 'fk_constraint_exists')) {
                try {
                    $table->foreign('categoria_id')
                        ->references('id')
                        ->on('epp_categorias')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                } catch (\Exception $e) {
                    // La constraint ya existe o hay error, ignorar
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            // Eliminar foreign key si existe
            try {
                $table->dropForeign(['categoria_id']);
            } catch (\Exception $e) {
                // No existe, ignorar
            }
            
            // Remover las nuevas columnas
            $table->dropColumn([
                'nombre_completo',
                'marca',
                'tipo',
                'talla',
                'color'
            ]);
            
            // Restaurar tallas_disponibles
            $table->json('tallas_disponibles')->nullable();
        });
    }
};
