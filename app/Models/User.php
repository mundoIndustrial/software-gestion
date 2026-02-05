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
        'google_id',
        'role_id',
        'roles_ids',
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

    /**
     * Obtener el rol principal/actual (primer rol en roles_ids quando existe)
     * Si no tiene roles_ids, retorna el role_id legacy
     * 
     * @return \App\Models\Role|null
     */
    public function getCurrentRole()
    {
        // Primero intentar obtener de roles_ids (nuevo sistema)
        if (!empty($this->roles_ids) && is_array($this->roles_ids)) {
            return Role::find($this->roles_ids[0]);
        }
        
        // Si no tiene roles_ids pero tiene role_id (legacy), usar eso
        if ($this->role_id) {
            return Role::find($this->role_id);
        }
        
        return null;
    }

    /**
     * Obtener el rol principal (primer rol en roles_ids)
     * Retorna un atributo accesible, no una relación
     *
     * @return \App\Models\Role|null
     */
    public function getRoleAttribute()
    {
        // Si tiene roles_ids, retornar el primero (nuevo sistema)
        if (!empty($this->roles_ids) && is_array($this->roles_ids)) {
            return Role::find($this->roles_ids[0]);
        }

        // Si no, retornar el role_id legacy
        if ($this->role_id) {
            return Role::find($this->role_id);
        }
        
        return null;
    }

    /**
     * Obtener todos los roles del usuario
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRolesAttribute()
    {
        // Obtener roles desde roles_ids (JSON)
        if (!empty($this->roles_ids)) {
            return Role::whereIn('id', $this->roles_ids)->get();
        }

        // Si no tiene roles, retornar colección vacía
        return collect([]);
    }

    /**
     * Obtener relación roles() desde roles_ids
     * Retorna una relación que puede ser consultada como un método
     */
    public function roles()
    {
        // Retornar una query builder que simula la relación
        if (!empty($this->roles_ids)) {
            return Role::whereIn('id', $this->roles_ids);
        }
        return Role::whereIn('id', []); // Retorna query builder vacío
    }

    /**
     * Verificar si el usuario tiene un rol específico
     *
     * @param string|int $role - Nombre o ID del rol
     * @return bool
     */
    public function hasRole($role): bool
    {
        // Si es un número, buscar por ID en roles_ids
        if (is_numeric($role)) {
            return in_array($role, $this->roles_ids ?? []);
        }

        // Si es string, buscar por nombre en roles usando el accessor
        return $this->roles->pluck('name')->contains($role);
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
        $rolesIds = array_map('intval', $rolesIds); // Convertir a enteros

        if (!in_array($roleId, $rolesIds, true)) {
            $rolesIds[] = $roleId;
            $this->update(['roles_ids' => $rolesIds]);
            $this->refresh(); // Recargar el modelo
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

    /**
     * Obtener los nombres de los roles del usuario
     * Compatible con Spatie\Permission API
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoleNames()
    {
        return $this->roles->pluck('name');
    }

    /**
     * Relación con el rol principal del usuario
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function registrosPisoCorte()
    {
        return $this->hasMany(RegistroPisoCorte::class, 'operario_id');
    }

    /**
     * Obtener todas las notificaciones del usuario
     * (Relación con tabla notifications)
     */
    public function notificacionesLectura()
    {
        return $this->hasMany('Illuminate\Notifications\DatabaseNotification', 'notifiable_id')
                    ->where('notifiable_type', User::class);
    }

    /**
     * Obtener notificaciones no leídas
     */
    public function notificacionesNoLeidas()
    {
        return $this->notificacionesLectura()->whereNull('read_at');
    }

    /**
     * Obtener el número de notificaciones no leídas
     */
    public function countNotificacionesNoLeidas(): int
    {
        return $this->notificacionesNoLeidas()->count();
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

    /**
     * Obtener solo el nombre del usuario (para usar como asesor)
     *
     * @return string
     */
    public function getNombreAsesor(): string
    {
        return $this->name ?? 'N/A';
    }
}
