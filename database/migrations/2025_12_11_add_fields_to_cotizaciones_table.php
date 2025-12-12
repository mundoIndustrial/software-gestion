<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->json('imagenes')->nullable()->after('especificaciones');
            $table->json('tecnicas')->nullable()->after('imagenes');
            $table->longText('observaciones_tecnicas')->nullable()->after('tecnicas');
            $table->json('ubicaciones')->nullable()->after('observaciones_tecnicas');
            $table->json('observaciones_generales')->nullable()->after('ubicaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn([
                'imagenes',
                'tecnicas',
                'observaciones_tecnicas',
                'ubicaciones',
                'observaciones_generales',
            ]);
        });
    }
};
