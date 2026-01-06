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
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            // Cambiar prenda_bodega de VARCHAR a BOOLEAN
            $table->boolean('prenda_bodega')->nullable(true)->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            // Revertir a VARCHAR
            $table->string('prenda_bodega')->nullable()->default(null)->change();
        });
    }
};
