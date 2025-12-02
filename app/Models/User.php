<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\Auditable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'avatar',
        'telefono',
        'bio',
        'ciudad',
        'departamento',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles_ids' => 'array', // Convierte automáticamente JSON a array
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Obtener todos los roles del usuario (múltiples roles)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function roles()
    {
        // Si tiene roles_ids, obtener esos roles
        if (!empty($this->roles_ids)) {
            return Role::whereIn('id', $this->roles_ids)->get();
        }

        // Si no, retornar el rol principal (si existe)
        if ($this->role_id) {
            return collect([$this->role]);
        }

        // Si no tiene ningún rol, retornar colección vacía
        return collect([]);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     *
     * @param string|int $role - Nombre o ID del rol
     * @return bool
     */
    public function hasRole($role): bool
    {
        // Si es un número, buscar por ID
        if (is_numeric($role)) {
            return in_array($role, $this->roles_ids ?? []) || $this->role_id == $role;
        }

        // Si es string, buscar por nombre
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     *
     * @param array $roles - Array de nombres o IDs de roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si el usuario tiene todos los roles especificados
     *
     * @param array $roles - Array de nombres o IDs de roles
     * @return bool
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Agregar un rol al usuario (sin eliminar los existentes)
     *
     * @param int $roleId - ID del rol a agregar
     * @return void
     */
    public function addRole(int $roleId): void
    {
        $rolesIds = $this->roles_ids ?? [];

        if (!in_array($roleId, $rolesIds)) {
            $rolesIds[] = $roleId;
            $this->update(['roles_ids' => $rolesIds]);
        }
    }

    /**
     * Eliminar un rol del usuario
     *
     * @param int $roleId - ID del rol a eliminar
     * @return void
     */
    public function removeRole(int $roleId): void
    {
        $rolesIds = $this->roles_ids ?? [];
        $rolesIds = array_filter($rolesIds, fn($id) => $id !== $roleId);
        $this->update(['roles_ids' => array_values($rolesIds)]);
    }

    /**
     * Establecer roles (reemplaza los existentes)
     *
     * @param array $roleIds - Array de IDs de roles
     * @return void
     */
    public function setRoles(array $roleIds): void
    {
        $this->update(['roles_ids' => $roleIds]);
    }

    /**
     * Sincronizar roles (agregar nuevos, eliminar los que no estén en la lista)
     *
     * @param array $roleIds - Array de IDs de roles
     * @return void
     */
    public function syncRoles(array $roleIds): void
    {
        $this->update(['roles_ids' => $roleIds]);
    }

    public function registrosPisoCorte()
    {
        return $this->hasMany(RegistroPisoCorte::class, 'operario_id');
    }

    /**
     * Get the number of minutes for the "remember me" session.
     *
     * @return int
     */
    public function getRememberTokenDuration(): int
    {
        return config('auth.remember_duration', 43200); // 30 días por defecto
    }
}
