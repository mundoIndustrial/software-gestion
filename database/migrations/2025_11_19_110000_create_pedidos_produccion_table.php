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
        // TABLA PRINCIPAL: PEDIDOS_PRODUCCION
        Schema::create('pedidos_produccion', function (Blueprint $table) {
            $table->id();
            
            // RELACIÓN CON COTIZACIÓN
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            
            // INFORMACIÓN BÁSICA
            $table->unsignedInteger('numero_pedido')->unique();
            $table->string('cliente', 96)->nullable();
            $table->text('novedades')->nullable();
            $table->string('asesora', 111)->nullable();
            $table->string('forma_de_pago', 69)->nullable();
            
            // ESTADO GENERAL DEL PEDIDO
            $table->enum('estado', ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'])->default('No iniciado');
            
            // FECHAS Y DÍAS DE ENTREGA
            $table->date('fecha_de_creacion_de_orden')->nullable();
            $table->integer('dia_de_entrega')->nullable();
            $table->date('fecha_estimada_de_entrega')->nullable();
            
            // METADATA
            $table->timestamps();
            $table->softDeletes();
        });

        // TABLA: PRENDAS_PEDIDO (una prenda por cada artículo del pedido)
        Schema::create('prendas_pedido', function (Blueprint $table) {
            $table->id();
            
            // RELACIÓN CON PEDIDO
            $table->foreignId('pedido_produccion_id')->constrained('pedidos_produccion')->onDelete('cascade');
            
            // INFORMACIÓN DE LA PRENDA
            $table->string('nombre_prenda', 100)->nullable(); // CAMISA, POLO, CAMISETA, etc.
            $table->string('cantidad', 56)->nullable();
            $table->text('descripcion')->nullable();
            
            // METADATA
            $table->timestamps();
            $table->softDeletes();
        });

        // TABLA: PROCESOS_PRENDA (cada proceso que pasa una prenda)
        Schema::create('procesos_prenda', function (Blueprint $table) {
            $table->id();
            
            // RELACIÓN CON PRENDA
            $table->foreignId('prenda_pedido_id')->constrained('prendas_pedido')->onDelete('cascade');
            
            // TIPO DE PROCESO
            $table->enum('proceso', [
                'Creación Orden',
                'Inventario',
                'Insumos y Telas',
                'Corte',
                'Bordado',
                'Estampado',
                'Costura',
                'Reflectivo',
                'Lavandería',
                'Arreglos',
                'Control Calidad',
                'Entrega',
                'Despacho'
            ]);
            
            // INFORMACIÓN DEL PROCESO
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('dias_duracion', 50)->nullable();
            $table->string('encargado', 100)->nullable();
            $table->enum('estado_proceso', ['Pendiente', 'En Progreso', 'Completado', 'Pausado'])->default('Pendiente');
            
            // DETALLES ESPECÍFICOS POR PROCESO
            $table->text('observaciones')->nullable();
            $table->string('codigo_referencia', 100)->nullable(); // Para bordado, estampado, etc.
            
            // METADATA
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos_prenda');
        Schema::dropIfExists('prendas_pedido');
        Schema::dropIfExists('pedidos_produccion');
    }
};
