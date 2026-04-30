<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_parciales', function (Blueprint $table) {
            $table->json('ubicaciones')->nullable()->after('notas');
            $table->text('observaciones')->nullable()->after('ubicaciones');
            $table->json('datos_adicionales')->nullable()->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_parciales', function (Blueprint $table) {
            $table->dropColumn(['ubicaciones', 'observaciones', 'datos_adicionales']);
        });
    }
};

