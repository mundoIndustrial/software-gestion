Route::get('/debug-supervisor-role', function() {
    $user = \App\Models\User::where('email', 'yus22@gmail.com')->first();
    
    if(!$user) {
        return response()->json(['error' => 'Usuario no encontrado']);
    }
    
    $roles = $user->roles()->pluck('name')->toArray();
    
    $response = [
        'usuario' => $user->email,
        'user_id' => $user->id,
        'roles_actuales' => $roles,
        'tiene_supervisor_pedidos' => in_array('supervisor_pedidos', $roles)
    ];
    
    // Si no tiene el rol, intentar asignarlo
    if(!in_array('supervisor_pedidos', $roles)) {
        $role = \App\Models\Role::where('name', 'supervisor_pedidos')->first();
        if($role) {
            $user->roles()->attach($role->id);
            $response['accion'] = 'Rol supervisor_pedidos asignado correctamente';
        } else {
            $response['error'] = 'El rol supervisor_pedidos no existe en la BD';
        }
    }
    
    return response()->json($response);
});
