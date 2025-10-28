<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'requires_credentials'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
