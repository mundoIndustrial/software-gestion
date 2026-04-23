<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemError extends Model
{
    protected $table = 'system_errors';

    protected $fillable = [
        'tipo',
        'mensaje',
        'detalles',
        'origen',
        'url_pagina',
        'navegador',
        'usuario_id',
        'pedido_id',
        'ocurrido_en'
    ];

    protected $casts = [
        'detalles' => 'array',
        'ocurrido_en' => 'datetime'
    ];

    /**
     * Relación con usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Scopes útiles
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorOrigen($query, $origen)
    {
        return $query->where('origen', $origen);
    }

    public function scopeRecientes($query, $horas = 24)
    {
        return $query->where('ocurrido_en', '>=', now()->subHours($horas));
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeDelPedido($query, $pedidoId)
    {
        return $query->where('pedido_id', $pedidoId);
    }

    /**
     * Método estático para registrar un error desde JavaScript
     */
    public static function registrarDesdeJavaScript($data)
    {
        try {
            $usuarioId = auth()->id();
            $pedidoId = null;

            // Extraer pedido_id de múltiples lugares
            // Prioridad: contexto > detalles > data del servidor
            if (isset($data['contexto']) && is_array($data['contexto'])) {
                $pedidoId = $data['contexto']['pedido_id'] ?? null;
                if (!$usuarioId && isset($data['contexto']['usuario_id'])) {
                    $usuarioId = $data['contexto']['usuario_id'];
                }
            }

            if (!$pedidoId && isset($data['detalles']) && is_array($data['detalles'])) {
                $pedidoId = $data['detalles']['pedido_id'] ?? null;
                if (!$usuarioId && isset($data['detalles']['usuario_id'])) {
                    $usuarioId = $data['detalles']['usuario_id'];
                }
            }

            // Validar que sean números
            $usuarioId = is_numeric($usuarioId) && $usuarioId > 0 ? (int) $usuarioId : null;
            $pedidoId = is_numeric($pedidoId) && $pedidoId > 0 ? (int) $pedidoId : null;

            return static::create([
                'tipo' => $data['tipo'] ?? 'ERROR_DESCONOCIDO',
                'mensaje' => $data['mensaje'] ?? $data['error'] ?? 'Sin mensaje',
                'detalles' => [
                    ...$data['detalles'] ?? [],
                    ...$data['contexto'] ?? [],
                    'cliente_js' => true // Marca que vino desde JS
                ],
                'origen' => $data['origen'] ?? 'client-js',
                'url_pagina' => $data['url_pagina'] ?? request()->url(),
                'navegador' => request()->userAgent(),
                'usuario_id' => $usuarioId,
                'pedido_id' => $pedidoId,
                'ocurrido_en' => now()
            ]);
        } catch (\Exception $e) {
            \Log::warning('[SystemError] Error registrando error del cliente:', [
                'original_error' => $data,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }
}
