<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ReciboPrenda extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedidos_produccion';

    protected $fillable = [
        'numero_pedido',
        'asesor_id',
        'cliente_id',
        'cantidad_total',
        'estado',
        'fecha_estimada_de_entrega',
        'cliente',
        'novedades',
    ];

    protected $casts = [
        'fecha_estimada_de_entrega' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con Asesor
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * Scopes útiles
     */
    public function scopePendiente($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeEntregado($query)
    {
        return $query->where('estado', 'Entregado');
    }

    public function scopeRetrasado($query)
    {
        return $query->where('estado', '!=', 'Entregado')
            ->where('fecha_estimada_de_entrega', '<', now());
    }

    /**
     * Verificar si está retrasado
     */
    public function isRetrasado(): bool
    {
        return $this->estado !== 'Entregado' && 
               $this->fecha_estimada_de_entrega && 
               Carbon::parse($this->fecha_estimada_de_entrega) < now();
    }
}

