<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos_prestamo_insumos', function (Blueprint $table) {
            $table->boolean('confirmado_entrada')->default(false)->after('anulado_en');
            $table->timestamp('confirmado_entrada_en')->nullable()->after('confirmado_entrada');
            $table->text('novedades')->nullable()->after('confirmado_entrada_en');
        });

        Schema::table('recibos_prestamo_contramuestra', function (Blueprint $table) {
            $table->boolean('confirmado_entrada')->default(false)->after('anulado_en');
            $table->timestamp('confirmado_entrada_en')->nullable()->after('confirmado_entrada');
            $table->text('novedades')->nullable()->after('confirmado_entrada_en');
        });
    }

    public function down(): void
    {
        Schema::table('recibos_prestamo_insumos', function (Blueprint $table) {
            $table->dropColumn(['confirmado_entrada', 'confirmado_entrada_en', 'novedades']);
        });

        Schema::table('recibos_prestamo_contramuestra', function (Blueprint $table) {
            $table->dropColumn(['confirmado_entrada', 'confirmado_entrada_en', 'novedades']);
        });
    }
};
