<?php

// Read .env file
$envFile = __DIR__ . '/.env';
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $envVars[trim($key)] = trim($val);
    }
}

// Get database config from .env
$dbHost = $envVars['DB_HOST'] ?? 'localhost';
$dbPort = $envVars['DB_PORT'] ?? 3306;
$dbName = $envVars['DB_DATABASE'] ?? '';
$dbUser = $envVars['DB_USERNAME'] ?? '';
$dbPass = $envVars['DB_PASSWORD'] ?? '';

echo "=== VERIFICANDO USUARIO: yus26@gmail.com ===\n\n";

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Get user
    $stmt = $pdo->prepare("SELECT id, name, email, roles_ids FROM users WHERE email = ?");
    $stmt->execute(['yus26@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Usuario no encontrado\n";
        exit(1);
    }
    
    echo "✅ Usuario encontrado:\n";
    echo "   ID: " . $user['id'] . "\n";
    echo "   Nombre: " . $user['name'] . "\n";
    echo "   Email: " . $user['email'] . "\n";
    echo "   roles_ids (raw): " . $user['roles_ids'] . "\n";
    
    // Parse roles_ids
    $rolesIds = json_decode($user['roles_ids'] ?? '[]', true) ?: [];
    if (!is_array($rolesIds)) {
        $rolesIds = [];
    }
    echo "   roles_ids (parsed): " . json_encode($rolesIds) . "\n";
    
    // Get roles
    if (!empty($rolesIds)) {
        echo "\n✅ Roles asignados:\n";
        $placeholders = implode(',', array_fill(0, count($rolesIds), '?'));
        $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id IN ($placeholders)");
        $stmt->execute($rolesIds);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($roles as $role) {
            echo "   - ID: {$role['id']}, Nombre: {$role['name']}\n";
        }
    } else {
        echo "\n⚠️  Sin roles asignados\n";
    }
    
    // Check Costura-Bodega role
    echo "\n=== BÚSQUEDA DEL ROL COSTURA-BODEGA ===\n";
    $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE name = ?");
    $stmt->execute(['Costura-Bodega']);
    $cRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cRole) {
        echo "✅ Rol 'Costura-Bodega' existe:\n";
        echo "   ID: {$cRole['id']}\n";
        echo "   Nombre: {$cRole['name']}\n";
        echo "   ¿Usuario tiene este rol? " . (in_array($cRole['id'], $rolesIds) ? "SÍ ✅" : "NO ❌") . "\n";
    } else {
        echo "❌ Rol 'Costura-Bodega' NO existe en la BD\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
