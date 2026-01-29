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
        Schema::table('despacho_parciales', function (Blueprint $table) {
            $table->enum('genero', ['DAMA', 'CABALLERO', 'UNISEX'])->nullable()->after('talla_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('despacho_parciales', function (Blueprint $table) {
            $table->dropColumn('genero');
        });
    }
};
