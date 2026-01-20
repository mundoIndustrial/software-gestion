<?php

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║        🧪 PRUEBA DE CREACIÓN DE PEDIDO - BÁSICA           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Cargar Laravel
    $app = require __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    echo "1️⃣  Verificando conexión a BD...\n";
    $conexion = DB::connection()->getPdo();
    echo "   ✅ Conexión exitosa\n\n";
    
    echo "2️⃣  Verificando tablas necesarias...\n";
    $tablas = [
        'clientes' => DB::table('clientes')->count(),
        'pedidos_produccion' => DB::table('pedidos_produccion')->count(),
        'prendas_pedido' => DB::table('prendas_pedido')->count(),
    ];
    
    foreach ($tablas as $tabla => $cantidad) {
        echo "   ✅ Tabla '{$tabla}': {$cantidad} registros\n";
    }
    
    echo "\n3️⃣  Creando datos de prueba...\n";
    
    // Crear cliente
    $clienteId = DB::table('clientes')->insertGetId([
        'nombre' => 'Cliente Test ' . time(),
        'estado' => 'activo',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "   ✅ Cliente creado (ID: {$clienteId})\n";
    
    // Crear pedido
    $numeroPedido = DB::table('numero_secuencias')
        ->where('tipo', 'pedido_produccion')
        ->value('siguiente') ?? 45709;
    
    $pedidoId = DB::table('pedidos_produccion')->insertGetId([
        'numero_pedido' => $numeroPedido,
        'cliente' => 'Cliente Test',
        'cliente_id' => $clienteId,
        'asesor_id' => 1,
        'forma_de_pago' => 'efectivo',
        'estado' => 'pendiente',
        'fecha_de_creacion_de_orden' => now(),
        'cantidad_total' => 50,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "   ✅ Pedido creado (ID: {$pedidoId}, Número: {$numeroPedido})\n";
    
    // Crear prenda
    $prendaId = DB::table('prendas_pedido')->insertGetId([
        'pedido_produccion_id' => $pedidoId,
        'nombre_producto' => 'Camiseta Test',
        'descripcion' => 'Camiseta de prueba',
        'de_bodega' => 1,
        'origen' => 'bodega',
        'cantidad_talla' => json_encode(['dama-S' => 10, 'dama-M' => 15]),
        'estado' => 'pendiente',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "   ✅ Prenda creada (ID: {$prendaId})\n\n";
    
    echo "4️⃣  Verificando datos guardados...\n";
    $pedido = DB::table('pedidos_produccion')->find($pedidoId);
    $prenda = DB::table('prendas_pedido')->find($prendaId);
    $cliente = DB::table('clientes')->find($clienteId);
    
    echo "   ✅ Pedido en BD:\n";
    echo "      • Número: {$pedido->numero_pedido}\n";
    echo "      • Cliente: {$pedido->cliente}\n";
    echo "      • Estado: {$pedido->estado}\n";
    echo "      • Cantidad Total: {$pedido->cantidad_total}\n";
    
    echo "\n   ✅ Prenda en BD:\n";
    echo "      • Nombre: {$prenda->nombre_producto}\n";
    echo "      • Cantidad Talla: {$prenda->cantidad_talla}\n";
    echo "      • Estado: {$prenda->estado}\n";
    
    echo "\n   ✅ Cliente en BD:\n";
    echo "      • Nombre: {$cliente->nombre}\n";
    echo "      • Estado: {$cliente->estado}\n\n";
    
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 RESUMEN:\n";
    echo "   • Pedido ID: {$pedidoId}\n";
    echo "   • Número Pedido: {$numeroPedido}\n";
    echo "   • Prenda ID: {$prendaId}\n";
    echo "   • Cliente ID: {$clienteId}\n";
    echo "   • Total Prendas en Pedido: " . DB::table('prendas_pedido')->where('pedido_produccion_id', $pedidoId)->count() . "\n\n";
    
    echo "✨ Todos los datos se guardaron correctamente en la base de datos\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR EN LA PRUEBA:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
    exit(1);
}
