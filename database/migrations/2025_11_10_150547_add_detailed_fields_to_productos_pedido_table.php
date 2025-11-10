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
        Schema::table('productos_pedido', function (Blueprint $table) {
            // Campos adicionales para capturar información detallada de los correos
            $table->string('tela')->nullable()->after('nombre_producto');
            $table->string('tipo_manga')->nullable()->after('tela');
            $table->string('color')->nullable()->after('tipo_manga');
            $table->string('genero')->nullable()->after('talla'); // Dama, Caballero, Unisex
            $table->string('ref_hilo')->nullable()->after('genero');
            $table->text('bordados')->nullable()->after('descripcion'); // Información de logos y bordados
            $table->string('modelo_foto')->nullable()->after('bordados'); // URL o referencia de foto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos_pedido', function (Blueprint $table) {
            $table->dropColumn([
                'tela',
                'tipo_manga',
                'color',
                'genero',
                'ref_hilo',
                'bordados',
                'modelo_foto'
            ]);
        });
    }
};
