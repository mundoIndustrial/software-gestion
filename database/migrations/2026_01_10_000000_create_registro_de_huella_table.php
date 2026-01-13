<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_de_huella', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_persona');
            $table->time('hora');
            $table->timestamps();
            
            // Relación con tabla personal
            $table->foreign('id_persona')
                ->references('id')
                ->on('personal')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_de_huella');
    }
};
