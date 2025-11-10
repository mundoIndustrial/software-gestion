<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoPedido extends Model
{
    use HasFactory;

    protected $table = 'productos_pedido';

    protected $fillable = [
        'pedido',
        'nombre_producto',
        'tela',
        'tipo_manga',
        'color',
        'descripcion',
        'bordados',
        'modelo_foto',
        'talla',
        'genero',
        'ref_hilo',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'imagen',
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación con el pedido (tabla_original)
     */
    public function pedidoOriginal()
    {
        return $this->belongsTo(TablaOriginal::class, 'pedido', 'pedido');
    }

    /**
     * Calcular subtotal automáticamente
     */
    public function calcularSubtotal()
    {
        if ($this->precio_unitario && $this->cantidad) {
            $this->subtotal = $this->precio_unitario * $this->cantidad;
        }
        return $this->subtotal;
    }

    // Relación con imágenes
    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_pedido_id');
    }
}
