<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\TipoPrenda;
use Illuminate\Support\Facades\Log;

class PedidoTipoPrendaService
{
    public function asegurarTipo(string $nombrePrenda): ?TipoPrenda
    {
        try {
            $nombreUpper = strtoupper(trim($nombrePrenda));

            $tipoPrenda = TipoPrenda::whereRaw('UPPER(nombre) = ?', [$nombreUpper])->first();

            if ($tipoPrenda) {
                Log::info('[PedidoTipoPrendaService] Tipo de prenda encontrado', [
                    'tipo_prenda_id' => $tipoPrenda->id,
                    'nombre' => $tipoPrenda->nombre,
                ]);

                return $tipoPrenda;
            }

            $tipoPrenda = TipoPrenda::create([
                'nombre' => $nombreUpper,
                'codigo' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nombrePrenda), 0, 10)),
                'descripcion' => 'Prenda creada automaticamente desde pedido',
                'activo' => true,
                'palabras_clave' => [],
            ]);

            Log::info('[PedidoTipoPrendaService] Tipo de prenda creado', [
                'tipo_prenda_id' => $tipoPrenda->id,
                'nombre' => $tipoPrenda->nombre,
            ]);

            return $tipoPrenda;
        } catch (\Exception $e) {
            Log::warning('[PedidoTipoPrendaService] Error creando tipo de prenda', [
                'error' => $e->getMessage(),
                'nombre_prenda' => $nombrePrenda,
            ]);

            return null;
        }
    }
}
