<?php

namespace App\Domain\Pedidos\Services;

use App\Models\Cliente;

/**
 * Servicio de Dominio para gestionar clientes
 */
class ClienteService
{
    /**
     * Obtener o crear cliente por nombre
     * @param string $nombreCliente
     * @return Cliente
     */
    public function obtenerOCrearCliente(string $nombreCliente): Cliente
    {
        $cliente = Cliente::where('nombre', $nombreCliente)->first();

        if (!$cliente) {
            $cliente = Cliente::create([
                'nombre' => $nombreCliente,
                'estado' => 'activo',
            ]);
        }

        return $cliente;
    }
}

