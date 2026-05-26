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
        Schema::create('recibos_prestamo_insumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero_orden')->unique();
            $table->date('fecha');
            $table->string('nombre_costurero', 150);
            $table->boolean('anulado')->default(false);
            $table->timestamp('anulado_en')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();

            $table->index('fecha');
            $table->index('anulado');
        });

        Schema::create('recibos_prestamo_insumos_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recibo_prestamo_insumo_id')
                ->constrained('recibos_prestamo_insumos')
                ->cascadeOnDelete();
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->text('descripcion');
            $table->unsignedInteger('orden_fila')->default(1);
            $table->timestamps();

            $table->index('orden_fila');
        });

        Schema::create('recibos_prestamo_contramuestra', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero_orden')->unique();
            $table->date('fecha');
            $table->string('nombre_costurero', 150);
            $table->text('descripcion');
            $table->boolean('anulado')->default(false);
            $table->timestamp('anulado_en')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();

            $table->index('fecha');
            $table->index('anulado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recibos_prestamo_contramuestra');
        Schema::dropIfExists('recibos_prestamo_insumos_items');
        Schema::dropIfExists('recibos_prestamo_insumos');
    }
};

