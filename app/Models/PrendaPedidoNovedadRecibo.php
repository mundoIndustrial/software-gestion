<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrendaPedidoNovedadRecibo extends Model
{
    use HasFactory;

    protected $table = 'prendas_pedido_novedades_recibo';

    protected $fillable = [
        'prenda_pedido_id',
        'numero_recibo',
        'novedad_texto',
        'tipo_novedad',
        'creado_por',
        'estado_novedad',
        'notas_adicionales',
        'fecha_resolucion',
        'resuelto_por',
    ];

    protected $casts = [
        'creado_en' => 'datetime',
        'fecha_resolucion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constantes para tipos de novedad
    const TIPO_OBSERVACION = 'observacion';
    const TIPO_PROBLEMA = 'problema';
    const TIPO_CAMBIO = 'cambio';
    const TIPO_APROBACION = 'aprobacion';
    const TIPO_RECHAZO = 'rechazo';
    const TIPO_CORRECCION = 'correccion';
    const TIPO_NOVEDAD = 'novedad';

    // Constantes para estado de novedad
    const ESTADO_ACTIVA = 'activa';
    const ESTADO_RESUELTA = 'resuelta';
    const ESTADO_PENDIENTE = 'pendiente';

    /**
     * Relación con la prenda del pedido
     */
    public function prendaPedido()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación con el usuario que creó la novedad
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con el usuario que resolvió la novedad
     */
    public function resueltoPor()
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    /**
     * Scope para filtrar por tipo de novedad
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_novedad', $tipo);
    }

    /**
     * Scope para filtrar por estado de novedad
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado_novedad', $estado);
    }

    /**
     * Scope para filtrar por número de recibo
     */
    public function scopePorRecibo($query, $numeroRecibo)
    {
        return $query->where('numero_recibo', $numeroRecibo);
    }

    /**
     * Scope para novedades activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado_novedad', self::ESTADO_ACTIVA);
    }

    /**
     * Scope para novedades resueltas
     */
    public function scopeResueltas($query)
    {
        return $query->where('estado_novedad', self::ESTADO_RESUELTA);
    }

    /**
     * Scope para novedades pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_novedad', self::ESTADO_PENDIENTE);
    }

    /**
     * Marcar novedad como resuelta
     */
    public function marcarComoResuelta($userId, $notas = null)
    {
        $this->estado_novedad = self::ESTADO_RESUELTA;
        $this->fecha_resolucion = now();
        $this->resuelto_por = $userId;
        
        if ($notas) {
            $this->notas_adicionales = $notas;
        }
        
        return $this->save();
    }

    /**
     * Obtener texto formateado para mostrar
     */
    public function getTextoFormateado($maxLength = 50)
    {
        $texto = $this->novedad_texto;
        
        if (strlen($texto) > $maxLength) {
            return substr($texto, 0, $maxLength) . '...';
        }
        
        return $texto;
    }

    /**
     * Obtener etiqueta HTML para el tipo de novedad
     */
    public function getEtiquetaTipo()
    {
        $colores = [
            self::TIPO_OBSERVACION => 'info',
            self::TIPO_PROBLEMA => 'danger',
            self::TIPO_CAMBIO => 'warning',
            self::TIPO_APROBACION => 'success',
            self::TIPO_RECHAZO => 'danger',
            self::TIPO_CORRECCION => 'warning',
        ];

        $color = $colores[$this->tipo_novedad] ?? 'secondary';
        
        return "<span class='badge bg-{$color}'>" . 
               ucfirst($this->tipo_novedad) . 
               "</span>";
    }

    /**
     * Obtener etiqueta HTML para el estado
     */
    public function getEtiquetaEstado()
    {
        $colores = [
            self::ESTADO_ACTIVA => 'primary',
            self::ESTADO_RESUELTA => 'success',
            self::ESTADO_PENDIENTE => 'warning',
        ];

        $color = $colores[$this->estado_novedad] ?? 'secondary';
        
        return "<span class='badge bg-{$color}'>" . 
               ucfirst($this->estado_novedad) . 
               "</span>";
    }
}
