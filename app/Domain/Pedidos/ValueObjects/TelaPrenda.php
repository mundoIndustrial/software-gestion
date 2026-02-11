<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: TelaPrenda
 * 
 * Representa una tela con sus características completas
 */
class TelaPrenda
{
    private ?int $id;
    private string $nombre;
    private string $color;
    private string $referencia;
    private ?string $descripcion;
    private ?string $grosor;
    private ?string $composicion;
    private array $imagenes;
    private ?string $origen;
    
    public function __construct(
        ?int $id,
        string $nombre,
        string $color,
        string $referencia = '',
        ?string $descripcion = null,
        ?string $grosor = null,
        ?string $composicion = null,
        array $imagenes = [],
        ?string $origen = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->color = $color;
        $this->referencia = $referencia;
        $this->descripcion = $descripcion;
        $this->grosor = $grosor;
        $this->composicion = $composicion;
        $this->imagenes = $imagenes;
        $this->origen = $origen;
        
        $this->validar();
    }
    
    private function validar(): void
    {
        if (empty(trim($this->nombre))) {
            throw new \InvalidArgumentException('El nombre de la tela es requerido');
        }
        
        if (empty(trim($this->color))) {
            throw new \InvalidArgumentException('El color de la tela es requerido');
        }
    }
    
    public function id(): ?int { return $this->id; }
    public function nombre(): string { return $this->nombre; }
    public function color(): string { return $this->color; }
    public function referencia(): string { return $this->referencia; }
    public function descripcion(): ?string { return $this->descripcion; }
    public function grosor(): ?string { return $this->grosor; }
    public function composicion(): ?string { return $this->composicion; }
    public function imagenes(): array { return $this->imagenes; }
    public function origen(): ?string { return $this->origen; }
    
    public function tieneReferencia(): bool
    {
        return !empty(trim($this->referencia));
    }
    
    public function tieneImagenes(): bool
    {
        return !empty($this->imagenes);
    }
    
    public function cantidadImagenes(): int
    {
        return count($this->imagenes);
    }
    
    /**
     * Obtiene la clave única para identificar esta tela
     */
    public function clave(): string
    {
        return strtolower(trim($this->nombre) . '|' . trim($this->color));
    }
    
    /**
     * Enriquece esta tela con datos de otra (usualmente desde variantes)
     */
    public function enriquecerDesde(TelaPrenda $otra): void
    {
        if (!$this->tieneReferencia() && $otra->tieneReferencia()) {
            $this->referencia = $otra->referencia();
            $this->origen = 'enriquecido_desde_variantes';
        }
        
        if (!$this->descripcion && $otra->descripcion()) {
            $this->descripcion = $otra->descripcion();
        }
        
        if (!$this->grosor && $otra->grosor()) {
            $this->grosor = $otra->grosor();
        }
        
        if (!$this->composicion && $otra->composicion()) {
            $this->composicion = $otra->composicion();
        }
    }
    
    public function equals(TelaPrenda $otra): bool
    {
        return $this->clave() === $otra->clave();
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre_tela' => $this->nombre,
            'color' => $this->color,
            'referencia' => $this->referencia,
            'descripcion' => $this->descripcion,
            'grosor' => $this->grosor,
            'composicion' => $this->composicion,
            'imagenes' => $this->imagenes,
            'origen' => $this->origen,
            'tiene_referencia' => $this->tieneReferencia(),
            'tiene_imagenes' => $this->tieneImagenes(),
            'cantidad_imagenes' => $this->cantidadImagenes(),
            'clave' => $this->clave()
        ];
    }
    
    /**
     * Crea desde ColorTela de la base de datos
     */
    public static function desdeColorTela(object $colorTela): self
    {
        $imagenes = [];
        
        // Procesar fotos: puede venir en fotos o fotos_tela
        $fotosArray = $colorTela->fotos ?? $colorTela->fotos_tela ?? [];
        
        if (is_array($fotosArray)) {
            foreach ($fotosArray as $foto) {
                $imagenes[] = [
                    'url' => $foto->ruta_webp ?? $foto->ruta_original ?? $foto->url ?? '',
                    'ruta_webp' => $foto->ruta_webp ?? '',
                    'ruta_original' => $foto->ruta_original ?? '',
                    'previewUrl' => $foto->ruta_webp ?? $foto->ruta_original ?? $foto->url ?? ''
                ];
            }
        }
        
        return new self(
            $colorTela->id ?? null,
            $colorTela->tela_nombre ?? '(Sin nombre)',
            $colorTela->color_nombre ?? '(Sin color)',
            $colorTela->referencia ?? $colorTela->tela_referencia ?? '',
            null, // descripción
            null, // grosor
            null, // composición
            $imagenes,
            'backend'
        );
    }
    
    /**
     * Crea desde variante (telas_multiples)
     */
    public static function desdeVariante(object $telaVariante, int $varianteIndex = 0, int $telaIndex = 0): self
    {
        $imagenes = [];
        
        if (isset($telaVariante->imagenes) && is_array($telaVariante->imagenes)) {
            foreach ($telaVariante->imagenes as $img) {
                $imagenes[] = [
                    'url' => $img->url ?? $img->ruta ?? '',
                    'ruta_webp' => $img->ruta_webp ?? '',
                    'ruta_original' => $img->ruta_original ?? '',
                    'previewUrl' => $img->url ?? $img->ruta ?? ''
                ];
            }
        }
        
        return new self(
            $telaVariante->id ?? null,
            $telaVariante->tela ?? $telaVariante->nombre_tela ?? '',
            $telaVariante->color ?? '',
            $telaVariante->referencia ?? '',
            $telaVariante->descripcion ?? '',
            $telaVariante->grosor ?? '',
            $telaVariante->composicion ?? '',
            $imagenes,
            'variante_directa_modal'
        );
    }
}
