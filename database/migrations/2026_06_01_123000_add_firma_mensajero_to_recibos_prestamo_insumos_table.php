<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos_prestamo_insumos', function (Blueprint $table) {
            $table->longText('firma_mensajero')->nullable()->after('firma_costurero_fecha');
            $table->timestamp('firma_mensajero_fecha')->nullable()->after('firma_mensajero');
        });
    }

    public function down(): void
    {
        Schema::table('recibos_prestamo_insumos', function (Blueprint $table) {
            $table->dropColumn(['firma_mensajero', 'firma_mensajero_fecha']);
        });
    }
};
