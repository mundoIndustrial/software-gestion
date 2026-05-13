<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_areas_logo_pedido', function (Blueprint $table): void {
            if (!Schema::hasColumn('prenda_areas_logo_pedido', 'consecutivo_recibo_id')) {
                $table->unsignedBigInteger('consecutivo_recibo_id')->nullable()->after('pedido_parcial_id');
                $table->index('consecutivo_recibo_id', 'idx_palp_consecutivo_recibo_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_areas_logo_pedido', function (Blueprint $table): void {
            if (Schema::hasColumn('prenda_areas_logo_pedido', 'consecutivo_recibo_id')) {
                $table->dropIndex('idx_palp_consecutivo_recibo_id');
                $table->dropColumn('consecutivo_recibo_id');
            }
        });
    }
};
