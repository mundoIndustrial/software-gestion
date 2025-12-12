<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Entities\Operario;
use App\Domain\Operario\Repositories\OperarioRepository;
use App\Domain\Operario\ValueObjects\TipoOperario;
use App\Domain\Operario\ValueObjects\AreaOperario;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Implementation: OperarioRepositoryImpl
 *
 * Implementación de OperarioRepository usando Eloquent
 */
class OperarioRepositoryImpl implements OperarioRepository
{
    /**
     * Obtener operario por ID
     */
    public function obtenerPorId(int $id): ?Operario
    {
        $usuario = User::find($id);

        if (!$usuario || !$usuario->hasAnyRole(['cortador', 'costurero'])) {
            return null;
        }

        return $this->mapearUserAOperario($usuario);
    }

    /**
     * Obtener operarios por tipo
     */
    public function obtenerPorTipo(TipoOperario $tipo): Collection
    {
        $rolName = $tipo->value;

        return User::whereJsonContains('roles_ids', function ($query) use ($rolName) {
            $role = \App\Models\Role::where('name', $rolName)->first();
            return $role ? $role->id : null;
        })->get()->map(fn($user) => $this->mapearUserAOperario($user));
    }

    /**
     * Obtener operarios por área
     */
    public function obtenerPorArea(AreaOperario $area): Collection
    {
        // Obtener todos los operarios y filtrar por área
        $operarios = User::whereJsonContains('roles_ids', function ($query) {
            $cortador = \App\Models\Role::where('name', 'cortador')->first();
            $costurero = \App\Models\Role::where('name', 'costurero')->first();
            return [$cortador?->id, $costurero?->id];
        })->get();

        return $operarios->map(fn($user) => $this->mapearUserAOperario($user))
            ->filter(fn($operario) => $operario->getArea() === $area);
    }

    /**
     * Obtener todos los operarios activos
     */
    public function obtenerActivos(): Collection
    {
        return User::whereJsonContains('roles_ids', function ($query) {
            $cortador = \App\Models\Role::where('name', 'cortador')->first();
            $costurero = \App\Models\Role::where('name', 'costurero')->first();
            return [$cortador?->id, $costurero?->id];
        })->get()->map(fn($user) => $this->mapearUserAOperario($user));
    }

    /**
     * Obtener operarios por tipo y área
     */
    public function obtenerPorTipoYArea(TipoOperario $tipo, AreaOperario $area): Collection
    {
        $rolName = $tipo->value;

        return User::whereJsonContains('roles_ids', function ($query) use ($rolName) {
            $role = \App\Models\Role::where('name', $rolName)->first();
            return $role ? $role->id : null;
        })->get()->map(fn($user) => $this->mapearUserAOperario($user))
            ->filter(fn($operario) => $operario->getArea() === $area);
    }

    /**
     * Guardar operario
     */
    public function guardar(Operario $operario): void
    {
        $usuario = User::find($operario->getId());

        if ($usuario) {
            $usuario->update([
                'name' => $operario->getNombre(),
                'email' => $operario->getEmail(),
            ]);
        }
    }

    /**
     * Obtener pedidos asignados a un operario
     */
    public function obtenerPedidosAsignados(int $operarioId): Collection
    {
        $operario = $this->obtenerPorId($operarioId);

        if (!$operario) {
            return collect();
        }

        return $operario->getPedidosAsignados();
    }

    /**
     * Mapear User a Operario Entity
     */
    private function mapearUserAOperario(User $usuario): Operario
    {
        $tipo = $usuario->hasRole('cortador') ? TipoOperario::CORTADOR : TipoOperario::COSTURERO;
        $area = $tipo === TipoOperario::CORTADOR ? AreaOperario::CORTE : AreaOperario::COSTURA;

        return Operario::reconstruir(
            id: $usuario->id,
            nombre: $usuario->name,
            email: $usuario->email,
            tipo: $tipo->value,
            area: $area->value,
            activo: true,
            fechaCreacion: $usuario->created_at
        );
    }
}
