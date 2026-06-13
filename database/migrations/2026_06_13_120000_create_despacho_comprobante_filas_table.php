<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despacho_comprobante_filas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('despacho_comprobante_id')
                ->constrained('despacho_comprobantes')
                ->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedInteger('cantidad')->default(0);
            $table->text('articulo')->nullable();
            $table->timestamps();

            $table->index(['despacho_comprobante_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despacho_comprobante_filas');
    }
};
