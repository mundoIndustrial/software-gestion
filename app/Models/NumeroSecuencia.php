<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumeroSecuencia extends Model
{
    protected $table = 'numero_secuencias';

    protected $fillable = [
        'tipo',
        'siguiente',
    ];
}
