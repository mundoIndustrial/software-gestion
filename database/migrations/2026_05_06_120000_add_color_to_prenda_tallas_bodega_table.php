<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_tallas_bodega', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_tallas_bodega', 'color')) {
                $table->string('color', 100)->nullable()->after('genero');
                $table->index('color', 'idx_prenda_tallas_bodega_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_tallas_bodega', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_tallas_bodega', 'color')) {
                $table->dropIndex('idx_prenda_tallas_bodega_color');
                $table->dropColumn('color');
            }
        });
    }
};

