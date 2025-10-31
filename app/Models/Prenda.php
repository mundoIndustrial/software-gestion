<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen',
        'referencia',
        'tipo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con balanceos
     */
    public function balanceos()
    {
        return $this->hasMany(Balanceo::class);
    }

    /**
     * Obtener el balanceo activo de la prenda
     */
    public function balanceoActivo()
    {
        return $this->hasOne(Balanceo::class)->where('activo', true)->latest();
    }
}
