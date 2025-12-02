<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'requires_credentials'];

    /**
     * Obtener todos los usuarios que tienen este rol
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function users()
    {
        return User::whereJsonContains('roles_ids', $this->id)->get();
    }

    /**
     * Contar usuarios que tienen este rol
     *
     * @return int
     */
    public function countUsers()
    {
        return User::whereJsonContains('roles_ids', $this->id)->count();
    }
}
