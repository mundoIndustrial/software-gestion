<?php
/**
 * 📥 Script para Capturar Requests Raw
 * 
 * Propósito: Agregar logging detallado temporalmente en el controlador
 * para ver exactamente qué JSON está siendo recibido desde el frontend
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║    📥 CAPTURA DE REQUESTS - INSTRUCCIONES DE SETUP         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Este script te ayuda a capturar exactamente qué datos recibe el backend.\n\n";

echo "PASO 1: Modificar el Controlador\n";
echo "────────────────────────────────────────────────────────────\n\n";

$controllerCode = <<<'CODE'
// Agregar esto al inicio de la función en:
// app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php

public function crearSinCotizacion(Request $request)
{
    // DEBUGGING: Capturar request raw
    \Log::info('📥 [DEBUG] REQUEST RECIBIDO - crearSinCotizacion', [
        'headers' => $request->headers->all(),
        'all_data' => $request->all(),
        'json_data' => $request->json()->all(),
        'prendas_count' => count($request->input('prendas', [])),
        'prendas_raw' => $request->input('prendas', []),
        'cliente' => $request->input('cliente'),
        'numero_pedido' => $request->input('numero_pedido'),
    ]);
    
    // ... resto del código ...
}
CODE;

echo $controllerCode . "\n\n";

echo "PASO 2: Modificar el Servicio\n";
echo "────────────────────────────────────────────────────────────\n\n";

$serviceCode = <<<'CODE'
// En: app/Application/Services/PedidoPrendaService.php
// Método: guardarPrendasEnPedido()

public function guardarPrendasEnPedido(PedidoProduccion $pedido, array $prendas): void
{
    // DEBUGGING MEJORADO
    \Log::info('📦 [DEBUG] GUARDANDO PRENDAS - Análisis detallado', [
        'pedido_id' => $pedido->id,
        'numero_pedido' => $pedido->numero_pedido,
        'cantidad_prendas' => count($prendas),
        
        // Mostrar CADA prenda en detalle
        'prendas_detalle' => array_map(function($p, $i) {
            return [
                'index' => $i,
                'prenda_data' => $p,
                'tipo' => gettype($p),
                'variantes_count' => isset($p['variantes']) ? count($p['variantes']) : 0,
                'variantes' => isset($p['variantes']) ? $p['variantes'] : [],
            ];
        }, $prendas, array_keys($prendas)),
    ]);
    
    // ... resto del código ...
}
CODE;

echo $serviceCode . "\n\n";

echo "PASO 3: Crear un Pedido de Prueba\n";
echo "────────────────────────────────────────────────────────────\n\n";

echo "1. Accede a la interfaz de creación de pedidos\n";
echo "2. Crea un pedido con una o dos prendas\n";
echo "3. Completa TODOS los campos\n";
echo "4. Envía el formulario\n\n";

echo "PASO 4: Ver los Logs\n";
echo "────────────────────────────────────────────────────────────\n\n";

echo "Ejecuta:\n";
echo "  tail -100 storage/logs/laravel.log | grep -A 50 'DEBUG'\n\n";

echo "O más específicamente:\n";
echo "  grep -A 100 'GUARDANDO PRENDAS' storage/logs/laravel.log | tail -200\n\n";

echo "PASO 5: Analizar la Salida\n";
echo "────────────────────────────────────────────────────────────\n\n";

echo "Busca en los logs:\n";
echo "✓ ¿Se ve 'REQUEST RECIBIDO'?\n";
echo "✓ ¿Cuántas prendas muestra?\n";
echo "✓ ¿Qué estructura tiene el JSON?\n";
echo "✓ ¿Están todos los campos (talla, cantidad, color_id, etc)?\n\n";

echo "PASO 6: Comparar con Esperado\n";
echo "────────────────────────────────────────────────────────────\n\n";

$expectedFormat = <<<'JSON'
Formato esperado en los logs:

[
  {
    "nombre_prenda": "Chaleco",
    "descripcion": "Chaleco azul",
    "genero": "U",
    "variantes": [
      {
        "talla": "M",
        "cantidad": 50,
        "color_id": 1,
        "tela_id": 2,
        "tipo_manga_id": 1,
        "tipo_broche_boton_id": 1,
        "manga_obs": "",
        "broche_boton_obs": "",
        "tiene_bolsillos": true,
        "bolsillos_obs": ""
      }
    ]
  }
]
JSON;

echo $expectedFormat . "\n\n";

echo "PASO 7: Scripts Automáticos\n";
echo "────────────────────────────────────────────────────────────\n\n";

echo "Después de crear el pedido, ejecuta:\n\n";

echo "1. Ver análisis completo:\n";
echo "   php debug_flujo_prendas.php [numero_pedido]\n\n";

echo "2. Ver validación de integridad:\n";
echo "   php validar_integridad_prendas.php [numero_pedido]\n\n";

echo "3. Ver análisis de datos:\n";
echo "   php analizar_datos_prendas.php [numero_pedido]\n\n";

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║        🔧 CHECKLIST DE CAMPOS A VALIDAR                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$checklist = [
    "Frontend envía 'nombre_prenda'",
    "Frontend envía 'descripcion'",
    "Frontend envía 'genero'",
    "Frontend envía array 'variantes'",
    "Cada variante tiene 'talla' (no vacía)",
    "Cada variante tiene 'cantidad' (> 0)",
    "Cada variante tiene 'color_id' (> 0)",
    "Cada variante tiene 'tela_id' (> 0)",
    "Cada variante tiene 'tipo_manga_id' (> 0)",
    "Cada variante tiene 'tipo_broche_boton_id' (> 0)",
    "Backend recibe los datos correctamente",
    "Backend guarda en base de datos",
    "Los datos se guardan sin ser modificados",
];

foreach ($checklist as $idx => $item) {
    echo ($idx + 1) . ". ☐ $item\n";
}

echo "\n\n✅ Instrucciones completadas\n\n";
?>
