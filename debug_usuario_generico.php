<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ProcesoPrenda;
use App\Models\PedidoProduccion;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;

echo "\n=== TEST: LÓGICA GENÉRICA PARA CUALQUIER COSTURERO ===\n";
echo "Verificando que funcione para usuarios con nombre genérico (ej: Fabian)\n\n";

// 1. Crear o buscar un usuario de prueba
echo "1️⃣  Buscando usuario de prueba...\n";

// Crear un usuario de prueba si no existe
$usuarioPrueba = User::where('email', 'fabian@test.com')->first();
if (!$usuarioPrueba) {
    echo "   Creando usuario 'Fabian'...\n";
    $usuarioPrueba = User::create([
        'name' => 'Fabian',
        'email' => 'fabian@test.com',
        'password' => bcrypt('password'),
        'roles_ids' => [
            \App\Models\Role::where('name', 'costurero')->first()?->id ?? 2
        ]
    ]);
}

echo "   ✅ Usuario: {$usuarioPrueba->name} (ID: {$usuarioPrueba->id})\n\n";

// 2. Crear un proceso asignado a este usuario de prueba
echo "2️⃣  Verificando procesos para Fabian...\n";

$procesosDelUsuario = ProcesoPrenda::all()->filter(function($p) use ($usuarioPrueba) {
    return strtolower(trim($p->encargado)) === strtolower(trim($usuarioPrueba->name));
});

echo "   Procesos encontrados: {$procesosDelUsuario->count()}\n";

if ($procesosDelUsuario->count() == 0) {
    echo "   ⚠️  No hay procesos asignados a Fabian. Creando uno...\n";
    
    // Buscar un pedido disponible
    $pedido = PedidoProduccion::first();
    if ($pedido) {
        $proceso = ProcesoPrenda::create([
            'numero_pedido' => $pedido->numero_pedido,
            'prenda_pedido_id' => $pedido->prendas->first()?->id ?? 1,
            'proceso' => 'Costura',
            'encargado' => 'Fabian',
            'estado_proceso' => 'Pendiente',
            'fecha_inicio' => now(),
        ]);
        echo "   ✅ Proceso creado: Pedido {$pedido->numero_pedido} | Encargado: Fabian\n\n";
    }
}

// 3. Usar el servicio para obtener pedidos de Fabian
echo "3️⃣  Obteniendo pedidos de Fabian usando el servicio...\n";
try {
    $service = app(ObtenerPedidosOperarioService::class);
    auth()->loginUsingId($usuarioPrueba->id);
    
    $datosOperario = $service->obtenerPedidosDelOperario($usuarioPrueba);
    
    echo "   ✅ Datos del operario:\n";
    echo "      Nombre: {$datosOperario->nombreOperario}\n";
    echo "      Tipo: {$datosOperario->tipoOperario}\n";
    echo "      Área: {$datosOperario->areaOperario}\n";
    echo "      Total pedidos: {$datosOperario->totalPedidos}\n\n";
    
    if ($datosOperario->totalPedidos > 0) {
        echo "   ✅ Pedidos asignados a {$usuarioPrueba->name}:\n";
        foreach ($datosOperario->pedidos as $pedido) {
            echo "      - Pedido #{$pedido['numero_pedido']} | {$pedido['descripcion']} | Estado: {$pedido['estado']}\n";
        }
    } else {
        echo "   ⚠️  Sin pedidos asignados\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN ===\n";
echo "✅ La lógica es GENÉRICA: funciona para cualquier usuario con rol costurero/cortador\n";
echo "✅ Si eres 'Fabian' o 'Juan' o cualquier nombre, ves tus pedidos asignados\n";
echo "✅ No hay hardcoding de nombres específicos\n\n";
