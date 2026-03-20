<?php

namespace App\Application\SupervisorPedidos\DTOs;

class UpdateProfileRequest
{
    private string $userId;
    private string $name;
    private string $email;
    private ?string $telefono;
    private ?string $ciudad;
    private ?string $departamento;
    private ?string $bio;
    private ?string $password;
    private $avatarFile;

    public function __construct(
        string $userId,
        string $name,
        string $email,
        ?string $telefono = null,
        ?string $ciudad = null,
        ?string $departamento = null,
        ?string $bio = null,
        ?string $password = null,
        $avatarFile = null
    ) {
        $this->userId = $userId;
        $this->name = $name;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->ciudad = $ciudad;
        $this->departamento = $departamento;
        $this->bio = $bio;
        $this->password = $password;
        $this->avatarFile = $avatarFile;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function getCiudad(): ?string
    {
        return $this->ciudad;
    }

    public function getDepartamento(): ?string
    {
        return $this->departamento;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getAvatarFile()
    {
        return $this->avatarFile;
    }

    public function hasAvatarFile(): bool
    {
        return $this->avatarFile !== null;
    }
}
