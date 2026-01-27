<?php

namespace App\DTOs\Edit;

/**
 * EditPrendaVariantePedidoDTO - DTO para edición segura de variante de prenda
 * 
 * ARQUITECTURA DE SEPARACIÓN:
 * - Creación: lógica DOM en Javascript construye toda la estructura
 * - Edición: DTO parcial que solo actualiza campos explícitos
 * 
 * Características:
 * ✓ Solo acepta campos explícitamente enviados
 * ✓ Las relaciones (colores, telas) se actualizan mediante MERGE
 * ✓ NO elimina relaciones automáticamente
 * ✓ Si no viene en payload → se conserva intacta
 */
class EditPrendaVariantePedidoDTO
{
    /**
     * @var int|null - ID de la variante a editar
     */
    public ?int $id;

    /**
     * @var int|null - Tipo de manga (opcional)
     */
    public ?int $tipo_manga_id;

    /**
     * @var int|null - Tipo de broche/botón (opcional)
     */
    public ?int $tipo_broche_boton_id;

    /**
     * @var bool|null - Tiene bolsillos (opcional)
     */
    public ?bool $tiene_bolsillos;

    /**
     * @var string|null - Observaciones de bolsillos (opcional)
     */
    public ?string $obs_bolsillos;

    /**
     * @var bool|null - Tiene reflectivo (opcional)
     */
    public ?bool $tiene_reflectivo;

    /**
     * @var string|null - Observaciones de reflectivo (opcional)
     */
    public ?string $obs_reflectivo;

    /**
     * @var array|null - Colores a actualizar mediante MERGE
     * Estructura:
     * [
     *   ['id' => 1, 'color_id' => 3], // UPDATE
     *   ['color_id' => 5], // CREATE
     * ]
     */
    public ?array $colores;

    /**
     * @var array|null - Telas a actualizar mediante MERGE
     * Estructura:
     * [
     *   ['id' => 2, 'tela_id' => 4], // UPDATE
     *   ['tela_id' => 6], // CREATE
     * ]
     */
    public ?array $telas;

    /**
     * @var array - Campos permitidos para edición
     */
    public array $allowed_fields = [
        'tipo_manga_id',
        'tipo_broche_boton_id',
        'tiene_bolsillos',
        'obs_bolsillos',
        'tiene_reflectivo',
        'obs_reflectivo',
        'colores',
        'telas',
    ];

    /**
     * @var array - Campos prohibidos (no se pueden editar)
     */
    public array $prohibited_fields = [
        'id',
        'prenda_pedido_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'telas_multiples',
    ];

    public function __construct(
        ?int $id = null,
        ?int $tipo_manga_id = null,
        ?int $tipo_broche_boton_id = null,
        ?bool $tiene_bolsillos = null,
        ?string $obs_bolsillos = null,
        ?bool $tiene_reflectivo = null,
        ?string $obs_reflectivo = null,
        ?array $colores = null,
        ?array $telas = null
    ) {
        $this->id = $id;
        $this->tipo_manga_id = $tipo_manga_id;
        $this->tipo_broche_boton_id = $tipo_broche_boton_id;
        $this->tiene_bolsillos = $tiene_bolsillos;
        $this->obs_bolsillos = $obs_bolsillos;
        $this->tiene_reflectivo = $tiene_reflectivo;
        $this->obs_reflectivo = $obs_reflectivo;
        $this->colores = $colores;
        $this->telas = $telas;
    }

    /**
     * Obtener solo los campos que fueron explícitamente enviados
     * 
     * @return array
     */
    public function getExplicitFields(): array
    {
        return array_filter([
            'tipo_manga_id' => $this->tipo_manga_id,
            'tipo_broche_boton_id' => $this->tipo_broche_boton_id,
            'tiene_bolsillos' => $this->tiene_bolsillos,
            'obs_bolsillos' => $this->obs_bolsillos,
            'tiene_reflectivo' => $this->tiene_reflectivo,
            'obs_reflectivo' => $this->obs_reflectivo,
            'colores' => $this->colores,
            'telas' => $this->telas,
        ], fn($value) => $value !== null);
    }

    /**
     * Obtener campos simples (no relaciones)
     * 
     * @return array
     */
    public function getSimpleFields(): array
    {
        return array_filter([
            'tipo_manga_id' => $this->tipo_manga_id,
            'tipo_broche_boton_id' => $this->tipo_broche_boton_id,
            'tiene_bolsillos' => $this->tiene_bolsillos,
            'obs_bolsillos' => $this->obs_bolsillos,
            'tiene_reflectivo' => $this->tiene_reflectivo,
            'obs_reflectivo' => $this->obs_reflectivo,
        ], fn($value) => $value !== null);
    }

    /**
     * Obtener campos de relaciones
     * 
     * @return array
     */
    public function getRelationshipFields(): array
    {
        return array_filter([
            'colores' => $this->colores,
            'telas' => $this->telas,
        ], fn($value) => $value !== null);
    }

    /**
     * Verificar si fue enviado un campo específico
     * 
     * @param string $field
     * @return bool
     */
    public function hasField(string $field): bool
    {
        return isset($this->$field) && $this->$field !== null;
    }

    /**
     * Convertir desde array de payload JSON
     * 
     * @param array $payload
     * @return self
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            id: $payload['id'] ?? null,
            tipo_manga_id: $payload['tipo_manga_id'] ?? null,
            tipo_broche_boton_id: $payload['tipo_broche_boton_id'] ?? null,
            tiene_bolsillos: $payload['tiene_bolsillos'] ?? null,
            obs_bolsillos: $payload['obs_bolsillos'] ?? null,
            tiene_reflectivo: $payload['tiene_reflectivo'] ?? null,
            obs_reflectivo: $payload['obs_reflectivo'] ?? null,
            colores: $payload['colores'] ?? null,
            telas: $payload['telas'] ?? null,
        );
    }
}
