<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LogoPedido;
use App\Models\LogoPedidoImagen;
use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "  LIMPIAR DATOS DE PRUEBA\n";
echo "========================================\n\n";

// Eliminar todas las imágenes de LOGO
echo "1️⃣  Eliminando imágenes de LOGO...\n";
$imagenes = LogoPedidoImagen::all();
foreach ($imagenes as $img) {
    $img->delete();
}
echo "   ✅ " . $imagenes->count() . " imágenes eliminadas\n\n";

// Eliminar todos los LOGO pedidos
echo "2️⃣  Eliminando LOGO pedidos...\n";
$logoPedidos = LogoPedido::all();
$count = $logoPedidos->count();
foreach ($logoPedidos as $logo) {
    $logo->delete();
}
echo "   ✅ " . $count . " LOGO pedidos eliminados\n\n";

// Limpiar directorios de imágenes
echo "3️⃣  Limpiando directorios de almacenamiento...\n";
$dir = storage_path('app/logo_pedidos');
if (is_dir($dir)) {
    $subdirs = glob($dir . '/*', GLOB_ONLYDIR);
    foreach ($subdirs as $subdir) {
        $files = glob($subdir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($subdir);
    }
    echo "   ✅ Directorio /logo_pedidos limpiado\n";
} else {
    echo "   ℹ️  Directorio /logo_pedidos no existe\n";
}

// Verificación final
echo "\n4️⃣  Verificación final...\n";
$logoPedidosFinales = LogoPedido::count();
$imagenasFinales = LogoPedidoImagen::count();

echo "   LOGO Pedidos en BD: " . $logoPedidosFinales . "\n";
echo "   Imágenes en BD: " . $imagenasFinales . "\n";

if ($logoPedidosFinales === 0 && $imagenasFinales === 0) {
    echo "\n   ✅ Base de datos limpia y lista para pruebas\n";
} else {
    echo "\n   ⚠️  Aún hay datos en las tablas\n";
}

echo "\n✅ Limpieza completada\n\n";
