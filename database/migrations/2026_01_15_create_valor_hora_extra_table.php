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
        Schema::create('valor_hora_extra', function (Blueprint $table) {
            $table->id();
            
            // Relación con la tabla personal
            $table->integer('codigo_persona')->unique();
            $table->foreign('codigo_persona')
                ->references('codigo_persona')
                ->on('personal')
                ->onDelete('cascade');
            
            // Valor de la hora extra
            $table->decimal('valor', 10, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valor_hora_extra');
    }
};
