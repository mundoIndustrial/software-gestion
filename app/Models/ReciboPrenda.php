<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReciboPrenda extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'recibo_prendas';

    protected $fillable = [
        'numero_pedido',
        'asesor_id',
        'empresa_id',
        'articulo_id',
        'cantidad',
        'observaciones',
        'fecha_entrega',
        'fecha_entrega_real',
        'estado',
        'usuario_bodeguero_id',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'fecha_entrega_real' => 'datetime',
        'cantidad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'estado' => 'pendiente',
    ];

    // Logging de actividad
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['numero_pedido', 'estado', 'observaciones', 'fecha_entrega'])
            ->useLogName('bodega')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relación con Asesor
     */
    public function asesor()
    {
        return $this->belongsTo(Asesor::class, 'asesor_id');
    }

    /**
     * Relación con Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Relación con Artículo
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'articulo_id');
    }

    /**
     * Relación con Usuario (Bodeguero que entregó)
     */
    public function usuarioBodeguero()
    {
        return $this->belongsTo(User::class, 'usuario_bodeguero_id');
    }

    /**
     * Scopes útiles
     */
    public function scopePendiente($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEntregado($query)
    {
        return $query->where('estado', 'entregado');
    }

    public function scopeRetrasado($query)
    {
        return $query->where('estado', 'pendiente')
            ->where('fecha_entrega', '<', now());
    }

    public function scopePorAsesor($query, $asesorId)
    {
        return $query->where('asesor_id', $asesorId);
    }

    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopePorPedido($query, $numeroPedido)
    {
        return $query->where('numero_pedido', $numeroPedido);
    }

    /**
     * Mutadores
     */
    public function getEstadoEtiquetaAttribute(): string
    {
        return match($this->estado) {
            'entregado' => '✓ ENTREGADO',
            'retrasado' => '⚠ RETRASADO',
            'pendiente' => '⏳ PENDIENTE',
            default => 'DESCONOCIDO',
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            'entregado' => 'green',
            'retrasado' => 'red',
            'pendiente' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Verificar si está retrasado
     */
    public function isRetrasado(): bool
    {
        return $this->estado !== 'entregado' && 
               $this->fecha_entrega && 
               $this->fecha_entrega < now();
    }

    /**
     * Marcar como entregado
     */
    public function marcarEntregado(?User $usuario = null): bool
    {
        $this->estado = 'entregado';
        $this->fecha_entrega_real = now();
        $this->usuario_bodeguero_id = $usuario?->id ?? auth()->id();

        return $this->save();
    }

    /**
     * Obtener resumen del pedido
     */
    public function getResumen(): array
    {
        return [
            'id' => $this->id,
            'numero_pedido' => $this->numero_pedido,
            'asesor' => $this->asesor->nombre ?? 'N/A',
            'empresa' => $this->empresa->nombre ?? 'N/A',
            'articulo' => $this->articulo->nombre ?? 'N/A',
            'cantidad' => $this->cantidad,
            'estado' => $this->estado,
            'fecha_entrega' => $this->fecha_entrega?->format('d-m-Y'),
            'observaciones' => $this->observaciones,
        ];
    }
}
