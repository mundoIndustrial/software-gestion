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
        'configuracion_telas',
        'descripcion',
        'bordados',
        'estampados',
        'personalizacion_combinada',
        'modelo_foto',
        'talla',
        'genero',
        'ref_hilo',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'imagen',
        'notas',
        // Campos de configuración modular
        'categoria_prenda',
        'tipo_prenda',
        'configuracion_cuello',
        'configuracion_bolsillos',
        'configuracion_puños',
        'configuracion_cierre',
        'configuracion_reflectivos',
        'configuracion_bordados',
        'caracteristicas_especiales',
        'tallas_cantidades',
        'ciclos',
        'origen',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'configuracion_telas' => 'array',
        'configuracion_reflectivos' => 'array',
        'configuracion_bordados' => 'array',
        'caracteristicas_especiales' => 'array',
        'tallas_cantidades' => 'array',
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
