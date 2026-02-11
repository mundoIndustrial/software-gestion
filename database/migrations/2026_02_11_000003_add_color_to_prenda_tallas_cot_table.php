<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_tallas_cot', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_tallas_cot', 'color')) {
                $table->string('color', 50)->nullable()->after('talla');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_tallas_cot', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_tallas_cot', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
