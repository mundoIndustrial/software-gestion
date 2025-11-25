<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoPedido extends Model
{
    use HasFactory;

    protected $table = 'productos_pedido';

    protected $fillable = [
        'pedido',
        'orden_asesor_id',
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
        'estampados',
        'personalizacion_combinada',
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
        'configuracion_telas',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación con el pedido (tabla_original)
     * NOTA: Este modelo sigue usando tabla_original. Si quieres actualizarlo a PedidoProduccion,
     * asegúrate de actualizar la migración y las referencias en controladores.
     */
    public function pedidoOriginal()
    {
        return $this->belongsTo(TablaOriginal::class, 'pedido', 'pedido');
    }

    /**
     * Relación alternativa con pedidos_produccion
     * Usa esta si necesitas vincular con el nuevo sistema
     */
    public function pedidoProduccion()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido', 'numero_pedido');
    }

    /**
     * Relación con la orden del asesor (para borradores)
     */
    public function ordenAsesor()
    {
        return $this->belongsTo(OrdenAsesor::class, 'orden_asesor_id');
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
