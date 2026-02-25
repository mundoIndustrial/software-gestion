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
        Schema::table('bodega_notas', function (Blueprint $table) {
            $table->timestamp('visto_at')->nullable()->after('ip_address');
            $table->index('visto_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bodega_notas', function (Blueprint $table) {
            $table->dropIndex(['visto_at']);
            $table->dropColumn('visto_at');
        });
    }
};
