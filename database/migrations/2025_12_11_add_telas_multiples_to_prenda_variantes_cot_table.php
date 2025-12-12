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
            $table->json('telas_multiples')->nullable()->after('descripcion_adicional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            $table->dropColumn('telas_multiples');
        });
    }
};
