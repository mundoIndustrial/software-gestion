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
        Schema::table('logo_cotizaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('logo_cotizaciones', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('cotizacion_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('logo_cotizaciones', 'descripcion')) {
                $table->dropColumn('descripcion');
            }
        });
    }
};
