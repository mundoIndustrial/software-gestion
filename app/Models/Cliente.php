<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'nombre',
        'email',
        'telefono',
        'ciudad',
        'notas'
    ];

    // Relación con usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
