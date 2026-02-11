<?php

namespace App\Application\Pedidos\DTOs;

/**
 * Data Transfer Object para variaciones de prendas procesadas
 */
class VariacionPrendaDTO
{
    public function __construct(
        public readonly array $variaciones_procesadas,
        public readonly array $configuracion_ui,
        public readonly array $genero,
        public readonly array $tipos_detectados,
        public readonly bool $es_valida,
        public readonly array $errores,
        public readonly bool $tiene_variaciones,
        public readonly array $resumen
    ) {}

    /**
     * Crear desde array crudo
     */
    public static function fromArray(array $data): self
    {
        return new self(
            variaciones_procesadas: $data['variaciones_procesadas'] ?? [],
            configuracion_ui: $data['configuracion_ui'] ?? [],
            genero: $data['genero'] ?? ['id' => null, 'nombre' => null],
            tipos_detectados: $data['tipos_detectados'] ?? [],
            es_valida: $data['es_valida'] ?? true,
            errores: $data['errores'] ?? [],
            tiene_variaciones: $data['tiene_variaciones'] ?? false,
            resumen: $data['resumen'] ?? []
        );
    }

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'variaciones_procesadas' => $this->variaciones_procesadas,
            'configuracion_ui' => $this->configuracion_ui,
            'genero' => $this->genero,
            'tipos_detectados' => $this->tipos_detectados,
            'es_valida' => $this->es_valida,
            'errores' => $this->errores,
            'tiene_variaciones' => $this->tiene_variaciones,
            'resumen' => $this->resumen,
            'ui_instructions' => [
                'marcar_checkboxes' => $this->generarInstruccionesCheckboxes(),
                'llenar_inputs' => $this->generarInstruccionesInputs(),
                'disparar_eventos' => $this->generarInstruccionesEventos()
            ]
        ];
    }

    /**
     * Obtener variación procesada por tipo
     */
    public function getVariacion(string $tipo): ?array
    {
        return $this->variaciones_procesadas[$tipo] ?? null;
    }

    /**
     * Obtener configuración UI para un elemento específico
     */
    public function getConfiguracionUIElemento(string $elemento): ?array
    {
        return $this->configuracion_ui[$elemento] ?? null;
    }

    /**
     * Obtener configuración UI completa
     */
    public function getConfiguracionUI(): array
    {
        return $this->configuracion_ui;
    }

    /**
     * Verificar si es válida
     */
    public function esValida(): bool
    {
        return $this->es_valida;
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
        return $this->tiene_variaciones;
    }

    /**
     * Obtener tipos detectados
     */
    public function getTiposDetectados(): array
    {
        return $this->tipos_detectados;
    }

    /**
     * Generar instrucciones para checkboxes
     */
    private function generarInstruccionesCheckboxes(): array
    {
        $instrucciones = [];
        
        foreach ($this->configuracion_ui as $clave => $config) {
            if ($clave === 'genero') {
                $instrucciones[] = [
                    'accion' => 'marcar_checkbox',
                    'selector' => $config['checkbox_selector'],
                    'valor' => $config['valor'],
                    'id' => $config['checkbox_id'],
                    'evento' => $config['evento']
                ];
            } else {
                $instrucciones[] = [
                    'accion' => 'marcar_checkbox',
                    'checkbox_id' => $config['checkbox_id'],
                    'marcado' => $config['checkbox_marcado'],
                    'evento' => $config['evento_checkbox']
                ];
            }
        }
        
        return $instrucciones;
    }

    /**
     * Generar instrucciones para inputs
     */
    private function generarInstruccionesInputs(): array
    {
        $instrucciones = [];
        
        foreach ($this->configuracion_ui as $clave => $config) {
            if ($clave !== 'genero' && $config['tiene_input']) {
                $instrucciones[] = [
                    'accion' => 'llenar_input',
                    'input_id' => $config['input_id'],
                    'valor' => $config['input_valor'],
                    'evento' => $config['evento_input']
                ];
            }
        }
        
        return $instrucciones;
    }

    /**
     * Generar instrucciones para eventos
     */
    private function generarInstruccionesEventos(): array
    {
        $eventos = [];
        
        foreach ($this->configuracion_ui as $clave => $config) {
            if ($clave === 'genero') {
                $eventos[] = [
                    'elemento' => 'checkbox_genero',
                    'evento' => $config['evento'],
                    'selector' => $config['checkbox_selector']
                ];
            } else {
                $eventos[] = [
                    'elemento' => 'checkbox_' . $clave,
                    'evento' => $config['evento_checkbox'],
                    'input_id' => $config['input_id'] ?? null,
                    'observacion_id' => $config['observacion_id'] ?? null
                ];
            }
        }
        
        return $eventos;
    }

    /**
     * Obtener estadísticas para debugging
     */
    public function getEstadisticas(): array
    {
        return [
            'total_variaciones' => count($this->variaciones_procesadas),
            'variaciones_aplicadas' => count(array_filter($this->variaciones_procesadas, fn($v) => $v['aplicado'])),
            'tipos_unicos' => array_unique(array_keys($this->variaciones_procesadas)),
            'genero_configurado' => !is_null($this->genero['id']),
            'configuracion_ui_generada' => !empty($this->configuracion_ui),
            'cantidad_errores' => count($this->errores),
            'es_valida' => $this->es_valida
        ];
    }
}
