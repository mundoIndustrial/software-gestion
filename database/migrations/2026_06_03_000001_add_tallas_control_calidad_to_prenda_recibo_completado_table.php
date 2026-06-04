<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_recibo_completado', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_recibo_completado', 'tallas_control_calidad')) {
                $table->json('tallas_control_calidad')->nullable()->after('fecha_completado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_recibo_completado', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_recibo_completado', 'tallas_control_calidad')) {
                $table->dropColumn('tallas_control_calidad');
            }
        });
    }
};
