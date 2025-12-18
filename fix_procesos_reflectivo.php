<?php

/**
 * Script para eliminar la llamada duplicada a crearProcesosParaReflectivo
 * en PedidosProduccionController.php
 */

$file = __DIR__ . '/app/Http/Controllers/Asesores/PedidosProduccionController.php';

if (!file_exists($file)) {
    die("Error: Archivo no encontrado\n");
}

$content = file_get_contents($file);

// Buscar y reemplazar la llamada duplicada
$search = "            // ✅ CREAR PROCESOS AUTOMÁTICAMENTE PARA COTIZACIONES REFLECTIVO
            \\Log::info('📞 Llamando a crearProcesosParaReflectivo', [
                'pedido_id' => \$pedido->id,
                'numero_pedido' => \$pedido->numero_pedido,
                'cotizacion_id' => \$cotizacion->id,
                'tipo_cotizacion' => \$cotizacion->tipoCotizacion?->nombre,
            ]);
            \$this->crearProcesosParaReflectivo(\$pedido, \$cotizacion);";

$replace = "            // Nota: Los procesos para pedidos reflectivos se crean automáticamente
            // mediante el Listener CrearProcesosParaCotizacionReflectivo
            // que se dispara con el evento PedidoCreado";

$newContent = str_replace($search, $replace, $content);

if ($content === $newContent) {
    echo "⚠️ No se encontró el texto a reemplazar\n";
    echo "Buscando variaciones...\n";
    
    // Intentar con diferentes variaciones de espacios/caracteres
    $search2 = "// ✅ CREAR PROCESOS AUTOMÁTICAMENTE PARA COTIZACIONES REFLECTIVO";
    if (strpos($content, $search2) !== false) {
        echo "✅ Encontrado el comentario\n";
    } else {
        echo "❌ No encontrado\n";
    }
} else {
    file_put_contents($file, $newContent);
    echo "✅ Archivo actualizado exitosamente\n";
}

// También eliminar el método completo crearProcesosParaReflectivo
$methodStart = "    /**
     * Crear procesos automáticamente para cotizaciones REFLECTIVO";
$methodEnd = "        }
    }";

// Buscar el método completo
if (strpos($content, $methodStart) !== false) {
    echo "✅ Método crearProcesosParaReflectivo encontrado - debe ser eliminado manualmente\n";
    echo "El Listener CrearProcesosParaCotizacionReflectivo ya maneja esta funcionalidad\n";
}
