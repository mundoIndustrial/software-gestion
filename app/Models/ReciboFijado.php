<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReciboFijado extends Model
{
    public $timestamps = false;

    protected $table = 'recibos_fijados';

    protected $fillable = [
        'id_recibo',
        'encargado_actual',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
