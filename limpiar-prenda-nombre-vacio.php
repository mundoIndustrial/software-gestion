<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "===========================================\n";
echo "LIMPIEZA: prenda_nombre VACÍO\n";
echo "===========================================\n\n";

// 1. Buscar registros con prenda_nombre vacío en bodega_detalles_talla
echo "=== bodega_detalles_talla ===\n";
$registrosVacios = DB::table('bodega_detalles_talla')
    ->where(function($query) {
        $query->whereNull('prenda_nombre')
              ->orWhere('prenda_nombre', '');
    })
    ->get();

echo "Encontrados {$registrosVacios->count()} registros con prenda_nombre vacío\n\n";

if ($registrosVacios->count() > 0) {
    foreach ($registrosVacios as $registro) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "ID: {$registro->id}\n";
        echo "Pedido: {$registro->numero_pedido}\n";
        echo "Talla: {$registro->talla}\n";
        echo "Cantidad: {$registro->cantidad}\n";
        echo "prenda_nombre actual: '{$registro->prenda_nombre}'\n";
        
        // Intentar encontrar la prenda correcta
        $pedido = DB::table('pedidos_produccion')
            ->where('numero_pedido', $registro->numero_pedido)
            ->first();
        
        if (!$pedido) {
            echo "⚠️ No se encontró el pedido de producción\n";
            continue;
        }
        
        // Buscar prendas del pedido
        $prendas = DB::table('prendas_pedido')
            ->where('pedido_produccion_id', $pedido->id)
            ->whereNull('deleted_at')
            ->get();
        
        $nombreEncontrado = null;
        
        foreach ($prendas as $prenda) {
            // Buscar si esta prenda tiene la talla y cantidad que coinciden
            $tieneTalla = DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $prenda->id)
                ->where('talla', $registro->talla)
                ->first();
            
            if ($tieneTalla) {
                $nombreEncontrado = $prenda->nombre_prenda;
                echo "✅ Prenda encontrada: {$nombreEncontrado}\n";
                
                // Actualizar el registro
                DB::table('bodega_detalles_talla')
                    ->where('id', $registro->id)
                    ->update([
                        'prenda_nombre' => $nombreEncontrado,
                        'updated_at' => now()
                    ]);
                
                echo "   → Registro actualizado\n";
                break;
            }
        }
        
        if (!$nombreEncontrado) {
            echo "❌ No se pudo determinar el nombre de la prenda\n";
        }
        
        echo "\n";
    }
}

// 2. Limpiar costura_bodega_detalles
echo "\n=== costura_bodega_detalles ===\n";
$costuraVacios = DB::table('costura_bodega_detalles')
    ->where(function($query) {
        $query->whereNull('prenda_nombre')
              ->orWhere('prenda_nombre', '');
    })
    ->get();

echo "Encontrados {$costuraVacios->count()} registros con prenda_nombre vacío\n\n";

if ($costuraVacios->count() > 0) {
    foreach ($costuraVacios as $registro) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "ID: {$registro->id}\n";
        echo "Pedido: {$registro->numero_pedido}\n";
        echo "Talla: {$registro->talla}\n";
        echo "Cantidad: {$registro->cantidad}\n";
        
        // Buscar en bodega_detalles_talla si ya tiene el nombre
        $bodegaBase = DB::table('bodega_detalles_talla')
            ->where('numero_pedido', $registro->numero_pedido)
            ->where('talla', $registro->talla)
            ->where('cantidad', $registro->cantidad)
            ->whereNotNull('prenda_nombre')
            ->where('prenda_nombre', '!=', '')
            ->first();
        
        if ($bodegaBase) {
            echo "✅ Nombre encontrado en bodega_detalles_talla: {$bodegaBase->prenda_nombre}\n";
            
            DB::table('costura_bodega_detalles')
                ->where('id', $registro->id)
                ->update([
                    'prenda_nombre' => $bodegaBase->prenda_nombre,
                    'updated_at' => now()
                ]);
            
            echo "   → Registro actualizado\n";
        } else {
            echo "❌ No se pudo determinar el nombre\n";
        }
        
        echo "\n";
    }
}

// 3. Limpiar epp_bodega_detalles
echo "\n=== epp_bodega_detalles ===\n";
$eppVacios = DB::table('epp_bodega_detalles')
    ->where(function($query) {
        $query->whereNull('prenda_nombre')
              ->orWhere('prenda_nombre', '');
    })
    ->get();

echo "Encontrados {$eppVacios->count()} registros con prenda_nombre vacío\n\n";

if ($eppVacios->count() > 0) {
    foreach ($eppVacios as $registro) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "ID: {$registro->id}\n";
        echo "Pedido: {$registro->numero_pedido}\n";
        echo "Talla: {$registro->talla}\n";
        echo "Cantidad: {$registro->cantidad}\n";
        
        // Buscar en bodega_detalles_talla si ya tiene el nombre
        $bodegaBase = DB::table('bodega_detalles_talla')
            ->where('numero_pedido', $registro->numero_pedido)
            ->where('talla', $registro->talla)
            ->where('cantidad', $registro->cantidad)
            ->whereNotNull('prenda_nombre')
            ->where('prenda_nombre', '!=', '')
            ->first();
        
        if ($bodegaBase) {
            echo "✅ Nombre encontrado en bodega_detalles_talla: {$bodegaBase->prenda_nombre}\n";
            
            DB::table('epp_bodega_detalles')
                ->where('id', $registro->id)
                ->update([
                    'prenda_nombre' => $bodegaBase->prenda_nombre,
                    'updated_at' => now()
                ]);
            
            echo "   → Registro actualizado\n";
        } else {
            echo "❌ No se pudo determinar el nombre\n";
        }
        
        echo "\n";
    }
}

echo "\n=== RESUMEN ===\n";
echo "✅ Registros limpiados en bodega_detalles_talla: {$registrosVacios->count()}\n";
echo "✅ Registros limpiados en costura_bodega_detalles: {$costuraVacios->count()}\n";
echo "✅ Registros limpiados en epp_bodega_detalles: {$eppVacios->count()}\n";
echo "\n💡 De ahora en adelante, todos los registros nuevos se guardarán con prenda_nombre correcto\n";

echo "\n=== FIN ===\n";
