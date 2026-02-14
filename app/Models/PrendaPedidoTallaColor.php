<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrendaPedidoTallaColor extends Model
{
    use HasFactory;

    protected $table = 'prenda_pedido_talla_colores';

    protected $fillable = [
        'prenda_pedido_talla_id',
        'tela_id',
        'tela_nombre',
        'color_id',
        'color_nombre',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    //  RELACIONES

    /**
     * Relación: Pertenece a una talla específica
     */
    public function prendaPedidoTalla()
    {
        return $this->belongsTo(PrendaPedidoTalla::class, 'prenda_pedido_talla_id');
    }

    /**
     * Relación: Tela (si está registrada en tabla telas)
     */
    public function tela()
    {
        return $this->belongsTo(Tela::class, 'tela_id');
    }

    /**
     * Relación: Color (si está registrado en tabla colores)
     */
    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    /**
     * Relación: Prenda (a través de talla)
     */
    public function prendaPedido()
    {
        return $this->hasManyThrough(
            PrendaPedido::class,
            PrendaPedidoTalla::class,
            'id',
            'id',
            'prenda_pedido_talla_id',
            'prenda_pedido_id'
        );
    }

    //  SCOPES

    /**
     * Filtrar por nombre de color
     */
    public function scopeByColorNombre($query, $colorNombre)
    {
        return $query->where('color_nombre', 'LIKE', "%{$colorNombre}%");
    }

    /**
     * Filtrar por nombre de tela
     */
    public function scopeByTelaNombre($query, $telaNombre)
    {
        return $query->where('tela_nombre', 'LIKE', "%{$telaNombre}%");
    }

    /**
     * Datos completos (con relaciones cargadas)
     */
    public function scopeWithDetails($query)
    {
        return $query->with(['tela', 'color', 'prendaPedidoTalla']);
    }

    //  ACCESSORS

    /**
     * Obtener nombre completo: Tela + Color
     */
    public function getDisplayNameAttribute()
    {
        $tela = $this->tela_nombre ?? ($this->tela?->nombre ?? 'Sin tela');
        $color = $this->color_nombre ?? ($this->color?->nombre ?? 'Sin color');
        return "{$tela} - {$color}";
    }
};
