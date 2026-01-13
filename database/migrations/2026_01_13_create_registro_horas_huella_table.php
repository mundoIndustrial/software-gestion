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
        Schema::create('registro_horas_huella', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_reporte');
            $table->unsignedBigInteger('id_persona');
            $table->date('dia');
            $table->json('horas')->nullable(); // Almacena las horas en formato JSON
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_reporte')
                ->references('id')
                ->on('reportes_personal')
                ->onDelete('cascade');

            $table->foreign('id_persona')
                ->references('id')
                ->on('personal')
                ->onDelete('cascade');

            // Indices
            $table->index(['id_reporte', 'id_persona', 'dia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_horas_huella');
    }
};
