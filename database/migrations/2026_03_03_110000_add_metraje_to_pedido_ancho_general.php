<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_ancho_general', 'metraje')) {
                $table->decimal('metraje', 10, 2)->nullable()->after('ancho');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_ancho_general', 'metraje')) {
                $table->dropColumn('metraje');
            }
        });
    }
};
