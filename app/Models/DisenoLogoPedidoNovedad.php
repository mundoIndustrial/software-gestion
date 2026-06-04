<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisenoLogoPedidoNovedad extends Model
{
    use HasFactory;

    protected $table = 'disenos_logo_pedido_novedades';

    protected $fillable = [
        'diseno_logo_pedido_id',
        'novedad',
        'usuario_id',
        'tipo_novedad',
    ];

    public function diseno(): BelongsTo
    {
        return $this->belongsTo(DisenoLogoPedido::class, 'diseno_logo_pedido_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
