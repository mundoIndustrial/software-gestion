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
        Schema::table('prendas_cot', function (Blueprint $table) {
            $table->text('texto_personalizado_tallas')->nullable()->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_cot', function (Blueprint $table) {
            $table->dropColumn('texto_personalizado_tallas');
        });
    }
};
