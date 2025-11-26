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
            
            // RELACIÓN CON COTIZACIÓN (nullable)
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->onDelete('cascade');
            
            // IDs de usuario y cliente (para vincular con asesor y cliente)
            $table->foreignId('asesor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            
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
            
            // INFORMACIÓN BÁSICA DE LA PRENDA
            $table->string('nombre_prenda', 100)->nullable();
            $table->string('cantidad', 56)->nullable();
            $table->text('descripcion')->nullable();
            
            // VARIACIONES DE LA PRENDA (opcionales, según cotización)
            $table->foreignId('color_id')->nullable()->constrained('colores')->onDelete('set null');
            $table->foreignId('tela_id')->nullable()->constrained('telas')->onDelete('set null');
            $table->foreignId('tipo_manga_id')->nullable()->constrained('tipo_mangas')->onDelete('set null');
            $table->foreignId('tipo_broche_id')->nullable()->constrained('tipo_broches')->onDelete('set null');
            
            // DETALLES BOOLEANOS
            $table->boolean('tiene_bolsillos')->default(false);
            $table->boolean('tiene_reflectivo')->default(false);
            
            // DETALLES DE VARIACIONES
            $table->longText('descripcion_variaciones')->nullable();
            
            // TALLAS Y CANTIDADES (JSON)
            // Ejemplo: {"XS": 5, "S": 10, "M": 15, "L": 8, "XL": 2}
            $table->json('cantidad_talla')->nullable();
            
            // METADATA
            $table->timestamps();
            $table->softDeletes();
        });

        // TABLA: PROCESOS_PRENDA (cada proceso que pasa un pedido)
        Schema::create('procesos_prenda', function (Blueprint $table) {
            $table->id();
            
            // RELACIÓN CON PEDIDO VÍA numero_pedido
            $table->unsignedInteger('numero_pedido');
            $table->foreign('numero_pedido')->references('numero_pedido')->on('pedidos_produccion')->onDelete('cascade');
            
            // TIPO DE PROCESO
            $table->string('proceso', 255);  // Cambiar de ENUM a VARCHAR para más flexibilidad
            
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
            
            // ÍNDICES
            $table->index('numero_pedido');
            $table->index('proceso');
            $table->index('fecha_inicio');
            $table->index(['numero_pedido', 'proceso']);
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
