<?php

namespace App\Application\Pedidos\DTOs;

/**
 * Data Transfer Object para procesos de prendas procesadas
 */
class ProcesoPrendaDTO
{
    public function __construct(
        public readonly array $procesos_procesados,
        public readonly array $configuracion_ui,
        public readonly bool $es_valido,
        public readonly array $errores,
        public readonly bool $tiene_procesos,
        public readonly array $resumen
    ) {}

    /**
     * Crear desde array crudo
     */
    public static function fromArray(array $data): self
    {
        return new self(
            procesos_procesados: $data['procesos_procesados'] ?? [],
            configuracion_ui: $data['configuracion_ui'] ?? [],
            es_valido: $data['es_valido'] ?? true,
            errores: $data['errores'] ?? [],
            tiene_procesos: $data['tiene_procesos'] ?? false,
            resumen: $data['resumen'] ?? []
        );
    }

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'procesos_procesados' => $this->procesos_procesados,
            'configuracion_ui' => $this->configuracion_ui,
            'es_valido' => $this->es_valido,
            'errores' => $this->errores,
            'tiene_procesos' => $this->tiene_procesos,
            'resumen' => $this->resumen,
            'ui_instructions' => [
                'marcar_checkboxes' => $this->generarInstruccionesCheckboxes(),
                'renderizar_tarjetas' => $this->generarInstruccionesTarjetas(),
                'actualizar_estado' => $this->generarInstruccionesEstado()
            ]
        ];
    }

    /**
     * Obtener proceso procesado por slug
     */
    public function getProceso(string $slug): ?array
    {
        foreach ($this->procesos_procesados as $proceso) {
            if ($proceso['slug'] === $slug) {
                return $proceso;
            }
        }
        
        return null;
    }

    /**
     * Obtener configuración UI para un proceso específico
     */
    public function getConfiguracionUIProceso(string $slug): ?array
    {
        return $this->configuracion_ui[$slug] ?? null;
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
        return $this->es_valido;
    }

    /**
     * Obtener errores de validación
     */
    public function getErrores(): array
    {
        return $this->errores;
    }

    /**
     * Verificar si tiene procesos
     */
    public function tieneProcesos(): bool
    {
        return $this->tiene_procesos;
    }

    /**
     * Obtener resumen
     */
    public function getResumen(): array
    {
        return $this->resumen;
    }

    /**
     * Generar instrucciones para checkboxes
     */
    private function generarInstruccionesCheckboxes(): array
    {
        $instrucciones = [];
        
        foreach ($this->configuracion_ui as $slug => $config) {
            $instrucciones[] = [
                'accion' => 'marcar_checkbox',
                'checkbox_id' => $config['checkbox_id'],
                'marcado' => $config['checkbox_marcado'],
                'data_tipo' => $config['data_tipo'],
                'evento' => $config['evento_checkbox'],
                'ignorar_onclick' => $config['ignorar_onclick'],
                'slug' => $slug
            ];
        }
        
        return $instrucciones;
    }

    /**
     * Generar instrucciones para renderizar tarjetas
     */
    private function generarInstruccionesTarjetas(): array
    {
        return [
            [
                'accion' => 'renderizar_tarjetas',
                'funcion' => 'window.renderizarTarjetasProcesos',
                'disparar_despues' => 100
            ]
        ];
    }

    /**
     * Generar instrucciones para actualizar estado
     */
    private function generarInstruccionesEstado(): array
    {
        return [
            [
                'accion' => 'actualizar_estado_global',
                'variable' => 'window.procesosSeleccionados',
                'estructura' => 'proceso_slug => {datos: datos_proceso}'
            ]
        ];
    }

    /**
     * Obtener estadísticas para debugging
     */
    public function getEstadisticas(): array
    {
        return [
            'total_procesos' => count($this->procesos_procesados),
            'procesos_con_errores' => count(array_filter($this->procesos_procesados, fn($p) => !$p['es_valido'])),
            'tipos_unicos' => array_unique(array_column($this->procesos_procesados, 'tipo')),
            'total_imagenes' => array_sum(array_column($this->procesos_procesados, fn($p) => count($p['imagenes']))),
            'total_ubicaciones' => array_sum(array_column($this->procesos_procesados, fn($p) => count($p['ubicaciones']))),
            'procesos_con_variaciones' => count(array_filter($this->procesos_procesados, fn($p) => !empty($p['variaciones_prenda']))),
            'configuracion_ui_generada' => !empty($this->configuracion_ui),
            'es_valida' => $this->es_valida
        ];
    }

    /**
     * Obtener procesos agrupados por tipo
     */
    public function getProcesosPorTipo(): array
    {
        $agrupados = [];
        
        foreach ($this->procesos_procesados as $proceso) {
            $tipo = $proceso['tipo'];
            
            if (!isset($agrupados[$tipo])) {
                $agrupados[$tipo] = [];
            }
            
            $agrupados[$tipo][] = $proceso;
        }
        
        return $agrupados;
    }

    /**
     * Obtener procesos que requieren ubicaciones
     */
    public function getProcesosConUbicaciones(): array
    {
        return array_filter($this->procesos_procesados, fn($p) => !empty($p['ubicaciones']));
    }

    /**
     * Obtener procesos con imágenes
     */
    public function getProcesosConImagenes(): array
    {
        return array_filter($this->procesos_procesados, fn($p) => !empty($p['imagenes']));
    }

    /**
     * Obtener procesos con tallas definidas
     */
    public function getProcesosConTallas(): array
    {
        return array_filter($this->procesos_procesados, fn($p) => 
            !empty($p['tallas']['dama']) || !empty($p['tallas']['caballero'])
        );
    }
}
