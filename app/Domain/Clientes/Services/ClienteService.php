<?php

namespace App\Domain\Clientes\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Cliente;

/**
 * Service: Gestión de Clientes
 * 
 * FASE 3 - Lógica de dominio para clientes
 * Responsabilidades:
 * - Buscar cliente por nombre
 * - Crear cliente nuevo si no existe
 * - Centralizar lógica compartida (usada por múltiples UseCases)
 * 
 * Patrón: Domain Service (lógica de negocio, sin HTTP)
 * 
 * @package App\Domain\Clientes\Services
 */
class ClienteService
{
    /**
     * Obtener cliente existente o crear uno nuevo
     * 
     * FLUJO:
     * 1. Buscar cliente por nombre (LIKE - case insensitive)
     * 2. Si existe, retornar
     * 3. Si no existe, crear nuevo con estado 'activo'
     * 4. Logging en ambos casos
     * 
     * @param string $nombre Nombre del cliente (requerido)
     * @return Cliente
     * @throws \Exception Si el nombre está vacío
     */
    public function obtenerOCrearCliente(string $nombre): Cliente
    {
        $nombre = trim($nombre);

        if (empty($nombre)) {
            throw new \Exception('Nombre de cliente no puede estar vacío');
        }

        // Búsqueda por nombre LIKE (case insensitive)
        $cliente = Cliente::where('nombre', 'LIKE', $nombre)->first();

        if ($cliente) {
            Log::debug('[ClienteService] Cliente existente encontrado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'busqueda' => $nombre,
            ]);

            return $cliente;
        }

        // Crear cliente nuevo si no existe
        $cliente = Cliente::create([
            'nombre' => $nombre,
            'email' => null,
            'telefono' => null,
            'direccion' => null,
            'ciudad' => null,
            'estado' => 'activo',
        ]);

        Log::info('[ClienteService] Cliente nuevo creado', [
            'cliente_id' => $cliente->id,
            'nombre' => $cliente->nombre,
        ]);

        return $cliente;
    }
}
