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
        Schema::table('prenda_cot_reflectivo', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->comment('Descripción del reflectivo para esta prenda')->after('ubicaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_cot_reflectivo', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });
    }
};
