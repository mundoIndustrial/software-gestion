<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistroDeHuella extends Model
{
    use HasFactory;

    protected $table = 'registro_de_huella';

    protected $fillable = [
        'id_persona',
        'id_reporte',
        'hora'
    ];

    protected $casts = [
        'hora' => 'datetime:H:i:s',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'id_persona', 'id');
    }

    public function reporte()
    {
        return $this->belongsTo(ReportePersonal::class, 'id_reporte', 'id');
    }
}
