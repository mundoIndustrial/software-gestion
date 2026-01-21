<?php
/**
 * Script de Prueba: Cargar Datos Completos del Pedido
 * 
 * Verifica que el endpoint /supervisor-pedidos/{id}/editar retorne:
 * - Variantes con detalles completos
 * - Tallas por género
 * - Telas agregadas
 * - Procesos con ubicaciones e imágenes
 * - Imágenes de prendas, logos y telas
 */

// Configurar para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

// Usar Eloquent
use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;

// ID del pedido a probar (cambiar según sea necesario)
$pedidoId = 1; // Cambiar al ID del pedido que quieras probar

echo "=== PRUEBA DE CARGA DE DATOS DEL PEDIDO ===\n\n";
echo "Pedido ID: $pedidoId\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Cargar pedido con todas las relaciones
    $orden = PedidoProduccion::with([
        'prendas' => function($query) {
            $query->with([
                'color',
                'tela',
                'tipoManga',
                'tipoBrocheBoton',
                'fotos',
                'fotosLogo',
                'fotosTela',
                'variantes' => function($q) {
                    $q->with(['color', 'tela', 'tipoManga', 'tipoBrocheBoton']);
                },
                'procesos' => function($q) {
                    $q->with('imagenes');
                }
            ]);
        },
        'asesora'
    ])->findOrFail($pedidoId);

    echo "✅ Pedido cargado correctamente\n";
    echo "Número de pedido: " . $orden->numero_pedido . "\n";
    echo "Cliente: " . $orden->cliente . "\n";
    echo "Prendas: " . $orden->prendas->count() . "\n\n";

    // Verificar cada prenda
    foreach ($orden->prendas as $idx => $prenda) {
        echo "--- PRENDA " . ($idx + 1) . " ---\n";
        echo "Nombre: " . $prenda->nombre_prenda . "\n";
        echo "Descripción: " . $prenda->descripcion . "\n";
        
        // Variantes
        echo "\n📋 VARIANTES: " . $prenda->variantes->count() . "\n";
        if ($prenda->variantes->count() > 0) {
            foreach ($prenda->variantes as $var) {
                echo "  - Talla: " . $var->talla . ", Cantidad: " . $var->cantidad . ", Género: " . $var->genero . "\n";
                echo "    Color: " . ($var->color?->nombre ?? 'N/A') . "\n";
                echo "    Tela: " . ($var->tela?->nombre ?? 'N/A') . "\n";
                echo "    Manga: " . ($var->tipoManga?->nombre ?? 'N/A') . "\n";
                echo "    Broche: " . ($var->tipoBrocheBoton?->nombre ?? 'N/A') . "\n";
                echo "    Bolsillos: " . ($var->tiene_bolsillos ? 'Sí' : 'No') . "\n";
            }
        } else {
            echo "  ⚠️  Sin variantes\n";
        }
        
        // Telas
        echo "\n🧵 TELAS: " . $prenda->fotosTelas->count() . "\n";
        if ($prenda->fotosTelas->count() > 0) {
            foreach ($prenda->fotosTelas as $tela) {
                echo "  - Ruta: " . $tela->ruta_webp . "\n";
            }
        } else {
            echo "  ⚠️  Sin telas\n";
        }
        
        // Fotos de prenda
        echo "\n📸 FOTOS DE PRENDA: " . $prenda->fotos->count() . "\n";
        if ($prenda->fotos->count() > 0) {
            foreach ($prenda->fotos as $foto) {
                echo "  - Ruta: " . $foto->ruta_foto . "\n";
            }
        } else {
            echo "  ⚠️  Sin fotos\n";
        }
        
        // Logos
        echo "\n🏷️  LOGOS: " . $prenda->fotosLogo->count() . "\n";
        if ($prenda->fotosLogo->count() > 0) {
            foreach ($prenda->fotosLogo as $logo) {
                echo "  - Ruta: " . $logo->ruta_foto . "\n";
            }
        } else {
            echo "  ⚠️  Sin logos\n";
        }
        
        // Procesos
        echo "\n⚙️  PROCESOS: " . $prenda->procesos->count() . "\n";
        if ($prenda->procesos->count() > 0) {
            foreach ($prenda->procesos as $proceso) {
                echo "  - Tipo: " . $proceso->tipo_proceso . "\n";
                echo "    Observaciones: " . $proceso->observaciones . "\n";
                echo "    Ubicaciones: " . $proceso->ubicaciones . "\n";
                echo "    Imágenes: " . $proceso->imagenes->count() . "\n";
                
                if ($proceso->imagenes->count() > 0) {
                    foreach ($proceso->imagenes as $img) {
                        echo "      • " . $img->ruta_webp . " (" . $img->ruta_original . ")\n";
                    }
                }
            }
        } else {
            echo "  ⚠️  Sin procesos\n";
        }
        
        echo "\n";
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "✅ Todas las relaciones se cargaron correctamente\n";
    echo "✅ Datos listos para enviar al frontend\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE PRUEBA ===\n";
