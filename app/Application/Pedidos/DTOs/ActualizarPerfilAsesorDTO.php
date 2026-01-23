<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ActualizarPerfilAsesorDTO
 * 
 * DTO para actualizar el perfil del asesor
 */
class ActualizarPerfilAsesorDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $email,
        public readonly ?string $telefono = null,
        public readonly ?string $ciudad = null,
        public readonly ?string $departamento = null,
        public readonly ?string $bio = null,
        public readonly ?string $password = null,
        public readonly mixed $avatar = null,
        public readonly ?int $asesorId = null
    ) {}

    /**
     * Crear desde una solicitud HTTP
     */
    public static function fromRequest(array $validated, mixed $archivoAvatar = null): self
    {
        return new self(
            nombre: $validated['name'] ?? '',
            email: $validated['email'] ?? '',
            telefono: $validated['telefono'] ?? null,
            ciudad: $validated['ciudad'] ?? null,
            departamento: $validated['departamento'] ?? null,
            bio: $validated['bio'] ?? null,
            password: $validated['password'] ?? null,
            avatar: $archivoAvatar,
            asesorId: \Illuminate\Support\Facades\Auth::id()
        );
    }
}
