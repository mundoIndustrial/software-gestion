<?php

namespace App\Application\Cotizacion\Services;

use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerOCrearClienteService
 * 
 * Servicio que obtiene un cliente existente o crea uno nuevo
 * basado en el nombre del cliente
 */
final class ObtenerOCrearClienteService
{
    /**
     * Obtener o crear cliente por nombre
     * 
     * @param string $nombreCliente Nombre del cliente
     * @return Cliente El cliente existente o recién creado
     */
    public function ejecutar(string $nombreCliente): Cliente
    {
        try {
            // Validar que el nombre no esté vacío
            if (empty(trim($nombreCliente))) {
                throw new \InvalidArgumentException('El nombre del cliente no puede estar vacío');
            }

            $nombreCliente = trim($nombreCliente);

            // Buscar cliente existente por nombre (case-insensitive)
            $cliente = Cliente::whereRaw('LOWER(nombre) = ?', [strtolower($nombreCliente)])
                ->first();

            // Si existe, devolverlo
            if ($cliente) {
                Log::info('ObtenerOCrearClienteService: Cliente encontrado', [
                    'cliente_id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                ]);
                return $cliente;
            }

            // Si no existe, crear uno nuevo
            $cliente = Cliente::create([
                'nombre' => $nombreCliente,
            ]);

            Log::info('ObtenerOCrearClienteService: Cliente creado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);

            return $cliente;
        } catch (\Exception $e) {
            Log::error('ObtenerOCrearClienteService: Error', [
                'nombre_cliente' => $nombreCliente,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
