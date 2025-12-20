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
        Schema::table('prenda_tela_fotos_cot', function (Blueprint $table) {
            $table->unsignedInteger('tela_index')->nullable()->after('prenda_tela_cot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_tela_fotos_cot', function (Blueprint $table) {
            $table->dropColumn('tela_index');
        });
    }
};
