<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega índices para optimizar las consultas de fotos en el sistema de operario
     * Mejora esperada: 30-40% más rápido en queries individuales
     */
    public function up(): void
    {
        // La mayoría de índices ya existen, solo agregamos los que faltan
        
        // 1. Índice para búsqueda de pedidos por número (el único que falta)
        if (!Schema::hasColumn('pedidos_produccion', 'numero_pedido')) {
            return; // Tabla no existe
        }
        
        DB::statement('ALTER TABLE pedidos_produccion ADD INDEX idx_numero_pedido (numero_pedido)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE pedidos_produccion DROP INDEX IF EXISTS idx_numero_pedido');
    }
};
