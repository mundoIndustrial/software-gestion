<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsecutivosRecibosPedidos extends Model
{

    protected $table = 'consecutivos_recibos_pedidos';

    protected $fillable = [
        'pedido_produccion_id',
        'prenda_id',
        'tipo_recibo',
        'consecutivo_actual',
        'consecutivo_inicial',
        'activo',
        'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación: Un consecutivo pertenece a un pedido
     */
    public function pedido()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un consecutivo pertenece a una prenda
     */
    public function prenda()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_id');
    }

    /**
     * Scope para obtener consecutivos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para obtener consecutivos por tipo de recibo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_recibo', $tipo);
    }

    /**
     * Obtener el siguiente consecutivo
     */
    public function getSiguienteConsecutivo()
    {
        return $this->consecutivo_actual + 1;
    }

    /**
     * Incrementar el consecutivo actual
     */
    public function incrementarConsecutivo()
    {
        $this->increment('consecutivo_actual');
        return $this->fresh();
    }

    /**
     * Verificar si hay consecutivos disponibles
     */
    public function tieneConsecutivosDisponibles()
    {
        // Lógica para verificar si hay consecutivos disponibles
        // Por ahora, siempre devuelve true, pero se puede personalizar
        return true;
    }

    /**
     * Obtener el número de recibo formateado
     */
    public function getNumeroRecibo()
    {
        return $this->tipo_recibo . ' #' . $this->consecutivo_actual;
    }

    /**
     * Crear un nuevo consecutivo para una prenda
     */
    public static function crearConsecutivo($pedidoId, $prendaId, $tipoRecibo, $consecutivoInicial = 1, $notas = null)
    {
        return self::create([
            'pedido_produccion_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'tipo_recibo' => $tipoRecibo,
            'consecutivo_actual' => $consecutivoInicial,
            'consecutivo_inicial' => $consecutivoInicial,
            'activo' => true,
            'notas' => $notas,
        ]);
    }

    /**
     * Obtener o crear consecutivo para una prenda y tipo
     */
    public static function obtenerOCrear($pedidoId, $prendaId, $tipoRecibo, $consecutivoInicial = 1)
    {
        $consecutivo = self::where([
            'pedido_produccion_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'tipo_recibo' => $tipoRecibo,
            'activo' => true
        ])->first();

        if (!$consecutivo) {
            $consecutivo = self::crearConsecutivo($pedidoId, $prendaId, $tipoRecibo, $consecutivoInicial);
        }

        return $consecutivo;
    }
}
