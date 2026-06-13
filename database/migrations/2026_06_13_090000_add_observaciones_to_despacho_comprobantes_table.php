<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('despacho_comprobantes', function (Blueprint $table): void {
            if (!Schema::hasColumn('despacho_comprobantes', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('fecha_entrega');
            }
        });
    }

    public function down(): void
    {
        Schema::table('despacho_comprobantes', function (Blueprint $table): void {
            if (Schema::hasColumn('despacho_comprobantes', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
        });
    }
};
