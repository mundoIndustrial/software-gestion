<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_global_novedades', function (Blueprint $table) {
            $table->id();
            $table->string('tab', 20);
            $table->unsignedBigInteger('recibo_id');
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->text('novedad');
            $table->timestamps();

            $table->index(['tab', 'recibo_id'], 'prestamos_global_novedades_tab_recibo_idx');
            $table->index(['usuario_id'], 'prestamos_global_novedades_usuario_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_global_novedades');
    }
};
