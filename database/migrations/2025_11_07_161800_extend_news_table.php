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
        Schema::table('news', function (Blueprint $table) {
            // Agregar columnas para identificar la tabla y registro afectado
            $table->string('table_name')->nullable()->after('event_type');
            $table->string('record_id')->nullable()->after('table_name');
            
            // Agregar índices para mejorar búsquedas
            $table->index('event_type');
            $table->index('table_name');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['event_type']);
            $table->dropIndex(['table_name']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['table_name', 'record_id']);
        });
    }
};
