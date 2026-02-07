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
        'genero',
        'origen',
        'tipo_cotizacion',
        'imagen',
        'referencia',
        'tipo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con Telas (belongsToMany)
     * Una prenda puede tener múltiples telas
     */
    public function telas()
    {
        return $this->belongsToMany(
            Tela::class,
            'prenda_tela',
            'prenda_id',
            'tela_id'
        );
    }

    /**
     * Relación con Procesos (belongsToMany)
     * Una prenda puede tener múltiples procesos
     */
    public function procesos()
    {
        return $this->belongsToMany(
            Proceso::class,
            'prenda_proceso',
            'prenda_id',
            'proceso_id'
        );
    }

    /**
     * Relación con Variaciones (belongsToMany)
     * Una prenda puede tener múltiples variaciones
     */
    public function variaciones()
    {
        return $this->belongsToMany(
            Variacion::class,
            'prenda_variacion',
            'prenda_id',
            'variacion_id'
        );
    }

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
