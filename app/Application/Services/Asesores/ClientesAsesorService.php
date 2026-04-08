<?php

namespace App\Application\Services\Asesores;

use App\Models\Cliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ClientesAsesorService
{
    public function listarPorUsuario(int $usuarioId, int $porPagina = 15): LengthAwarePaginator
    {
        return Cliente::query()
            ->where('user_id', $usuarioId)
            ->orderBy('created_at', 'desc')
            ->paginate($porPagina);
    }

    public function crear(int $usuarioId, array $data): Cliente
    {
        return Cliente::create([
            'user_id' => $usuarioId,
            'nombre' => $data['nombre'],
            'email' => $data['email'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'notas' => $data['notas'] ?? null,
        ]);
    }

    public function actualizar(int $usuarioId, int $clienteId, array $data): void
    {
        $cliente = Cliente::findOrFail($clienteId);
        $this->assertPropietario($cliente, $usuarioId);
        $cliente->update($data);
    }

    public function eliminar(int $usuarioId, int $clienteId): void
    {
        $cliente = Cliente::findOrFail($clienteId);
        $this->assertPropietario($cliente, $usuarioId);
        $cliente->delete();
    }

    private function assertPropietario(Cliente $cliente, int $usuarioId): void
    {
        if ((int) $cliente->user_id !== $usuarioId) {
            throw new AccessDeniedHttpException('No autorizado para este cliente');
        }
    }
}
