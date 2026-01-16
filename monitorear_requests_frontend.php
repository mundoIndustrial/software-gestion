<?php
/**
 * 📤 Script para Monitorear Requests del Frontend
 * 
 * Propósito: Capturar y analizar qué datos se envían desde el frontend
 * Detecta si el problema está en el envío o en el backend
 * 
 * Uso: php monitorear_requests_frontend.php [minutos]
 * Ejemplo: php monitorear_requests_frontend.php 5
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$minutos = $argv[1] ?? 10;
$desde = now()->subMinutes($minutos);

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║    📤 MONITOR DE REQUESTS DEL FRONTEND (últimos $minutos min)     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Leer el archivo de log
    $logFile = storage_path('logs/laravel.log');
    
    if (!file_exists($logFile)) {
        echo "❌ Archivo de log no encontrado: $logFile\n\n";
        exit(1);
    }
    
    // Leer el log completo
    $logContent = file_get_contents($logFile);
    
    // Buscar requests de creación de prendas
    echo "🔍 BÚSQUEDA DE REQUESTS\n";
    echo "────────────────────────────────────────────────────────\n\n";
    
    // 1. Requests a crearPedido
    echo "1️⃣  REQUESTS A crearPedido:\n";
    if (strpos($logContent, 'crearPedido') !== false) {
        echo "✅ Encontrado - Buscando detalles...\n\n";
        
        // Extraer líneas relevantes
        $lines = explode("\n", $logContent);
        $relevantLines = array_filter($lines, function($line) {
            return strpos($line, 'crearPedido') !== false || 
                   strpos($line, 'guardarPrendasEnPedido') !== false ||
                   strpos($line, 'Procesando prenda') !== false;
        });
        
        foreach ($relevantLines as $line) {
            if (strlen($line) > 3) {
                echo trim($line) . "\n";
            }
        }
    } else {
        echo "❌ No encontrado en logs\n";
    }
    
    echo "\n\n";
    
    // 2. Analizar estructura de datos esperada
    echo "📋 ESTRUCTURA DE DATOS ESPERADA\n";
    echo "────────────────────────────────────────────────────────\n\n";
    
    echo "El frontend DEBE enviar en cada prenda:\n\n";
    echo "{\n";
    echo "  \"nombre_prenda\": \"string\",\n";
    echo "  \"descripcion\": \"string\",\n";
    echo "  \"genero\": \"string (M/F/U)\",\n";
    echo "  \"variantes\": [\n";
    echo "    {\n";
    echo "      \"talla\": \"string\",  ← OBLIGATORIO\n";
    echo "      \"cantidad\": number,   ← OBLIGATORIO\n";
    echo "      \"color_id\": number,   ← OBLIGATORIO\n";
    echo "      \"tela_id\": number,    ← OBLIGATORIO\n";
    echo "      \"tipo_manga_id\": number,         ← OBLIGATORIO\n";
    echo "      \"tipo_broche_boton_id\": number,  ← OBLIGATORIO\n";
    echo "      \"manga_obs\": \"string (opcional)\",\n";
    echo "      \"broche_boton_obs\": \"string (opcional)\",\n";
    echo "      \"tiene_bolsillos\": boolean,\n";
    echo "      \"bolsillos_obs\": \"string (opcional)\"\n";
    echo "    }\n";
    echo "  ]\n";
    echo "}\n\n";
    
    // 3. Checklista de validación
    echo "✅ CHECKLIST DE VALIDACIÓN\n";
    echo "────────────────────────────────────────────────────────\n\n";
    
    $checks = [
        "El frontend envía 'nombre_prenda'",
        "El frontend envía 'descripcion'",
        "El frontend envía 'genero'",
        "El frontend envía array de 'variantes'",
        "Cada variante tiene 'talla'",
        "Cada variante tiene 'cantidad'",
        "Cada variante tiene 'color_id'",
        "Cada variante tiene 'tela_id'",
        "Cada variante tiene 'tipo_manga_id'",
        "Cada variante tiene 'tipo_broche_boton_id'",
    ];
    
    foreach ($checks as $check) {
        echo "☐ $check\n";
    }
    
    echo "\n\n";
    
    // 4. Comandos útiles
    echo "🛠️  COMANDOS ÚTILES PARA DEBUGGING\n";
    echo "────────────────────────────────────────────────────────\n\n";
    
    echo "# Ver últimos logs del servicio\n";
    echo "tail -100 storage/logs/laravel.log | grep -i 'prenda\\|pedido'\n\n";
    
    echo "# Ver solicitudes HTTP (si tienes xdebug/profiler)\n";
    echo "tail -50 storage/logs/laravel.log | grep -i 'request\\|post'\n\n";
    
    echo "# Buscar errores específicos\n";
    echo "grep -i 'error\\|exception' storage/logs/laravel.log | tail -20\n\n";
    
    echo "# Monitorear en tiempo real\n";
    echo "tail -f storage/logs/laravel.log\n\n";
    
    // 5. Información de controlador
    echo "🎯 PUNTOS CLAVE DEL CÓDIGO\n";
    echo "────────────────────────────────────────────────────────\n\n";
    
    echo "Backend - Controlador:\n";
    echo "  📄 app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php\n";
    echo "  Método: crearSinCotizacion() o similar\n\n";
    
    echo "Backend - Servicio:\n";
    echo "  📄 app/Application/Services/PedidoPrendaService.php\n";
    echo "  Método: guardarPrendasEnPedido()\n";
    echo "  Método: guardarPrenda()\n\n";
    
    echo "Frontend - JavaScript:\n";
    echo "  📄 public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js\n";
    echo "  Método: recolectarDatosPedido()\n";
    echo "  Método: manejarSubmitFormulario()\n\n";
    
    echo "✅ Monitoreo completado\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
