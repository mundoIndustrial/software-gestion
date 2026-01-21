<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Modifica la tabla epps con los campos acordados
     */
    public function up(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            // Agregar nuevo campo nombre_completo si no existe
            if (!Schema::hasColumn('epps', 'nombre_completo')) {
                $table->string('nombre_completo', 500)->nullable()->after('codigo');
            }
            
            // Agregar marca si no existe
            if (!Schema::hasColumn('epps', 'marca')) {
                $table->string('marca', 100)->nullable()->after('nombre_completo');
            }
            
            // Agregar tipo (PRODUCTO o SERVICIO) si no existe
            if (!Schema::hasColumn('epps', 'tipo')) {
                $table->enum('tipo', ['PRODUCTO', 'SERVICIO'])->default('PRODUCTO')->after('categoria_id');
            }
            
            // Agregar talla si no existe
            if (!Schema::hasColumn('epps', 'talla')) {
                $table->string('talla', 100)->nullable()->after('tipo');
            }
            
            // Agregar color si no existe
            if (!Schema::hasColumn('epps', 'color')) {
                $table->string('color', 100)->nullable()->after('talla');
            }
        });
        
        // Copiar datos del campo nombre a nombre_completo si existe nombre
        if (Schema::hasColumn('epps', 'nombre') && Schema::hasColumn('epps', 'nombre_completo')) {
            DB::statement('UPDATE epps SET nombre_completo = nombre WHERE nombre_completo IS NULL');
        }
        
        // Eliminar tallas_disponibles (JSON obsoleto) si existe
        if (Schema::hasColumn('epps', 'tallas_disponibles')) {
            Schema::table('epps', function (Blueprint $table) {
                $table->dropColumn('tallas_disponibles');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            // Remover las nuevas columnas
            if (Schema::hasColumn('epps', 'nombre_completo')) {
                $table->dropColumn('nombre_completo');
            }
            if (Schema::hasColumn('epps', 'marca')) {
                $table->dropColumn('marca');
            }
            if (Schema::hasColumn('epps', 'tipo')) {
                $table->dropColumn('tipo');
            }
            if (Schema::hasColumn('epps', 'talla')) {
                $table->dropColumn('talla');
            }
            if (Schema::hasColumn('epps', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
