<?php

namespace App\Domain\Recibos\Services;

use App\Models\ConsecutivoReciboPedido;
use App\Services\CalculadorDiasService;
/**
 * Domain Service para lógica de negocio de Recibos de Costura
 * 
 * Responsabilidades:
 * - Cálculos de días hábiles entre fechas
 * - Validaciones de reglas de negocio de recibos
 * - Enriquecimiento de datos de recibos
 */
class RecibosCozturaService
{
    protected $calculadorDias;

    public function __construct(CalculadorDiasService $calculadorDias = null)
    {
        $this->calculadorDias = $calculadorDias ?? app(CalculadorDiasService::class);
    }

    /**
     * Calcular días hábiles desde creación del recibo hasta hoy
     * 
     * @param ConsecutivoReciboPedido $recibo
     * @return int Días hábiles
     */
    public function calcularDiasHabiles(ConsecutivoReciboPedido $recibo): int
    {
        return $this->calculadorDias->calcularDiasHabiles(
            $recibo->created_at->toDateString(),
            now()->toDateString()
        );
    }

    /**
     * Validar si un recibo cumple reglas de negocio
     * 
     * @param ConsecutivoReciboPedido $recibo
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validar(ConsecutivoReciboPedido $recibo): array
    {
        $errores = [];

        // Validar que tenga un pedido asociado
        if (!$recibo->pedido_produccion_id) {
            $errores[] = 'El recibo debe estar asociado a un pedido';
        }

        // Validar que tenga una prenda
        if (!$recibo->prenda_id) {
            $errores[] = 'El recibo debe estar asociado a una prenda';
        }

        // Validar tipo de recibo
        $tiposValidos = ['COSTURA', 'ESTAMPADO', 'BORDADO', 'REFLECTIVO', 'DTF', 'SUBLIMADO', 'COSTURA-BODEGA'];
        if (!in_array($recibo->tipo_recibo, $tiposValidos)) {
            $errores[] = 'Tipo de recibo inválido: ' . $recibo->tipo_recibo;
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Enriquecer recibo con información calculada
     * Aquí se "arma" la descripción y otros datos
     * 
     * @param ConsecutivoReciboPedido $recibo
     * @return array Recibo con datos enriquecidos
     */
    public function enriquecer(ConsecutivoReciboPedido $recibo): array
    {
        $diasHabiles = $this->calcularDiasHabiles($recibo);
        
        // La descripción se construye/arma a partir de la prenda
        $descripcion = $recibo->prenda?->nombre ?? 'Sin nombre';
        
        // El cliente se toma del pedido relacionado
        $cliente = $recibo->pedido?->cliente?->nombre ?? 'N/A';
        
        // Las novedades vienen de las notas del recibo
        $novedades = $recibo->notas ?? 'Sin novedades';

        return [
            'id' => $recibo->id,
            'numero_recibo' => $recibo->consecutivo_actual,
            'tipo_recibo' => $recibo->tipo_recibo,
            'estado' => $recibo->estado,
            'area' => $recibo->area,
            'total_dias' => $diasHabiles,
            'descripcion' => $descripcion,  // ARMADO desde prenda
            'cliente' => $cliente,           // ARMADO desde pedido
            'novedades' => $novedades,       // ARMADO desde notas
            'color_costura' => $recibo->color_costura,
            'fecha_creacion' => $recibo->created_at->format('Y-m-d'),
            'fecha_estimada' => $recibo->pedido?->fecha_estimada_de_entrega?->format('Y-m-d'),
            'encargado_orden' => $recibo->pedido?->usuario?->name ?? 'N/A',
            'pedido_id' => $recibo->pedido_produccion_id,
            'prenda_id' => $recibo->prenda_id,
            'activo' => $recibo->activo,
            'marcar_plooter' => $recibo->marcar_plooter,
        ];
    }

    /**
     * Obtener cantidad total de la prenda
     * 
     * @param ConsecutivoReciboPedido $recibo
     * @return int
     */
    public function obtenerCantidadTotal(ConsecutivoReciboPedido $recibo): int
    {
        // Obtener cantidad desde la talla de la prenda
        return $recibo->prenda?->talla?->obtenerCantidadTotal() ?? 0;
    }

    /**
     * Validar si recibo está en estado crítico (muchos días)
     * 
     * @param ConsecutivoReciboPedido $recibo
     * @param int $diasCriticos Umbral de días críticos (default 30)
     * @return bool
     */
    public function esCritico(ConsecutivoReciboPedido $recibo, int $diasCriticos = 30): bool
    {
        return $this->calcularDiasHabiles($recibo) > $diasCriticos;
    }
}
