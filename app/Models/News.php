<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\NewNotificationEvent;

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
        'read_at',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($news) {
            broadcast(new NewNotificationEvent($news, $news->user_id))->toOthers();
        });
    }

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

    /**
     * Scope para filtrar notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope para filtrar notificaciones leídas
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Marcar como leída
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Marcar como no leída
     */
    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
    }
}
