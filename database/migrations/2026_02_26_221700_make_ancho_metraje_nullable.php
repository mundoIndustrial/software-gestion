<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            $table->decimal('ancho', 10, 2)->nullable()->default(null)->change();
            $table->decimal('metraje', 10, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            $table->decimal('ancho', 10, 2)->nullable(false)->default(0)->change();
            $table->decimal('metraje', 10, 2)->nullable(false)->default(0)->change();
        });
    }
};
