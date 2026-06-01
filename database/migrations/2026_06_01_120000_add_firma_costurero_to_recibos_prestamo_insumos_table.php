<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos_prestamo_insumos', function (Blueprint $table) {
            $table->longText('firma_costurero')->nullable()->after('nombre_costurero');
            $table->timestamp('firma_costurero_fecha')->nullable()->after('firma_costurero');
        });
    }

    public function down(): void
    {
        Schema::table('recibos_prestamo_insumos', function (Blueprint $table) {
            $table->dropColumn(['firma_costurero', 'firma_costurero_fecha']);
        });
    }
};
