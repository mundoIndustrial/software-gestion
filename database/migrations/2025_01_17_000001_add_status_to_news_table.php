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
            // Agregar columna de estado con enum
            $table->enum('status', ['unread', 'read'])->default('unread')->after('read_at');
            
            // Agregar índice para mejorar rendimiento de consultas
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
        
        // Actualizar registros existentes basados en read_at
        DB::statement("UPDATE news SET status = 'read' WHERE read_at IS NOT NULL");
        DB::statement("UPDATE news SET status = 'unread' WHERE read_at IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
