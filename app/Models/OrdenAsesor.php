<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenAsesor extends Model
{
    use HasFactory;

    protected $table = 'ordenes_asesores';

    protected $fillable = [
        'numero_orden',
        'asesor_id',
        'cliente',
        'telefono',
        'email',
        'descripcion',
        'monto_total',
        'cantidad_prendas',
        'estado',
        'prioridad',
        'fecha_entrega',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'monto_total' => 'decimal:2',
        'cantidad_prendas' => 'integer',
    ];

    /**
     * Relación con el asesor (usuario)
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Scope para filtrar por asesor
     */
    public function scopeDelAsesor($query, $asesorId)
    {
        return $query->where('asesor_id', $asesorId);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para órdenes del día
     */
    public function scopeDelDia($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope para órdenes del mes
     */
    public function scopeDelMes($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Scope para órdenes del año
     */
    public function scopeDelAnio($query)
    {
        return $query->whereYear('created_at', now()->year);
    }
}
