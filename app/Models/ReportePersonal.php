<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportePersonal extends Model
{
    use HasFactory;

    protected $table = 'reportes_personal';

    protected $fillable = [
        'numero_reporte',
        'nombre_reporte'
    ];

    public function registros()
    {
        return $this->hasMany(RegistroDeHuella::class, 'id_reporte', 'id');
    }
}
