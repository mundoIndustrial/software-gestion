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
        Schema::table('registro_piso_produccion', function (Blueprint $table) {
            $table->string('hora', 50)->change();
        });
        
        Schema::table('registro_piso_polo', function (Blueprint $table) {
            $table->string('hora', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_piso_produccion', function (Blueprint $table) {
            $table->time('hora')->change();
        });
        
        Schema::table('registro_piso_polo', function (Blueprint $table) {
            $table->time('hora')->change();
        });
    }
};
