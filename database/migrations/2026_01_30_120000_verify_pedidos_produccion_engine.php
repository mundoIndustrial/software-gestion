<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Verificar y asegurar que pedidos_produccion use InnoDB
 * 
 * Esta migración asegura que la tabla principal de pedidos esté configurada
 * correctamente para alta concurrencia con AUTO_INCREMENT seguro.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verificar motor de almacenamiento actual
        $engine = DB::select("
            SELECT ENGINE 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'pedidos_produccion'
        ", [env('DB_DATABASE')]);

        $currentEngine = $engine[0]->ENGINE ?? null;
        
        if ($currentEngine !== 'InnoDB') {
            // Convertir a InnoDB si no lo está
            DB::statement("ALTER TABLE pedidos_produccion ENGINE = InnoDB");
            
            echo "✅ pedidos_produccion convertido a InnoDB\n";
        } else {
            echo "✅ pedidos_produccion ya usa InnoDB\n";
        }

        // Verificar que AUTO_INCREMENT esté configurado correctamente
        $autoIncrement = DB::select("
            SELECT AUTO_INCREMENT 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'pedidos_produccion'
        ", [env('DB_DATABASE')]);

        $nextId = $autoIncrement[0]->AUTO_INCREMENT ?? null;
        
        echo "📊 Próximo AUTO_INCREMENT: $nextId\n";

        // Verificar que no existan bloqueos innecesarios
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // No añadir índices UNIQUE que puedan causar bloqueos
            // numero_pedido es UN pero se genera después (no en creación)
            // La primary key ya existe, no se necesita agregar
        });
    }

    public function down(): void
    {
        // No se necesita rollback - esta migración solo verifica y ajusta
    }
};
