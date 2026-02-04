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
        Schema::table('prenda_tallas_cot', function (Blueprint $table) {
            $table->unsignedBigInteger('genero_id')->nullable()->after('talla');
            
            $table->foreign('genero_id')
                  ->references('id')
                  ->on('generos_prenda')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_tallas_cot', function (Blueprint $table) {
            $table->dropForeign(['genero_id']);
            $table->dropColumn('genero_id');
        });
    }
};
