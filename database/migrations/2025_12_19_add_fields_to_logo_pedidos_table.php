<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos necesarios para que logo_pedidos sea independiente de pedidos_produccion
     */
    public function up(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            // Información del cliente y asesora
            $table->string('cliente')->nullable()->after('logo_cotizacion_id');
            $table->string('asesora')->nullable()->after('cliente');
            
            // Forma de pago
            $table->string('forma_de_pago')->nullable()->after('asesora');
            
            // Encargado de orden
            $table->string('encargado_orden')->nullable()->after('forma_de_pago');
            
            // Fecha de creación del pedido
            $table->timestamp('fecha_de_creacion_de_orden')->nullable()->after('encargado_orden');
            
            // Estado del pedido de logo
            $table->string('estado')->default('pendiente')->index()->after('fecha_de_creacion_de_orden');
            
            // Cotización del logo (número)
            $table->string('numero_cotizacion')->nullable()->index()->after('estado');
            
            // ID de cotización para relación
            $table->unsignedBigInteger('cotizacion_id')->nullable()->index()->after('numero_cotizacion');
            
            // Prendas asociadas (JSON array con info)
            $table->json('prendas')->nullable()->after('cotizacion_id');
            
            // Observaciones generales
            $table->longText('observaciones')->nullable()->after('prendas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            $table->dropColumn([
                'cliente',
                'asesora',
                'forma_de_pago',
                'encargado_orden',
                'fecha_de_creacion_de_orden',
                'estado',
                'numero_cotizacion',
                'cotizacion_id',
                'prendas',
                'observaciones'
            ]);
        });
    }
};
