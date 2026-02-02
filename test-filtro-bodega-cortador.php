#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\User;
use App\Models\Role;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;
use Illuminate\Support\Facades\Auth;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PRUEBA: FILTRO DE PRENDAS DE BODEGA PARA ROL CORTADOR        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Obtener un pedido con prendas
$pedido = PedidoProduccion::with('prendas')->first();

if (!$pedido) {
    echo "❌ No hay pedidos en la base de datos\n\n";
    exit(1);
}

echo "📋 Pedido seleccionado:\n";
echo "   - Número: {$pedido->numero_pedido}\n";
echo "   - ID: {$pedido->id}\n";
echo "   - Total prendas: {$pedido->prendas->count()}\n\n";

// Ver prendas del pedido
echo "🔍 Prendas del pedido:\n";
$prendasConBodega = [];
$prendasSinBodega = [];

foreach ($pedido->prendas as $prenda) {
    $deBodega = $prenda->de_bodega ? '✅ SÍ' : '❌ NO';
    echo "   - {$prenda->nombre_prenda} (ID: {$prenda->id}) de_bodega={$deBodega}\n";
    
    if ($prenda->de_bodega) {
        $prendasConBodega[] = $prenda;
    } else {
        $prendasSinBodega[] = $prenda;
    }
}

echo "\n📊 Resumen:\n";
echo "   - Con de_bodega=TRUE: " . count($prendasConBodega) . "\n";
echo "   - Con de_bodega=FALSE: " . count($prendasSinBodega) . "\n\n";

if (count($prendasConBodega) === 0) {
    echo "⚠️  No hay prendas con de_bodega=TRUE. Actualizando una...\n";
    $prenda = $pedido->prendas()->first();
    if ($prenda) {
        $prenda->update(['de_bodega' => true]);
        $prendasConBodega[] = $prenda;
        $prendasSinBodega = $pedido->prendas()->where('de_bodega', false)->get()->toArray();
        echo "✅ Prenda actualizada a de_bodega=TRUE\n\n";
    }
}

// TEST 1: Sin autenticación
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 1: Sin autenticación (usuario = NULL)\n";
echo "═══════════════════════════════════════════════════════════════\n";

Auth::logout();

$useCase = app()->make(ObtenerPrendasPedidoUseCase::class);
$resultado = $useCase->ejecutar(new ObtenerPrendasPedidoDTO($pedido->id));

echo "✓ Resultado: " . count($resultado) . " prendas\n";
echo "✓ Esperado: " . $pedido->prendas->count() . " prendas\n";

if (count($resultado) === $pedido->prendas->count()) {
    echo "✅ CORRECTO: Sin autenticación se ven TODAS las prendas\n\n";
} else {
    echo "❌ ERROR\n\n";
}

// TEST 2: Buscar cortador en la BD
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 2: Obtener usuario con rol CORTADOR\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Obtener rol cortador
$rolCortador = Role::where('name', 'cortador')->first();

if (!$rolCortador) {
    echo "❌ No existe rol CORTADOR\n\n";
    exit(1);
}

// Buscar usuario con ese rol
$cortador = User::whereJsonContains('roles_ids', $rolCortador->id)->first();

if ($cortador) {
    echo "✓ Usuario encontrado: {$cortador->name} (ID: {$cortador->id})\n";
    echo "✓ Roles: " . implode(", ", $cortador->roles->pluck('name')->toArray()) . "\n\n";
    
    // Autenticar
    Auth::login($cortador);
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "TEST 3: Con rol CORTADOR\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    $useCase2 = app()->make(ObtenerPrendasPedidoUseCase::class);
    $resultado2 = $useCase2->ejecutar(new ObtenerPrendasPedidoDTO($pedido->id));
    
    $prendasEsperadas = count($prendasSinBodega);
    
    echo "✓ Resultado: " . count($resultado2) . " prendas\n";
    echo "✓ Esperado: {$prendasEsperadas} prendas (solo sin de_bodega)\n";
    
    if (count($resultado2) === $prendasEsperadas) {
        echo "✅ CORRECTO: CORTADOR ve solo prendas sin de_bodega=TRUE\n\n";
    } else {
        echo "❌ ERROR: CORTADOR debería ver {$prendasEsperadas} prendas\n\n";
    }
    
    // Verificar detalle
    if (count($resultado2) > 0 && count($prendasConBodega) > 0) {
        echo "🔎 Verificación de contenido:\n";
        $idsResultado = collect($resultado2)->pluck('id')->toArray();
        $idsBodega = collect($prendasConBodega)->pluck('id')->toArray();
        
        foreach ($idsBodega as $id) {
            if (in_array($id, $idsResultado)) {
                echo "   ❌ Prenda de bodega (ID: $id) ESTÁ en resultados (NO debería)\n";
            } else {
                echo "   ✅ Prenda de bodega (ID: $id) NO está en resultados (correcto)\n";
            }
        }
        echo "\n";
    }
    
} else {
    echo "⚠️  No hay usuarios con rol CORTADOR\n";
    echo "   Usuarios disponibles:\n";
    
    $usuarios = User::limit(10)->get();
    foreach ($usuarios as $u) {
        $roles = $u->roles->pluck('name')->implode(', ');
        echo "   - {$u->name} ({$roles})\n";
    }
    echo "\n";
    exit(1);
}

// TEST 4: Con otro rol (no cortador)
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 4: Con rol NO CORTADOR\n";
echo "═══════════════════════════════════════════════════════════════\n";

$otroRol = Role::where('name', '!=', 'cortador')->first();

if ($otroRol) {
    $otroUsuario = User::whereJsonContains('roles_ids', $otroRol->id)->first();
    
    if ($otroUsuario) {
        Auth::login($otroUsuario);
        echo "✓ Usuario autenticado: {$otroUsuario->name} (Rol: {$otroRol->name})\n\n";
        
        $useCase3 = app()->make(ObtenerPrendasPedidoUseCase::class);
        $resultado3 = $useCase3->ejecutar(new ObtenerPrendasPedidoDTO($pedido->id));
        
        $totalPrendas = $pedido->prendas->count();
        echo "✓ Resultado: " . count($resultado3) . " prendas\n";
        echo "✓ Esperado: {$totalPrendas} prendas (todas, incluyendo de bodega)\n";
        
        if (count($resultado3) === $totalPrendas) {
            echo "✅ CORRECTO: {$otroRol->name} ve TODAS las prendas\n\n";
        } else {
            echo "❌ ERROR: {$otroRol->name} debería ver todas las prendas\n\n";
        }
    }
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ PRUEBAS COMPLETADAS                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
