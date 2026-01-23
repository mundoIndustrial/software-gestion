<?php

namespace App\Models;

/**
 * Alias para mantener compatibilidad con código existente
 * El modelo real es Pedido, pero se referencia como Pedidos en muchos lugares
 */
class Pedidos extends Pedido
{
    // Alias simple - hereda todo de Pedido
}
