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
        // Primero eliminar todos los procesos actuales (solo registros de prueba)
        \Illuminate\Support\Facades\DB::table('procesos_prenda')->truncate();
        
        // Verificar si ya existe la columna numero_pedido
        if(!Schema::hasColumn('procesos_prenda', 'numero_pedido')) {
            // Eliminar prenda_pedido_id si existe
            if(Schema::hasColumn('procesos_prenda', 'prenda_pedido_id')) {
                Schema::table('procesos_prenda', function (Blueprint $table) {
                    // Intentar eliminar foreign key si existe
                    try {
                        DB::statement('ALTER TABLE procesos_prenda DROP FOREIGN KEY procesos_prenda_prenda_pedido_id_foreign');
                    } catch (\Exception $e) {
                        // Ignorar si no existe
                    }
                    $table->dropColumn('prenda_pedido_id');
                });
            }

            // Agregar columna numero_pedido
            Schema::table('procesos_prenda', function (Blueprint $table) {
                $table->unsignedInteger('numero_pedido')->after('id');
                $table->foreign('numero_pedido')->references('numero_pedido')->on('pedidos_produccion')->onDelete('cascade');
                $table->index('numero_pedido');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            try {
                $table->dropForeign(['numero_pedido']);
            } catch (\Exception $e) {
                // Ignorar
            }
            $table->dropColumn('numero_pedido');
        });

        Schema::table('procesos_prenda', function (Blueprint $table) {
            $table->foreignId('prenda_pedido_id')->constrained('prendas_pedido')->onDelete('cascade');
        });
    }
};
