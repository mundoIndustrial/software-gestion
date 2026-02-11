<?php

namespace App\Domain\Pedidos\ValueObjects;

use App\Domain\Pedidos\Enums\TipoProceso;
use App\Domain\Pedidos\Enums\GeneroPrenda;

/**
 * Value Object para manejo de procesos de prendas
 * 
 * Encapsula la lógica compleja de procesamiento de procesos:
 * - Detección automática de tipo de proceso
 * - Normalización de ubicaciones y tallas
 * - Procesamiento de imágenes
 * - Validación de configuraciones
 */
class ProcesoPrenda
{
    private ?int $id;
    private TipoProceso $tipo;
    private string $nombre;
    private string $slug;
    private ?int $tipoProcesoId;
    private array $ubicaciones;
    private string $observaciones;
    private array $tallas;
    private array $imagenes;
    private array $variacionesPrenda;
    private array $tallaCantidad;
    private bool $esValido;
    private array $errores;

    public function __construct(array $datosProceso = [])
    {
        $this->ubicaciones = [];
        $this->tallas = ['dama' => [], 'caballero' => [], 'sobremedida' => []];
        $this->imagenes = [];
        $this->variacionesPrenda = [];
        $this->tallaCantidad = [];
        $this->esValido = true;
        $this->errores = [];
        
        if (!empty($datosProceso)) {
            $this->procesarDatos($datosProceso);
        }
    }

    /**
     * Procesar datos del proceso desde diferentes fuentes
     */
    public function procesarDatos(array $datosProceso): self
    {
        // 1. Extraer datos reales (manejar estructura anidada)
        $datosReales = $this->extraerDatosReales($datosProceso);
        
        // 2. Detectar y configurar tipo
        $this->procesarTipo($datosReales);
        
        // 3. Procesar ubicaciones
        $this->procesarUbicaciones($datosReales);
        
        // 4. Procesar tallas
        $this->procesarTallas($datosReales);
        
        // 5. Procesar imágenes
        $this->procesarImagenes($datosReales);
        
        // 6. Procesar variaciones
        $this->procesarVariaciones($datosReales);
        
        // 7. Procesar talla cantidad
        $this->procesarTallaCantidad($datosReales);
        
        // 8. Validar configuración
        $this->validarConfiguracion();
        
        return $this;
    }

    /**
     * Extraer datos reales (manejar estructura anidada)
     */
    private function extraerDatosReales(array $datosProceso): array
    {
        // Si viene con estructura anidada {datos: {...}}
        if (isset($datosProceso['datos'])) {
            return $datosProceso['datos'];
        }
        
        // Si viene directamente
        return $datosProceso;
    }

    /**
     * Procesar tipo de proceso
     */
    private function procesarTipo(array $datosReales): void
    {
        $this->id = $datosReales['id'] ?? null;
        $this->tipoProcesoId = $datosReales['tipo_proceso_id'] ?? null;
        
        // Detectar tipo usando múltiples campos
        $tipoDetectado = TipoProceso::detectarDesdeDatos($datosReales);
        $this->tipo = $tipoDetectado ?? TipoProceso::OTRO;
        
        // Configurar nombre y slug
        $this->nombre = $datosReales['nombre'] ?? $datosReales['tipo_proceso'] ?? $this->tipo->getNombre();
        $this->slug = $datosReales['slug'] ?? $this->tipo->getSlug();
    }

    /**
     * Procesar ubicaciones (maneja múltiples formatos)
     */
    private function procesarUbicaciones(array $datosReales): void
    {
        if (!isset($datosReales['ubicaciones'])) {
            return;
        }
        
        $ubicaciones = $datosReales['ubicaciones'];
        
        if (is_array($ubicaciones)) {
            // Ya es array, usarlo directamente
            $this->ubicaciones = $ubicaciones;
        } elseif (is_string($ubicaciones)) {
            // String separado por comas, convertir a array
            $this->ubicaciones = array_map('trim', explode(',', $ubicaciones));
        } elseif (is_object($ubicaciones)) {
            // Objeto, extraer valores
            $this->ubicaciones = array_values(array_filter(
                (array)$ubicaciones,
                fn($u) => is_string($u) || is_array($u)
            ));
        }
        
        // Filtrar valores vacíos
        $this->ubicaciones = array_filter($this->ubicaciones, fn($u) => !empty($u));
    }

    /**
     * Procesar tallas
     */
    private function procesarTallas(array $datosReales): void
    {
        $tallas = $datosReales['tallas'] ?? ['dama' => [], 'caballero' => [], 'sobremedida' => []];
        
        if (is_array($tallas)) {
            if (empty($tallas)) {
                // Array vacío, inicializar estructura por defecto
                $this->tallas = ['dama' => [], 'caballero' => [], 'sobremedida' => []];
            } else {
                // Validar estructura de tallas
                $this->tallas = $this->validarEstructuraTallas($tallas);
            }
        }
    }

    /**
     * Validar estructura de tallas
     */
    private function validarEstructuraTallas(array $tallas): array
    {
        $estructuraValida = ['dama' => [], 'caballero' => [], 'sobremedida' => []];
        
        foreach ($estructuraValida as $genero => $valor) {
            if (isset($tallas[$genero]) && is_array($tallas[$genero])) {
                $estructuraValida[$genero] = $tallas[$genero];
            }
        }
        
        return $estructuraValida;
    }

    /**
     * Procesar imágenes
     */
    private function procesarImagenes(array $datosReales): void
    {
        if (!isset($datosReales['imagenes'])) {
            return;
        }
        
        $imagenes = $datosReales['imagenes'];
        
        if (!is_array($imagenes)) {
            return;
        }
        
        $this->imagenes = array_map([$this, 'procesarImagenIndividual'], $imagenes);
        
        // Filtrar URLs vacías
        $this->imagenes = array_filter($this->imagenes, fn($url) => !empty($url));
    }

    /**
     * Procesar imagen individual
     */
    private function procesarImagenIndividual($img): string
    {
        if (is_string($img)) {
            return $this->normalizarUrlImagen($img);
        }
        
        if (is_array($img)) {
            // Prioridad: ruta_original > ruta > ruta_webp > url
            $url = $img['ruta_original'] ?? $img['ruta'] ?? $img['ruta_webp'] ?? $img['url'] ?? '';
            
            if ($url) {
                return $this->normalizarUrlImagen($url);
            }
        }
        
        return '';
    }

    /**
     * Normalizar URL de imagen
     */
    private function normalizarUrlImagen(string $url): string
    {
        $url = trim($url);
        
        // Agregar /storage/ si es necesario
        if ($url && !str_starts_with($url, '/')) {
            $url = '/storage/' . $url;
        }
        
        return $url;
    }

    /**
     * Procesar variaciones de prenda
     */
    private function procesarVariaciones(array $datosReales): void
    {
        $this->variacionesPrenda = $datosReales['variaciones_prenda'] ?? [];
    }

    /**
     * Procesar talla cantidad
     */
    private function procesarTallaCantidad(array $datosReales): void
    {
        $this->tallaCantidad = $datosReales['talla_cantidad'] ?? [];
    }

    /**
     * Validar configuración del proceso
     */
    private function validarConfiguracion(): void
    {
        $errores = [];
        
        // Validar que el tipo sea compatible con los datos
        if ($this->tipo->requiereUbicaciones() && empty($this->ubicaciones)) {
            $errores[] = "El proceso {$this->tipo->getNombre()} requiere ubicaciones";
        }
        
        if ($this->tipo->soportaImagenes() && empty($this->imagenes)) {
            // No es error, solo advertencia
        }
        
        if ($this->tipo->requiereTallas() && empty($this->tallas['dama']) && empty($this->tallas['caballero'])) {
            $errores[] = "El proceso {$this->tipo->getNombre()} requiere tallas definidas";
        }
        
        $this->errores = $errores;
        $this->esValido = empty($errores);
    }

    /**
     * Obtener datos procesados para frontend
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->value,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'tipo_proceso' => $this->tipo->getCampoTipo(),
            'tipo_proceso_id' => $this->tipoProcesoId,
            'ubicaciones' => $this->ubicaciones,
            'observaciones' => $this->observaciones,
            'tallas' => $this->tallas,
            'imagenes' => $this->imagenes,
            'variaciones_prenda' => $this->variacionesPrenda,
            'talla_cantidad' => $this->tallaCantidad,
            'es_valido' => $this->esValido,
            'errores' => $this->errores,
            'requiere_ubicaciones' => $this->tipo->requiereUbicaciones(),
            'soporta_imagenes' => $this->tipo->soportaImagenes(),
            'requiere_tallas' => $this->tipo->requiereTallas(),
            'checkbox_id' => $this->tipo->getCheckboxId(),
            'resumen' => [
                'total_ubicaciones' => count($this->ubicaciones),
                'total_imagenes' => count($this->imagenes),
                'total_tallas_dama' => count($this->tallas['dama']),
                'total_tallas_caballero' => count($this->tallas['caballero']),
                'tiene_variaciones' => !empty($this->variacionesPrenda),
                'tiene_talla_cantidad' => !empty($this->tallaCantidad)
            ]
        ];
    }

    /**
     * Obtener configuración para UI
     */
    public function getConfiguracionUI(): array
    {
        return [
            'checkbox_id' => $this->tipo->getCheckboxId(),
            'checkbox_marcado' => true,
            'data_tipo' => $this->tipo->getSlug(),
            'nombre_proceso' => $this->nombre,
            'observaciones' => $this->observaciones,
            'mostrar_ubicaciones' => !empty($this->ubicaciones),
            'mostrar_imagenes' => !empty($this->imagenes),
            'mostrar_tallas' => $this->tipo->requiereTallas(),
            'requerido' => in_array($this->tipo, [TipoProceso::BORDADO, TipoProceso::SERIGRAFIA]),
            'evento_checkbox' => 'change',
            'ignorar_onclick' => true
        ];
    }

    /**
     * Verificar si es válido
     */
    public function esValido(): bool
    {
        return $this->esValido;
    }

    /**
     * Obtener errores de validación
     */
    public function getErrores(): array
    {
        return $this->errores;
    }

    /**
     * Obtener tipo de proceso
     */
    public function getTipo(): TipoProceso
    {
        return $this->tipo;
    }

    /**
     * Obtener ID del proceso
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Obtener slug
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Verificar si tiene imágenes
     */
    public function tieneImagenes(): bool
    {
        return !empty($this->imagenes);
    }

    /**
     * Verificar si tiene ubicaciones
     */
    public function tieneUbicaciones(): bool
    {
        return !empty($this->ubicaciones);
    }

    /**
     * Verificar si tiene tallas definidas
     */
    public function tieneTallas(): bool
    {
        return !empty($this->tallas['dama']) || !empty($this->tallas['caballero']);
    }
}
