<?php

/**
 * Script de prueba para el sistema de auditoría
 * Ejecutar con: php test-auditoria.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\News;
use App\Models\RegistroPisoCorte;
use Illuminate\Support\Facades\Auth;

echo "🔍 PRUEBA DEL SISTEMA DE AUDITORÍA\n";
echo "=====================================\n\n";

// Simular usuario autenticado
$user = User::first();
if (!$user) {
    echo "❌ No hay usuarios en el sistema. Crea uno primero.\n";
    exit(1);
}

Auth::login($user);
echo "✅ Usuario autenticado: {$user->name}\n\n";

// Prueba 1: Verificar estructura de tabla news
echo "📋 Verificando estructura de tabla 'news'...\n";
$columns = \DB::select("SHOW COLUMNS FROM news");
$columnNames = array_column($columns, 'Field');

$requiredColumns = ['table_name', 'record_id', 'event_type', 'description', 'user_id', 'metadata'];
$missingColumns = array_diff($requiredColumns, $columnNames);

if (empty($missingColumns)) {
    echo "✅ Todas las columnas requeridas existen\n";
} else {
    echo "❌ Faltan columnas: " . implode(', ', $missingColumns) . "\n";
    exit(1);
}

// Prueba 2: Contar registros de auditoría existentes
echo "\n📊 Estadísticas actuales:\n";
$totalNews = News::count();
echo "   Total de eventos registrados: {$totalNews}\n";

$todayNews = News::whereDate('created_at', today())->count();
echo "   Eventos de hoy: {$todayNews}\n";

$byType = News::whereDate('created_at', today())
    ->select('event_type', \DB::raw('count(*) as count'))
    ->groupBy('event_type')
    ->get();

if ($byType->count() > 0) {
    echo "   Por tipo de evento:\n";
    foreach ($byType as $type) {
        echo "      - {$type->event_type}: {$type->count}\n";
    }
}

// Prueba 3: Crear un registro de prueba (si hay datos necesarios)
echo "\n🧪 Probando creación de registro con auditoría...\n";

try {
    // Verificar si existen las dependencias necesarias
    $hora = \App\Models\Hora::first();
    $operario = User::first();
    $maquina = \App\Models\Maquina::first();
    $tela = \App\Models\Tela::first();

    if ($hora && $operario && $maquina && $tela) {
        $testRecord = RegistroPisoCorte::create([
            'fecha' => today(),
            'modulo' => 'TEST-AUDITORIA',
            'orden_produccion' => 'TEST-001',
            'hora_id' => $hora->id,
            'operario_id' => $operario->id,
            'actividad' => 'Prueba de auditoría',
            'maquina_id' => $maquina->id,
            'tela_id' => $tela->id,
            'tiempo_ciclo' => 10.5,
            'porcion_tiempo' => 1.0,
            'cantidad' => 100,
            'producida' => 50,
            'tiempo_parada_no_programada' => 0,
            'tiempo_para_programada' => 0,
            'tiempo_disponible' => 3600,
            'meta' => 100,
            'eficiencia' => 50.0
        ]);

        echo "✅ Registro de prueba creado (ID: {$testRecord->id})\n";

        // Verificar que se creó el registro de auditoría
        sleep(1); // Esperar un momento
        $auditRecord = News::where('table_name', 'registro_piso_corte')
            ->where('record_id', $testRecord->id)
            ->where('event_type', 'record_created')
            ->first();

        if ($auditRecord) {
            echo "✅ Registro de auditoría creado correctamente\n";
            echo "   Usuario: " . ($auditRecord->user ? $auditRecord->user->name : 'N/A') . "\n";
            echo "   Descripción: {$auditRecord->description}\n";
        } else {
            echo "❌ No se encontró el registro de auditoría\n";
        }

        // Probar actualización
        echo "\n🔄 Probando actualización de registro...\n";
        $testRecord->update(['producida' => 75]);

        sleep(1);
        $updateAudit = News::where('table_name', 'registro_piso_corte')
            ->where('record_id', $testRecord->id)
            ->where('event_type', 'record_updated')
            ->first();

        if ($updateAudit) {
            echo "✅ Auditoría de actualización registrada\n";
            echo "   Cambios: " . json_encode($updateAudit->metadata['changes'] ?? []) . "\n";
        } else {
            echo "❌ No se registró la actualización\n";
        }

        // Probar eliminación
        echo "\n🗑️  Probando eliminación de registro...\n";
        $testRecord->delete();

        sleep(1);
        $deleteAudit = News::where('table_name', 'registro_piso_corte')
            ->where('record_id', $testRecord->id)
            ->where('event_type', 'record_deleted')
            ->first();

        if ($deleteAudit) {
            echo "✅ Auditoría de eliminación registrada\n";
        } else {
            echo "❌ No se registró la eliminación\n";
        }

    } else {
        echo "⚠️  No hay datos suficientes para crear registro de prueba\n";
        echo "   Necesitas: hora, operario, máquina y tela en la base de datos\n";
    }

} catch (\Exception $e) {
    echo "❌ Error en prueba: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}:{$e->getLine()}\n";
}

// Prueba 4: Verificar últimos eventos
echo "\n📜 Últimos 5 eventos registrados:\n";
$recentNews = News::with('user')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentNews->count() > 0) {
    foreach ($recentNews as $news) {
        $userName = $news->user ? $news->user->name : 'Sistema';
        $time = $news->created_at->format('Y-m-d H:i:s');
        echo "   [{$time}] {$news->event_type} - {$userName}\n";
        echo "      {$news->description}\n";
    }
} else {
    echo "   No hay eventos registrados\n";
}

echo "\n✅ PRUEBA COMPLETADA\n";
echo "=====================================\n";
echo "\n💡 Visita el dashboard en /dashboard para ver las notificaciones\n";
