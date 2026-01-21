<?php
/**
 * Test directo del endpoint /supervisor-pedidos/{id}/editar
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\PedidoProduccion;

// Buscar pedido por número
$pedido = PedidoProduccion::where('numero_pedido', 45767)->first();

if (!$pedido) {
    echo "Pedido no encontrado\n";
    exit;
}

echo "=== PRUEBA DIRECTA DEL CONTROLADOR ===\n\n";
echo "Pedido ID: " . $pedido->id . "\n";
echo "Número: " . $pedido->numero_pedido . "\n\n";

// Cargar con las mismas relaciones que el controlador
$pedido->load([
    'prendas' => function($query) {
        $query->with([
            'fotos',
            'coloresTelas' => function($q) {
                $q->with(['color', 'tela', 'fotos']);
            },
            'fotosTelas',
            'variantes',
            'procesos' => function($q) {
                $q->with(['imagenes']);
            }
        ]);
    }
]);

// Revisar la primera prenda
$prenda = $pedido->prendas->first();

if (!$prenda) {
    echo "Sin prendas\n";
    exit;
}

echo "PRENDA: " . $prenda->nombre_prenda . "\n";
echo "ID: " . $prenda->id . "\n\n";

// Revisar coloresTelas
echo "=== COLORES-TELAS ===\n";
echo "Cargados: " . $prenda->coloresTelas->count() . "\n";
foreach ($prenda->coloresTelas as $ct) {
    echo "  - ID: " . $ct->id . "\n";
    echo "    color_id: " . $ct->color_id . "\n";
    echo "    tela_id: " . $ct->tela_id . "\n";
    echo "    Color: " . ($ct->color?->nombre ?? 'NULL') . "\n";
    echo "    Tela: " . ($ct->tela?->nombre ?? 'NULL') . "\n";
}

echo "\n=== FOTOS DE TELA ===\n";
echo "Cargadas: " . $prenda->fotosTelas->count() . "\n";
foreach ($prenda->fotosTelas as $ft) {
    echo "  - ruta_webp: " . $ft->ruta_webp . "\n";
}

echo "\n=== FOTOS DE PRENDA ===\n";
echo "Cargadas: " . $prenda->fotos->count() . "\n";
foreach ($prenda->fotos as $f) {
    echo "  - ruta_webp: " . $f->ruta_webp . "\n";
    echo "  - ruta_original: " . $f->ruta_original . "\n";
}

echo "\n=== VARIANTES ===\n";
echo "Cargadas: " . $prenda->variantes->count() . "\n";
foreach ($prenda->variantes as $v) {
    echo "  - ID: " . $v->id . "\n";
    echo "    tipo_manga_id: " . $v->tipo_manga_id . "\n";
    echo "    tipo_broche_boton_id: " . $v->tipo_broche_boton_id . "\n";
    echo "    manga_obs: " . $v->manga_obs . "\n";
}

echo "\n=== PROCESOS ===\n";
echo "Cargados: " . $prenda->procesos->count() . "\n";
foreach ($prenda->procesos as $p) {
    echo "  - ID: " . $p->id . "\n";
    echo "    tipo_proceso_id: " . $p->tipo_proceso_id . "\n";
    echo "    observaciones: " . $p->observaciones . "\n";
    echo "    ubicaciones: " . $p->ubicaciones . "\n";
    echo "    Imágenes: " . $p->imagenes->count() . "\n";
    foreach ($p->imagenes as $img) {
        echo "      • ruta_webp: " . $img->ruta_webp . "\n";
    }
}

echo "\n=== FIN ===\n";
