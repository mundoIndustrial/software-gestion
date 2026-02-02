#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
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

// Obtener directamente de DB sin relación
$cortador = \DB::table('users')
    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
    ->where('roles.name', 'cortador')
    ->select('users.*')
    ->first();

if ($cortador) {
    $usuario = \App\Models\User::find($cortador->id);
    echo "✓ Usuario encontrado: {$usuario->name} (ID: {$usuario->id})\n\n";
    
    // Autenticar
    Auth::login($usuario);
    
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
    echo "❌ No hay usuarios con rol CORTADOR\n";
    echo "   Usuarios y roles disponibles:\n";
    
    $usuarios = \DB::table('users')
        ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
        ->join('roles', 'roles.id', '=', 'user_roles.role_id')
        ->select('users.name', 'roles.name as role')
        ->get();
    
    foreach ($usuarios as $u) {
        echo "   - {$u->name} ({$u->role})\n";
    }
    echo "\n";
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FIN DE PRUEBAS                                               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
