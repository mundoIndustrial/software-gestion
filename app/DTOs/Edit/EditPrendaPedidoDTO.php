<?php

namespace App\DTOs\Edit;

/**
 * EditPrendaPedidoDTO - DTO para edición segura de prenda persistida
 * 
 * ARQUITECTURA DE SEPARACIÓN:
 * - Creación: usa PrendaCreationDTO (extrae todo del DOM)
 * - Edición: usa EditPrendaPedidoDTO (PATCH parcial, solo campos explícitos)
 * 
 * Características:
 * ✓ Solo acepta campos explícitamente enviados
 * ✓ No fuerza estructura completa (PATCH != PUT)
 * ✓ Preserva campos no mencionados
 * ✓ Valida restricciones de negocio (tallas con procesos, etc)
 * 
 * Regla de oro: Si un campo NO viene en el payload, se ignora completamente
 */
class EditPrendaPedidoDTO
{
    /**
     * @var int|null - ID de la prenda a editar
     */
    public ?int $id;

    /**
     * @var string|null - Nombre de la prenda (opcional)
     */
    public ?string $nombre_prenda;

    /**
     * @var string|null - Descripción (opcional)
     */
    public ?string $descripcion;

    /**
     * @var int|null - Cantidad total (opcional, con validación de procesos)
     */
    public ?int $cantidad;

    /**
     * @var bool|null - De bodega (opcional)
     */
    public ?bool $de_bodega;

    /**
     * @var array|null - Tallas a actualizar mediante MERGE (opcional)
     * Estructura:
     * [
     *   ['genero' => 'dama', 'talla' => 'M', 'cantidad' => 10],
     *   ['genero' => 'dama', 'talla' => 'L', 'cantidad' => 15],
     * ]
     */
    public ?array $tallas;

    /**
     * @var array|null - Variantes a actualizar mediante MERGE (opcional)
     * Estructura:
     * [
     *   [
     *     'id' => 1, // Si viene id: es UPDATE
     *     'tipo_manga_id' => 2,
     *     'tipo_broche_boton_id' => 1,
     *     'tiene_bolsillos' => true,
     *     'tiene_reflectivo' => false,
     *   ],
     *   [
     *     // Sin id: es CREATE
     *     'tipo_manga_id' => 3,
     *     'tipo_broche_boton_id' => 2,
     *     'tiene_bolsillos' => false,
     *   ]
     * ]
     */
    public ?array $variantes;

    /**
     * @var array|null - Colores a actualizar mediante MERGE (opcional)
     * Estructura:
     * [
     *   ['id' => 5, ...], // UPDATE
     *   ['color_id' => 3, ...], // CREATE
     * ]
     */
    public ?array $colores;

    /**
     * @var array|null - Telas a actualizar mediante MERGE (opcional)
     * Estructura:
     * [
     *   ['id' => 2, ...], // UPDATE
     *   ['tela_id' => 4, ...], // CREATE
     * ]
     */
    public ?array $telas;

    /**
     * @var array|null - Campos que explícitamente NO deben actualizarse
     * Se ignoran completamente aunque vengan en el payload
     * Campos prohibidos: procesos, fotos (se manejan separadamente)
     */
    public ?array $prohibited_fields = [
        'procesos',
        'fotos',
        'id',
        'pedido_produccion_id',
        'numero_pedido',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var array - Campos que SÍ pueden ser actualizados
     */
    public array $allowed_fields = [
        'nombre_prenda',
        'descripcion',
        'cantidad',
        'de_bodega',
        'tallas',
        'variantes',
        'colores',
        'telas',
    ];

    public function __construct(
        ?int $id = null,
        ?string $nombre_prenda = null,
        ?string $descripcion = null,
        ?int $cantidad = null,
        ?bool $de_bodega = null,
        ?array $tallas = null,
        ?array $variantes = null,
        ?array $colores = null,
        ?array $telas = null
    ) {
        $this->id = $id;
        $this->nombre_prenda = $nombre_prenda;
        $this->descripcion = $descripcion;
        $this->cantidad = $cantidad;
        $this->de_bodega = $de_bodega;
        $this->tallas = $tallas;
        $this->variantes = $variantes;
        $this->colores = $colores;
        $this->telas = $telas;
    }

    /**
     * Obtener solo los campos que fueron explícitamente enviados
     * 
     * @return array Campos no nulos
     */
    public function getExplicitFields(): array
    {
        return array_filter([
            'nombre_prenda' => $this->nombre_prenda,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'de_bodega' => $this->de_bodega,
            'tallas' => $this->tallas,
            'variantes' => $this->variantes,
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
     * Obtener los campos simples a actualizar (no relaciones)
     * 
     * @return array
     */
    public function getSimpleFields(): array
    {
        return array_filter([
            'nombre_prenda' => $this->nombre_prenda,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'de_bodega' => $this->de_bodega,
        ], fn($value) => $value !== null);
    }

    /**
     * Obtener los campos de relaciones que necesitan MERGE
     * 
     * @return array
     */
    public function getRelationshipFields(): array
    {
        return array_filter([
            'tallas' => $this->tallas,
            'variantes' => $this->variantes,
            'colores' => $this->colores,
            'telas' => $this->telas,
        ], fn($value) => $value !== null);
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
            nombre_prenda: $payload['nombre_prenda'] ?? null,
            descripcion: $payload['descripcion'] ?? null,
            cantidad: $payload['cantidad'] ?? null,
            de_bodega: $payload['de_bodega'] ?? null,
            tallas: $payload['tallas'] ?? null,
            variantes: $payload['variantes'] ?? null,
            colores: $payload['colores'] ?? null,
            telas: $payload['telas'] ?? null,
        );
    }
}
