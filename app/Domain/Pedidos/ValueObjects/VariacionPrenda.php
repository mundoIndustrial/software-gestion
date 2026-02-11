<?php

namespace App\Domain\Pedidos\ValueObjects;

use App\Domain\Pedidos\Enums\GeneroPrenda;
use App\Domain\Pedidos\Enums\TipoVariacion;

/**
 * Value Object para manejo de variaciones de prendas
 * 
 * Encapsula la lógica compleja de procesamiento de variaciones:
 * - Detección automática de tipos de variación
 * - Normalización de datos de entrada
 * - Validación de combinaciones permitidas
 * - Generación de configuración para UI
 */
class VariacionPrenda
{
    private ?int $generoId;
    private ?string $generoNombre;
    private array $variaciones;
    private array $configuracionUI;
    private bool $esValida;
    private array $errores;

    public function __construct(array $datosVariantes = [])
    {
        $this->variaciones = [];
        $this->configuracionUI = [];
        $this->esValida = true;
        $this->errores = [];
        
        if (!empty($datosVariantes)) {
            $this->procesarVariantes($datosVariantes);
        }
    }

    /**
     * Procesar variaciones desde diferentes fuentes de datos
     */
    public function procesarVariantes(array $datosVariantes): self
    {
        // 1. Extraer variaciones de múltiples fuentes
        $variantesExtraidas = $this->extraerVariantes($datosVariantes);
        
        // 2. Procesar género
        $this->procesarGenero($variantesExtraidas);
        
        // 3. Procesar cada tipo de variación
        foreach (TipoVariacion::cases() as $tipo) {
            $this->procesarTipoVariacion($tipo, $variantesExtraidas);
        }
        
        // 4. Validar combinaciones
        $this->validarCombinaciones();
        
        // 5. Generar configuración para UI
        $this->generarConfiguracionUI();
        
        return $this;
    }

    /**
     * Extraer variaciones desde diferentes fuentes
     */
    private function extraerVariantes(array $datos): array
    {
        $variantes = $datos['variantes'] ?? [];
        
        // Si variantes está vacío pero hay procesos (Logo/Reflectivo), extraer de los procesos
        if (empty($variantes) && isset($datos['procesos'])) {
            $procesosArray = is_array($datos['procesos']) ? $datos['procesos'] : array_values($datos['procesos']);
            
            if (!empty($procesosArray) && isset($procesosArray[0]['variaciones_prenda'])) {
                $variantes = $procesosArray[0]['variaciones_prenda'];
            }
        }
        
        return $variantes;
    }

    /**
     * Procesar género desde variantes
     */
    private function procesarGenero(array $variantes): void
    {
        if (!isset($variantes['genero_id'])) {
            return;
        }
        
        $generoId = $variantes['genero_id'];
        $this->generoId = is_numeric($generoId) ? (int)$generoId : null;
        
        // Mapear ID a nombre
        $generoMap = [
            1 => GeneroPrenda::DAMA->value,
            2 => GeneroPrenda::CABALLERO->value,
            3 => GeneroPrenda::UNISEX->value
        ];
        
        $this->generoNombre = $generoMap[$this->generoId] ?? null;
        
        // Si viene nombre en los datos, usarlo
        if (isset($variantes['genero']['nombre'])) {
            $this->generoNombre = strtoupper($variantes['genero']['nombre']);
        }
    }

    /**
     * Procesar un tipo específico de variación
     */
    private function procesarTipoVariacion(TipoVariacion $tipo, array $variantes): void
    {
        $campo = $tipo->getCampoFormulario();
        $datosVariacion = $variantes[$campo] ?? [];
        
        if (empty($datosVariacion)) {
            return;
        }
        
        $variacion = [
            'tipo' => $tipo->value,
            'opcion' => $this->extraerOpcion($datosVariacion, $tipo),
            'observacion' => $this->extraerObservacion($datosVariacion, $tipo),
            'id' => $this->extraerId($datosVariacion, $tipo),
            'aplicado' => !empty($datosVariacion),
            'normalizado' => $this->normalizarValor($this->extraerOpcion($datosVariacion, $tipo))
        ];
        
        $this->variaciones[$tipo->value] = $variacion;
    }

    /**
     * Extraer opción de variación (maneja múltiples formatos)
     */
    private function extraerOpcion(array $datosVariacion, TipoVariacion $tipo): string
    {
        // Prioridad 1: Campo directo (nuevo formato)
        $campoDirecto = 'tipo_' . $tipo->value;
        if (isset($datosVariacion[$campoDirecto]) && is_string($datosVariacion[$campoDirecto])) {
            return $datosVariacion[$campoDirecto];
        }
        
        // Prioridad 2: Objeto anidado (formato antiguo)
        if (isset($datosVariacion['opcion']) && is_string($datosVariacion['opcion'])) {
            return $datosVariacion['opcion'];
        }
        
        // Prioridad 3: Otros campos posibles
        $camposAlternativos = ['opcion', 'tipo', 'manga', 'broche'];
        foreach ($camposAlternativos as $campo) {
            if (isset($datosVariacion[$campo]) && is_string($datosVariacion[$campo])) {
                return $datosVariacion[$campo];
            }
        }
        
        return '';
    }

    /**
     * Extraer observación de variación
     */
    private function extraerObservacion(array $datosVariacion, TipoVariacion $tipo): string
    {
        $campoObs = 'obs_' . $tipo->value;
        
        // Prioridad 1: Campo específico
        if (isset($datosVariacion[$campoObs])) {
            return $datosVariacion[$campoObs];
        }
        
        // Prioridad 2: Campo genérico
        if (isset($datosVariacion['observacion'])) {
            return $datosVariacion['observacion'];
        }
        
        // Prioridad 3: Campo en objeto anidado
        if (isset($datosVariacion['observacion'])) {
            return $datosVariacion['observacion'];
        }
        
        return '';
    }

    /**
     * Extraer ID de variación
     */
    private function extraerId(array $datosVariacion, TipoVariacion $tipo): ?int
    {
        $campoId = 'tipo_' . $tipo->value . '_id';
        
        if (isset($datosVariacion[$campoId]) && is_numeric($datosVariacion[$campoId])) {
            return (int)$datosVariacion[$campoId];
        }
        
        return null;
    }

    /**
     * Normalizar valor (quitar acentos y convertir a minúsculas)
     */
    private function normalizarValor(string $valor): string
    {
        return strtolower($valor)
            ->replace(['á', 'à', 'ä', 'â', 'ã'], 'a')
            ->replace(['é', 'è', 'ë', 'ê'], 'e')
            ->replace(['í', 'ì', 'ï', 'î'], 'i')
            ->replace(['ó', 'ò', 'ö', 'ô', 'õ'], 'o')
            ->replace(['ú', 'ù', 'ü', 'û'], 'u')
            ->replace(['Á', 'À', 'Ä', 'Â', 'Ã'], 'a')
            ->replace(['É', 'È', 'Ë', 'Ê'], 'e')
            ->replace(['Í', 'Ì', 'Ï', 'Î'], 'i')
            ->replace(['Ó', 'Ò', 'Ö', 'Ô', 'Õ'], 'o')
            ->replace(['Ú', 'Ù', 'Ü', 'Û'], 'u');
    }

    /**
     * Validar combinaciones de variaciones permitidas
     */
    private function validarCombinaciones(): void
    {
        $errores = [];
        
        // Validar que no haya combinaciones contradictorias
        foreach ($this->variaciones as $tipo => $variacion) {
            if ($variacion['aplicado']) {
                // Validar reglas específicas por tipo
                $erroresTipo = $this->validarReglasTipo($tipo, $variacion);
                $errores = array_merge($errores, $erroresTipo);
            }
        }
        
        $this->errores = $errores;
        $this->esValida = empty($errores);
    }

    /**
     * Validar reglas específicas para un tipo de variación
     */
    private function validarReglasTipo(string $tipo, array $variacion): array
    {
        $errores = [];
        
        switch ($tipo) {
            case TipoVariacion::MANGA->value:
                if (empty($variacion['opcion']) && !empty($variacion['observacion'])) {
                    $errores[] = 'Manga requiere opción o descripción';
                }
                break;
                
            case TipoVariacion::BROCHE->value:
                if (empty($variacion['opcion']) && !empty($variacion['observacion'])) {
                    $errores[] = 'Broche requiere opción o descripción';
                }
                break;
                
            case TipoVariacion::BOLILLOS->value:
                if (empty($variacion['opcion']) && empty($variacion['observacion'])) {
                    $errores[] = 'Bolsillos requiere opción o descripción';
                }
                break;
        }
        
        return $errores;
    }

    /**
     * Generar configuración para la UI
     */
    private function generarConfiguracionUI(): void
    {
        $configuracion = [];
        
        foreach ($this->variaciones as $tipo => $variacion) {
            if (!$variacion['aplicado']) {
                continue;
            }
            
            $configuracion[$tipo] = [
                'checkbox_id' => TipoVariacion::from($tipo)->getCheckboxId(),
                'input_id' => $tipo === TipoVariacion::BOLILLOS->value ? null : TipoVariacion::from($tipo)->getCampoInput(),
                'observacion_id' => TipoVariacion::from($tipo)->getCampoObservacion(),
                'opcion' => $variacion['opcion'],
                'observacion' => $variacion['observacion'],
                'valor_normalizado' => $variacion['normalizado'],
                'checked' => true,
                'disabled' => false
            ];
        }
        
        // Agregar configuración de género si existe
        if ($this->generoNombre) {
            $configuracion['genero'] = [
                'id' => $this->generoId,
                'nombre' => $this->generoNombre,
                'checkbox_value' => strtolower($this->generoNombre),
                'checkbox_id' => 'genero-' . strtolower($this->generoNombre)
            ];
        }
        
        $this->configuracionUI = $configuracion;
    }

    /**
     * Obtener variaciones procesadas
     */
    public function getVariaciones(): array
    {
        return $this->variaciones;
    }

    /**
     * Obtener configuración para UI
     */
    public function getConfiguracionUI(): array
    {
        return $this->configuracionUI;
    }

    /**
     * Obtener género
     */
    public function getGenero(): array
    {
        return [
            'id' => $this->generoId,
            'nombre' => $this->generoNombre
        ];
    }

    /**
     * Verificar si es válida
     */
    public function esValida(): bool
    {
        return $this->esValida;
    }

    /**
     * Obtener errores de validación
     */
    public function getErrores(): array
    {
        return $this->errores;
    }

    /**
     * Verificar si tiene variaciones aplicadas
     */
    public function tieneVariaciones(): bool
    {
        foreach ($this->variaciones as $variacion) {
            if ($variacion['aplicado']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtener variación por tipo
     */
    public function getVariacion(string $tipo): ?array
    {
        return $this->variaciones[$tipo] ?? null;
    }

    /**
     * Convertir a array para DTO
     */
    public function toArray(): array
    {
        return [
            'genero' => $this->getGenero(),
            'variaciones' => $this->getVariaciones(),
            'configuracion_ui' => $this->getConfiguracionUI(),
            'es_valida' => $this->esValida(),
            'errores' => $this->getErrores(),
            'tiene_variaciones' => $this->tieneVariaciones(),
            'tipos_activos' => array_keys(array_filter($this->getVariaciones(), fn($v) => $v['aplicado'])),
            'resumen' => [
                'total_variaciones' => count($this->getVariaciones()),
                'variaciones_aplicadas' => count(array_filter($this->getVariaciones(), fn($v) => $v['aplicada'])),
                'tiene_genero' => !is_null($this->generoId)
            ]
        ];
    }
}
