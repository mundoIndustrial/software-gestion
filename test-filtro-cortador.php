#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\User;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;
use Illuminate\Support\Facades\Auth;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PRUEBA: FILTRO DE PRENDAS DE BODEGA PARA ROL CORTADOR        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Obtener un pedido con prendas
$pedido = PedidoProduccion::with('prendas')->first();

if (!$pedido) {
    echo "❌ No hay pedidos en la base de datos\n\n";
    exit(1);
}

echo "📋 Pedido seleccionado para pruebas:\n";
echo "   - Número: {$pedido->numero_pedido}\n";
echo "   - ID: {$pedido->id}\n";
echo "   - Total prendas: {$pedido->prendas->count()}\n\n";

// Ver prendas del pedido
echo "🔍 Prendas del pedido:\n";
$prendasConBodega = [];
$prendasSinBodega = [];

foreach ($pedido->prendas as $prenda) {
    $deBodega = $prenda->de_bodega ? '✅ SÍ (de_bodega=TRUE)' : '❌ NO (de_bodega=FALSE)';
    echo "   - {$prenda->nombre_prenda} (ID: {$prenda->id}) → {$deBodega}\n";
    
    if ($prenda->de_bodega) {
        $prendasConBodega[] = $prenda;
    } else {
        $prendasSinBodega[] = $prenda;
    }
}

echo "\n";
echo "📊 Resumen de prendas:\n";
echo "   - Prendas de bodega (de_bodega=TRUE): " . count($prendasConBodega) . "\n";
echo "   - Prendas normales (de_bodega=FALSE): " . count($prendasSinBodega) . "\n\n";

if (count($prendasConBodega) === 0) {
    echo "⚠️  No hay prendas con de_bodega=TRUE. Crear algunas para la prueba.\n";
    echo "   UPDATE prendas_pedido SET de_bodega = 1 LIMIT 1;\n\n";
}

// Test 1: Sin autenticación (debería ver todas)
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 1: Sin autenticación (usuario = NULL)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

Auth::logout();

$useCase1 = app()->make(ObtenerPrendasPedidoUseCase::class);
$resultado1 = $useCase1->ejecutar(
    new \App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO($pedido->id)
);

echo "Resultado: Se obtuvieron " . count($resultado1) . " prendas\n";
if (count($resultado1) === $pedido->prendas->count()) {
    echo "✅ CORRECTO: Se retornan TODAS las prendas (incluidas de bodega)\n\n";
} else {
    echo "❌ ERROR: Debería retornar todas las prendas\n\n";
}

// Test 2: Con rol CORTADOR
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 2: Con rol CORTADOR\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Buscar un usuario con rol cortador
$cortador = User::whereHas('roles', function($q) {
    $q->where('name', 'cortador');
})->first();

if (!$cortador) {
    echo "⚠️  No hay usuarios con rol CORTADOR. Creando uno...\n";
    $cortador = User::create([
        'name' => 'Test Cortador',
        'email' => 'test-cortador-' . time() . '@test.com',
        'password' => bcrypt('password'),
    ]);
    
    $rolCortador = \App\Models\Role::where('name', 'cortador')->first();
    if ($rolCortador) {
        $cortador->roles()->attach($rolCortador->id);
        echo "✅ Usuario CORTADOR creado: {$cortador->name} (ID: {$cortador->id})\n\n";
    } else {
        echo "❌ No existe rol CORTADOR en la BD\n\n";
        exit(1);
    }
}

// Autenticar como cortador
Auth::login($cortador);
echo "👤 Autenticado como: {$cortador->name} (Rol: cortador)\n\n";

$useCase2 = app()->make(ObtenerPrendasPedidoUseCase::class);
$resultado2 = $useCase2->ejecutar(
    new \App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO($pedido->id)
);

$prendasEsperadas = count($prendasSinBodega);
echo "Resultado: Se obtuvieron " . count($resultado2) . " prendas\n";
echo "Esperadas: {$prendasEsperadas} prendas (solo las que NO son de bodega)\n";

if (count($resultado2) === $prendasEsperadas) {
    echo "✅ CORRECTO: CORTADOR ve solo prendas de confección (sin prendas de bodega)\n\n";
} else {
    echo "❌ ERROR: CORTADOR debería ver " . $prendasEsperadas . " prendas, obtuvo " . count($resultado2) . "\n\n";
}

// Verificar que las prendas retornadas NO incluyan las de bodega
if (count($resultado2) > 0 && count($prendasConBodega) > 0) {
    $prendasRetornadas = collect($resultado2)->pluck('id')->toArray();
    $prendasBodegaIds = collect($prendasConBodega)->pluck('id')->toArray();
    
    echo "🔎 Verificación de prendas de bodega:\n";
    $tienePredasBodega = false;
    foreach ($prendasBodegaIds as $id) {
        if (in_array($id, $prendasRetornadas)) {
            echo "   ❌ Prenda de bodega (ID: $id) ENCONTRADA en resultados (NO debería estar)\n";
            $tienePredasBodega = true;
        }
    }
    
    if (!$tienePredasBodega && count($prendasBodegaIds) > 0) {
        echo "   ✅ Ninguna prenda de bodega en los resultados\n\n";
    } elseif (count($prendasBodegaIds) === 0) {
        echo "   ℹ️  No hay prendas de bodega para verificar\n\n";
    }
}

// Test 3: Usando ObtenerPedidoUseCase (para OperarioController)
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 3: ObtenerPedidoUseCase con CORTADOR\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$useCase3 = app()->make(ObtenerPedidoUseCase::class);
try {
    $resultado3 = $useCase3->ejecutar($pedido->id);
    
    $numPrendas = count($resultado3->prendas ?? []);
    echo "Resultado: Se obtuvieron {$numPrendas} prendas\n";
    echo "Esperadas: {$prendasEsperadas} prendas\n";
    
    if ($numPrendas === $prendasEsperadas) {
        echo "✅ CORRECTO: ObtenerPedidoUseCase filtra prendas de bodega para CORTADOR\n\n";
    } else {
        echo "❌ ERROR: Debería filtrar las prendas de bodega\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n\n";
}

// Test 4: Con otro rol (admin, asesor, etc)
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 4: Con rol ASESOR o ADMIN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$asesor = User::whereHas('roles', function($q) {
    $q->whereIn('name', ['asesor', 'admin']);
})->first();

if ($asesor) {
    Auth::login($asesor);
    echo "👤 Autenticado como: {$asesor->name} (Rol: {$asesor->roles->first()->name})\n\n";
    
    $useCase4 = app()->make(ObtenerPrendasPedidoUseCase::class);
    $resultado4 = $useCase4->ejecutar(
        new \App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO($pedido->id)
    );
    
    $totalPrendas = $pedido->prendas->count();
    echo "Resultado: Se obtuvieron " . count($resultado4) . " prendas\n";
    echo "Esperadas: {$totalPrendas} prendas (todas, incluidas de bodega)\n";
    
    if (count($resultado4) === $totalPrendas) {
        echo "✅ CORRECTO: {$asesor->roles->first()->name} ve TODAS las prendas\n\n";
    } else {
        echo "⚠️  ERROR: {$asesor->roles->first()->name} debería ver todas las prendas\n\n";
    }
} else {
    echo "⚠️  No hay usuarios ASESOR o ADMIN para la prueba\n\n";
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FIN DE PRUEBAS                                               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
