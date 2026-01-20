<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar datos existentes de prenda_pedido_variantes a prenda_pedido_colores_telas
        $variantes = DB::table('prenda_pedido_variantes')
            ->whereNotNull('color_id')
            ->orWhereNotNull('tela_id')
            ->get();

        foreach ($variantes as $variante) {
            // Verificar si ya existe un registro para esta prenda
      