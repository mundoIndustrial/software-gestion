<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE procesos_prenda DROP FOREIGN KEY fk_procesos_numero_pedido');
        DB::statement('ALTER TABLE procesos_prenda MODIFY numero_pedido INT UNSIGNED NULL');
        DB::statement('ALTER TABLE procesos_prenda ADD CONSTRAINT fk_procesos_numero_pedido FOREIGN KEY (numero_pedido) REFERENCES pedidos_produccion(numero_pedido) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE procesos_prenda DROP FOREIGN KEY fk_procesos_numero_pedido');
        DB::statement('DELETE FROM procesos_prenda WHERE numero_pedido IS NULL');
        DB::statement('ALTER TABLE procesos_prenda MODIFY numero_pedido INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE procesos_prenda ADD CONSTRAINT fk_procesos_numero_pedido FOREIGN KEY (numero_pedido) REFERENCES pedidos_produccion(numero_pedido) ON DELETE CASCADE ON UPDATE CASCADE');
    }
};
