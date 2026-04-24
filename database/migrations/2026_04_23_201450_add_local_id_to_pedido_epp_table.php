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
        Schema::table('pedido_epp', function (Blueprint $table) {
            $table->string('local_id', 100)->nullable()->after('id');
            $table->index(['pedido_produccion_id', 'local_id'], 'idx_pedido_epp_local_id');
        });
    }

    public function down(): void
    {
        Schema::table('pedido_epp', function (Blueprint $table) {
            $table->dropIndex('idx_pedido_epp_local_id');
            $table->dropColumn('local_id');
        });
    }
};
