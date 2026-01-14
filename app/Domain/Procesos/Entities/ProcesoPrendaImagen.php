<?php

namespace App\Domain\Procesos\Entities;

use App\Domain\Shared\Entity;

/**
 * Entity: ProcesoPrendaImagen
 * 
 * Representa una imagen asociada a un proceso de prenda
 * Un proceso puede tener múltiples imágenes
 */
class ProcesoPrendaImagen extends Entity
{
    protected $procesoPrendaDetalleId;
    protected $ruta;
    protected $nombreOriginal;
    protected $tipoMime;
    protected $tamaño;
    protected $ancho;
    protected $alto;
    protected $hashMd5;
    protected $orden;
    protected $esPrincipal;
    protected $descripcion;

    public function __construct(
        ?int $id,
        int $procesoPrendaDetalleId,
        string $ruta,
        string $nombreOriginal,
        string $tipoMime,
        int $tamaño,
        ?int $ancho = null,
        ?int $alto = null,
        ?string $hashMd5 = null,
        int $orden = 0,
        bool $esPrincipal = false,
        ?string $descripcion = null
    ) {
        parent::__construct($id);
        $this->procesoPrendaDetalleId = $procesoPrendaDetalleId;
        $this->ruta = $ruta;
        $this->nombreOriginal = $nombreOriginal;
        $this->tipoMime = $tipoMime;
        $this->tamaño = $tamaño;
        $this->ancho = $ancho;
        $this->alto = $alto;
        $this->hashMd5 = $hashMd5;
        $this->orden = $orden;
        $this->esPrincipal = $esPrincipal;
        $this->descripcion = $descripcion;
    }

    public function getProcesoPrendaDetalleId(): int
    {
        return $this->procesoPrendaDetalleId;
    }

    public function getRuta(): string
    {
        return $this->ruta;
    }

    public function getNombreOriginal(): string
    {
        return $this->nombreOriginal;
    }

    public function getTipoMime(): string
    {
        return $this->tipoMime;
    }

    public function getTamaño(): int
    {
        return $this->tamaño;
    }

    public function getAncho(): ?int
    {
        return $this->ancho;
    }

    public function setAncho(?int $ancho): void
    {
        $this->ancho = $ancho;
    }

    public function getAlto(): ?int
    {
        return $this->alto;
    }

    public function setAlto(?int $alto): void
    {
        $this->alto = $alto;
    }

    public function getHashMd5(): ?string
    {
        return $this->hashMd5;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): void
    {
        $this->orden = $orden;
    }

    public function isEsPrincipal(): bool
    {
        return $this->esPrincipal;
    }

    public function getEsPrincipal(): bool
    {
        return $this->esPrincipal;
    }

    public function marcarComoPrincipal(): void
    {
        $this->esPrincipal = true;
    }

    public function desmarcarComoPrincipal(): void
    {
        $this->esPrincipal = false;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function esImagenValida(): bool
    {
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($this->tipoMime, $tiposPermitidos);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'proceso_prenda_detalle_id' => $this->procesoPrendaDetalleId,
            'ruta' => $this->ruta,
            'nombre_original' => $this->nombreOriginal,
            'tipo_mime' => $this->tipoMime,
            'tamaño' => $this->tamaño,
            'ancho' => $this->ancho,
            'alto' => $this->alto,
            'hash_md5' => $this->hashMd5,
            'orden' => $this->orden,
            'es_principal' => $this->esPrincipal,
            'descripcion' => $this->descripcion,
        ];
    }
}
