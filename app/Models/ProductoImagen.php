<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\ProductoPedido;

class ProductoImagen extends Model
{
    use HasFactory;

    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_pedido_id',
        'tipo',
        'imagen',
        'titulo',
        'descripcion',
        'orden',
    ];

    public function productoPedido()
    {
        return $this->belongsTo(ProductoPedido::class, 'producto_pedido_id');
    }
}
