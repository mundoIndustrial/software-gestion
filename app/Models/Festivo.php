<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Festivo extends Model
{
    protected $table = 'festivos';
    protected $fillable = ['fecha', 'descripcion'];
}