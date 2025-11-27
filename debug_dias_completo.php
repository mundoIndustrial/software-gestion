<?php
// Script de diagnóstico para verificar cálculo de días

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== DIAGNÓSTICO DE CÁLCULO DE DÍAS ===\n\n";

// 1. Verificar primeros 5 pedidos
echo "1️⃣  VERIFICANDO PRIMEROS 5 PEDIDOS:\n";
$pedidos = PedidoProduccion::limit(5)->get();

foreach ($pedidos as $pedido) {
    echo "\n📋 Pedido: {$pedido->numero_pedido}\n";
    echo "   Fecha creación: {$pedido->fecha_de_creacion_de_orden}\n";
    
    // Obtener procesos
    $procesos = DB::table('procesos_prenda')
        ->where('numero_pedido', $pedido->numero_pedido)
        ->orderBy('fecha_inicio', 'ASC')
        ->select('proceso', 'fecha_inicio', 'fecha_fin')
        ->get();
    
    echo "   Procesos encontrados: {$procesos->count()}\n";
    
    if ($procesos->count() > 0) {
        foreach ($procesos as $p) {
            echo "     • {$p->proceso}: {$p->fecha_inicio}\n";
        }
    }
    
    // Obtener días calculados
    $dias = CacheCalculosService::getTotalDias($pedido->numero_pedido, $pedido->estado);
    echo "   ✅ Total días: {$dias}\n";
}

// 2. Verificar método directo
echo "\n\n2️⃣  PROBANDO CÁLCULO DIRECTO (sin caché):\n";
$testPedido = $pedidos->first();

if ($testPedido) {
    echo "Pedido de prueba: {$testPedido->numero_pedido}\n";
    
    $procesos = DB::table('procesos_prenda')
        ->where('numero_pedido', $testPedido->numero_pedido)
        ->orderBy('fecha_inicio', 'ASC')
        ->select('fecha_inicio')
        ->get();
    
    if ($procesos->isNotEmpty()) {
        echo "Total procesos: {$procesos->count()}\n";
        
        // Calcular manualmente
        $festivosArray = Festivo::pluck('fecha')->toArray();
        $festivosSet = [];
        foreach ($festivosArray as $f) {
            try {
                $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
            } catch (\Exception $e) {}
        }
        
        $procesosFechas = $procesos->map(fn($p) => Carbon::parse($p->fecha_inicio))->toArray();
        echo "Fechas de procesos:\n";
        foreach ($procesosFechas as $i => $fecha) {
            echo "  {$i}: {$fecha->format('Y-m-d H:i:s')}\n";
        }
        
        // Calcular días entre cada par
        echo "\nCálculo por tramo:\n";
        $totalDiasManual = 0;
        foreach ($procesosFechas as $i => $fechaInicio) {
            $fechaFin = isset($procesosFechas[$i + 1]) ? $procesosFechas[$i + 1] : Carbon::now();
            
            // Simular calcularDiasHabiles
            $current = $fechaInicio->copy()->addDay();
            $diasEnTramo = 0;
            $weekends = 0;
            $holidays = 0;
            
            while ($current <= $fechaFin) {
                $dateString = $current->format('Y-m-d');
                $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                $isFestivo = isset($festivosSet[$dateString]);
                
                $diasEnTramo++;
                if ($isWeekend) $weekends++;
                if ($isFestivo) $holidays++;
                
                $current->addDay();
            }
            
            $diasHabiles = $diasEnTramo - $weekends - $holidays;
            $totalDiasManual += $diasHabiles;
            
            echo "  Tramo {$i}: {$fechaInicio->format('Y-m-d')} → {$fechaFin->format('Y-m-d')}\n";
            echo "    • Total días: {$diasEnTramo}, Fines de semana: {$weekends}, Festivos: {$holidays}\n";
            echo "    • Días hábiles: {$diasHabiles}\n";
        }
        
        echo "\n  📊 TOTAL MANUAL: {$totalDiasManual} días\n";
    } else {
        echo "❌ Sin procesos para este pedido\n";
    }
}

// 3. Verificar caché
echo "\n\n3️⃣  ESTADO DE CACHÉ:\n";
$cacheKey = "orden_dias_{$testPedido->numero_pedido}_{$testPedido->estado}";
$cachedValue = \Illuminate\Support\Facades\Cache::get($cacheKey);
echo "Clave: {$cacheKey}\n";
echo "Valor en caché: " . ($cachedValue !== null ? $cachedValue : "❌ NO ENCONTRADO") . "\n";

// 4. Verificar getTotalDiasBatch
echo "\n\n4️⃣  PROBANDO getTotalDiasBatch:\n";
$ordenesPrueba = PedidoProduccion::limit(3)->get();
$festivos = Festivo::pluck('fecha')->toArray();
$resultados = CacheCalculosService::getTotalDiasBatch($ordenesPrueba->toArray(), $festivos);

foreach ($resultados as $numeroPedido => $dias) {
    echo "Pedido {$numeroPedido}: {$dias} días\n";
}

echo "\n✅ Diagnóstico completado\n";
