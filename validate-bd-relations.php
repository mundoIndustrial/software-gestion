#!/usr/bin/env php
<?php
/**
 * Script de Validación: Estructura BD y Relaciones Eloquent
 * 
 * Uso:
 * cd c:\Users\Usuario\Documents\trabahiiiii\v10\v10\mundoindustrial
 * php validate-bd-relations.php 2700
 * 
 * Este script verifica que todas las relaciones Eloquent funcionen correctamente
 * sin necesidad de usar Tinker manualmente
 */

require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Log;
use App\Models\PedidoProduccion;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obtener ID del pedido desde argumentos
$pedidoId = $argv[1] ?? 2700;

echo "\n" . str_repeat("=", 80) . "\n";
echo "VALIDACIÓN DE ESTRUCTURA BD Y RELACIONES ELOQUENT\n";
echo str_repeat("=", 80) . "\n\n";

echo "📋 Validando pedido ID: $pedidoId\n\n";

try {
    // 1. Verificar que el modelo existe
    echo "1️⃣  Verificando existencia del pedido...\n";
    $modeloPedido = PedidoProduccion::find($pedidoId);
    
    if (!$modeloPedido) {
        throw new \Exception("Pedido $pedidoId no encontrado en BD");
    }
    
    echo "   ✅ Pedido encontrado: #$modeloPedido->numero_pedido\n\n";

    // 2. Verificar prendas
    echo "2️⃣  Verificando relación prendas...\n";
    $prendas = $modeloPedido->prendas;
    
    if (!$prendas) {
        throw new \Exception("No se puede cargar relación prendas");
    }
    
    echo "   ✅ Prendas cargadas: " . count($prendas) . " prendas\n";
    
    if ($prendas->isEmpty()) {
        echo "   ⚠️  Advertencia: El pedido no tiene prendas\n";
    } else {
        // 3. Verificar primera prenda
        $prenda = $prendas->first();
        echo "\n   Verificando prenda ID: $prenda->id ($prenda->nombre_prenda)\n";
        
        // 3a. Verificar tallas
        echo "   3️⃣  Verificando relación tallas...\n";
        $tallas = $prenda->tallas;
        echo "      ✅ Tallas cargadas: " . count($tallas) . " registros\n";
        
        if (!$tallas->isEmpty()) {
            $talla = $tallas->first();
            echo "         - Ejemplo: $talla->genero $talla->talla = $talla->cantidad\n";
        }
        
        // 3b. Verificar variantes
        echo "   4️⃣  Verificando relación variantes...\n";
        $variantes = $prenda->variantes;
        echo "      ✅ Variantes cargadas: " . count($variantes) . " registros\n";
        
        if (!$variantes->isEmpty()) {
            $var = $variantes->first();
            echo "         - Ejemplo: Manga ID $var->tipo_manga_id, Broche ID $var->tipo_broche_boton_id\n";
            
            // Verificar relación tipoManga
            if ($var->tipo_manga_id) {
                echo "   5️⃣  Verificando relación tipoManga...\n";
                $manga = $var->tipoManga;
                if ($manga) {
                    echo "      ✅ Manga cargada: $manga->nombre\n";
                } else {
                    echo "      ⚠️  Manga con ID $var->tipo_manga_id no encontrado\n";
                }
            }
            
            // Verificar relación tipoBroche
            if ($var->tipo_broche_boton_id) {
                echo "   6️⃣  Verificando relación tipoBroche...\n";
                $broche = $var->tipoBroche;
                if ($broche) {
                    echo "      ✅ Broche cargado: $broche->nombre\n";
                } else {
                    echo "      ⚠️  Broche con ID $var->tipo_broche_boton_id no encontrado\n";
                }
            }
        }
        
        // 3c. Verificar coloresTelas
        echo "   7️⃣  Verificando relación coloresTelas...\n";
        $coloresTelas = $prenda->coloresTelas;
        echo "      ✅ Colores/Telas cargados: " . count($coloresTelas) . " registros\n";
        
        if (!$coloresTelas->isEmpty()) {
            $ct = $coloresTelas->first();
            echo "         - Color ID: $ct->color_id, Tela ID: $ct->tela_id\n";
            
            // Verificar color
            if ($ct->color) {
                echo "         - Color: " . $ct->color->nombre . "\n";
            }
            
            // Verificar tela
            if ($ct->tela) {
                echo "         - Tela: " . $ct->tela->nombre . " (Ref: " . $ct->tela->referencia . ")\n";
            }
            
            // Verificar fotos de tela
            echo "   8️⃣  Verificando relación fotos de tela...\n";
            $fotosTela = $ct->fotos;
            echo "      ✅ Fotos de tela cargadas: " . count($fotosTela) . " registros\n";
        }
        
        // 3d. Verificar fotos de prenda
        echo "   9️⃣  Verificando relación fotos de prenda...\n";
        $fotos = $prenda->fotos;
        echo "      ✅ Fotos cargadas: " . count($fotos) . " registros\n";
    }
    
    // 4. Verificar EPPs
    echo "\n   1️⃣0️⃣  Verificando relación epps...\n";
    $epps = $modeloPedido->epps;
    echo "      ✅ EPPs cargados: " . count($epps) . " registros\n";
    
    if (!$epps->isEmpty()) {
        $epp = $epps->first();
        echo "         - EPP ID: $epp->epp_id, Cantidad: $epp->cantidad\n";
        
        if ($epp->epp) {
            $nombreEpp = $epp->epp->nombre_completo ?? $epp->epp->nombre ?? 'SIN NOMBRE';
            echo "         - Nombre: $nombreEpp\n";
        }
        
        // Verificar imágenes del EPP
        echo "   1️⃣1️⃣  Verificando relación imágenes de EPP...\n";
        $imagenesEpp = $epp->imagenes;
        echo "      ✅ Imágenes EPP cargadas: " . count($imagenesEpp) . " registros\n";
    }
    
    // 5. Verificar ObtenerPedidoUseCase
    echo "\n   1️⃣2️⃣  Ejecutando ObtenerPedidoUseCase::ejecutar($pedidoId)...\n";
    
    $repository = app(\App\Domain\Pedidos\Repositories\PedidoRepository::class);
    $useCase = new ObtenerPedidoUseCase($repository);
    
    $resultado = $useCase->ejecutar($pedidoId);
    
    echo "      ✅ Use Case ejecutado exitosamente\n";
    echo "      ✅ Prendas en DTO: " . count($resultado->prendas) . "\n";
    echo "      ✅ EPPs en DTO: " . count($resultado->epps) . "\n";
    
    if (!empty($resultado->prendas)) {
        $prenda = $resultado->prendas[0];
        echo "\n      Estructura de primera prenda:\n";
        echo "         - nombre_prenda: " . $prenda['nombre_prenda'] . "\n";
        echo "         - tela: " . $prenda['tela'] . "\n";
        echo "         - color: " . $prenda['color'] . "\n";
        echo "         - tallas: " . json_encode($prenda['tallas']) . "\n";
        echo "         - variantes: " . count($prenda['variantes']) . " registros\n";
        echo "         - imagenes: " . count($prenda['imagenes']) . " registros\n";
        echo "         - imagenes_tela: " . count($prenda['imagenes_tela']) . " registros\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "✅ VALIDACIÓN COMPLETADA EXITOSAMENTE\n";
    echo str_repeat("=", 80) . "\n\n";
    
} catch (\Throwable $e) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "❌ ERROR EN VALIDACIÓN\n";
    echo str_repeat("=", 80) . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    echo str_repeat("=", 80) . "\n\n";
    
    exit(1);
}
