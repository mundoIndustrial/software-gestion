<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    protected $fillable = [
        'event_type',
        'table_name',
        'record_id',
        'description',
        'user_id',
        'pedido',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relación con el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrar por tipo de evento
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope para filtrar por tabla
     */
    public function scopeOfTable($query, $table)
    {
        return $query->where('table_name', $table);
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopeOfDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    /**
     * Scope para obtener registros recientes
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
