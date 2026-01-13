<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_personal', function (Blueprint $table) {
            $table->id();
            $table->string('numero_reporte')->unique();
            $table->string('nombre_reporte');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_personal');
    }
};
