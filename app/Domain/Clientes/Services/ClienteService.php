<?php

namespace App\Domain\Clientes\Services;

use App\Models\Cliente;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Service: Gestion de Clientes
 *
 * FASE 3 - Logica de dominio para clientes
 * Responsabilidades:
 * - Buscar cliente por nombre
 * - Crear cliente nuevo si no existe
 * - Centralizar logica compartida (usada por multiples UseCases)
 *
 * Patron: Domain Service (logica de negocio, sin HTTP)
 *
 * @package App\Domain\Clientes\Services
 */
class ClienteService
{
    /**
     * Obtener cliente existente o crear uno nuevo.
     *
     * Normaliza nombre para evitar duplicados por diferencias de formato
     * y tolera colisiones concurrentes en el indice unique.
     *
     * @param string $nombre Nombre del cliente (requerido)
     * @return Cliente
     * @throws \Exception Si el nombre esta vacio
     */
    public function obtenerOCrearCliente(string $nombre): Cliente
    {
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
        $nombreNormalizado = mb_strtoupper($nombre, 'UTF-8');

        if ($nombreNormalizado === '') {
            throw new \Exception('Nombre de cliente no puede estar vacio');
        }

        // Reusar cliente existente de manera case-insensitive.
        $cliente = Cliente::query()
            ->whereRaw('UPPER(TRIM(nombre)) = ?', [$nombreNormalizado])
            ->first();

        if ($cliente) {
            Log::debug('[ClienteService] Cliente existente encontrado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'busqueda' => $nombreNormalizado,
            ]);

            return $cliente;
        }

        try {
            $cliente = Cliente::create([
                'nombre' => $nombreNormalizado,
                'email' => null,
                'telefono' => null,
                'ciudad' => null,
            ]);
        } catch (QueryException $e) {
            $isDuplicate = ((string) $e->getCode() === '23000')
                || str_contains(mb_strtolower($e->getMessage(), 'UTF-8'), 'duplicate entry');

            if (!$isDuplicate) {
                throw $e;
            }

            $cliente = Cliente::query()
                ->whereRaw('UPPER(TRIM(nombre)) = ?', [$nombreNormalizado])
                ->first();

            if (!$cliente) {
                throw $e;
            }

            Log::warning('[ClienteService] Cliente recuperado tras colision de unique', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'busqueda' => $nombreNormalizado,
            ]);

            return $cliente;
        }

        Log::info('[ClienteService] Cliente nuevo creado', [
            'cliente_id' => $cliente->id,
            'nombre' => $cliente->nombre,
        ]);

        return $cliente;
    }
}
