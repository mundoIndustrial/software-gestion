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
            $table->unsignedBigInteger('homologado_de')->nullable()->after('deleted_at')->comment('ID del EPP anterior en caso de homologación');
            $table->foreign('homologado_de')->references('id')->on('pedido_epp')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_epp', function (Blueprint $table) {
            $table->dropForeign(['homologado_de']);
            $table->dropColumn('homologado_de');
        });
    }
};
