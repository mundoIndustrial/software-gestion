<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionAprobacion extends Model
{
    use HasFactory;

    protected $table = 'cotizacion_aprobaciones';

    protected $fillable = [
        'cotizacion_id',
        'usuario_id',
        'fecha_aprobacion',
        'comentario',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
