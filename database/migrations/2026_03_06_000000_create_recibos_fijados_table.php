<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos_fijados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_recibo');
            $table->string('encargado_actual');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['encargado_actual'], 'recibos_fijados_encargado_unique');
            $table->index('id_recibo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos_fijados');
    }
};
