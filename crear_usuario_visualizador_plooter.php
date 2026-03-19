<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo PHP_EOL;
echo "╔════════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║   CREADOR DE USUARIO: VISUALIZADOR DE PLOOTER                  ║" . PHP_EOL;
echo "╚════════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

try {
    // 1. Crear el rol (si no existe)
    echo "📋 PASO 1: Verificando rol 'visualizador_plooter'..." . PHP_EOL;
    $rol = \App\Models\Role::where('name', 'visualizador_plooter')->first();
    
    if ($rol) {
        echo "   ✅ Rol ya existe (ID: {$rol->id})" . PHP_EOL;
    } else {
        $rol = \App\Models\Role::create([
            'name' => 'visualizador_plooter',
            'description' => 'Visualizador de Plooter - Solo puede ver el registro de plooter (solo lectura)',
            'requires_credentials' => true,
        ]);
        echo "   ✅ Rol creado exitosamente (ID: {$rol->id})" . PHP_EOL;
    }
    
    echo PHP_EOL;
    echo "👤 PASO 2: Crear usuario" . PHP_EOL;
    echo "   Ingresa los datos del nuevo usuario:" . PHP_EOL;
    echo PHP_EOL;
    
    // Solicitar datos del usuario
    echo "   Nombre completo: ";
    $nombre = trim(fgets(STDIN));
    
    echo "   Email: ";
    $email = trim(fgets(STDIN));
    
    echo "   Nombre de usuario (login): ";
    $username = trim(fgets(STDIN));
    
    echo "   Contraseña (se ocultará): ";
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo PHP_EOL;
    
    echo "   Confirma contraseña (se ocultará): ";
    system('stty -echo');
    $passwordConfirm = trim(fgets(STDIN));
    system('stty echo');
    echo PHP_EOL;
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($username) || empty($password)) {
        echo PHP_EOL . "❌ Error: Todos los campos son requeridos" . PHP_EOL;
        exit(1);
    }
    
    if ($password !== $passwordConfirm) {
        echo PHP_EOL . "❌ Error: Las contraseñas no coinciden" . PHP_EOL;
        exit(1);
    }
    
    if (strlen($password) < 6) {
        echo PHP_EOL . "❌ Error: La contraseña debe tener al menos 6 caracteres" . PHP_EOL;
        exit(1);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo PHP_EOL . "❌ Error: Email inválido" . PHP_EOL;
        exit(1);
    }
    
    // Verificar si el usuario ya existe
    if (\App\Models\User::where('email', $email)->orWhere('username', $username)->exists()) {
        echo PHP_EOL . "❌ Error: El usuario o email ya existe" . PHP_EOL;
        exit(1);
    }
    
    echo PHP_EOL;
    echo "📝 Creando usuario..." . PHP_EOL;
    
    // Crear usuario
    $user = \App\Models\User::create([
        'nombre' => $nombre,
        'email' => $email,
        'username' => $username,
        'password' => bcrypt($password),
        'roles_ids' => [$rol->id], // Asignar el rol visualizador_plooter
        'secciones_permitidas' => 'plooter', // Solo acceso a plooter
        'estado' => 'activo',
    ]);
    
    echo "   ✅ Usuario creado exitosamente" . PHP_EOL;
    echo PHP_EOL;
    
    // Mostrar resumen
    echo "╔════════════════════════════════════════════════════════════════╗" . PHP_EOL;
    echo "║                    RESUMEN DE CREACIÓN                         ║" . PHP_EOL;
    echo "╚════════════════════════════════════════════════════════════════╝" . PHP_EOL;
    echo PHP_EOL;
    echo "   Usuario ID:        {$user->id}" . PHP_EOL;
    echo "   Nombre:            {$user->nombre}" . PHP_EOL;
    echo "   Email:             {$user->email}" . PHP_EOL;
    echo "   Username:          {$user->username}" . PHP_EOL;
    echo "   Rol:               {$rol->name}" . PHP_EOL;
    echo "   Descripción Rol:   {$rol->description}" . PHP_EOL;
    echo "   Estado:            {$user->estado}" . PHP_EOL;
    echo PHP_EOL;
    
    echo "✅ ¡Usuario creado exitosamente!" . PHP_EOL;
    echo "   El usuario puede ingresar con:" . PHP_EOL;
    echo "   • Email/Username: {$email} o {$username}" . PHP_EOL;
    echo "   • Contraseña: [La que ingresaste]" . PHP_EOL;
    echo PHP_EOL;
    
    echo "ℹ️  PERMISOS DEL USUARIO:" . PHP_EOL;
    echo "   ✓ Ver tabla de plooter (solo lectura)" . PHP_EOL;
    echo "   ✗ Registrar fechas de envío" . PHP_EOL;
    echo "   ✗ Registrar fechas de llegada" . PHP_EOL;
    echo "   ✗ Eliminar registros" . PHP_EOL;
    echo PHP_EOL;

} catch (Exception $e) {
    echo PHP_EOL . "❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
