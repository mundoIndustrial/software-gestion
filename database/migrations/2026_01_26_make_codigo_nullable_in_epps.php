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
        Schema::table('epps', function (Blueprint $table) {
            // Hacer nullable la columna codigo
            $table->string('codigo', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            // Revertir a NOT NULL
            $table->string('codigo', 50)->nullable(false)->change();
        });
    }
};
