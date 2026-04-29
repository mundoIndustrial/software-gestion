<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_tallas_bodega', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_tallas_bodega', 'genero')) {
                $table->string('genero', 20)->nullable()->after('talla');
                $table->index('genero', 'idx_prenda_tallas_bodega_genero');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_tallas_bodega', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_tallas_bodega', 'genero')) {
                $table->dropIndex('idx_prenda_tallas_bodega_genero');
                $table->dropColumn('genero');
            }
        });
    }
};

