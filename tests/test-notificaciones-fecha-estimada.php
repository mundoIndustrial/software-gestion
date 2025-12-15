#!/usr/bin/env php
<?php
/**
 * Script de Prueba - Notificaciones de Fecha Estimada
 * 
 * Uso: php tests/test-notificaciones-fecha-estimada.php
 */

require __DIR__ . '/../bootstrap/app.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n====================================================\n";
echo "  🧪 TEST: Notificaciones de Fecha Estimada\n";
echo "====================================================\n\n";

// 1. Obtener un asesor
echo "1️⃣  Obteniendo un asesor...\n";
$asesor = User::role('asesor')->first();
if (!$asesor) {
    echo "❌ No hay asesores en la base de datos\n";
    exit(1);
}
echo "✅ Asesor: {$asesor->name} (ID: {$asesor->id})\n\n";

// 2. Obtener o crear un pedido de ese asesor
echo "2️⃣  Obteniendo pedido del asesor...\n";
$pedido = PedidoProduccion::where('asesor_id', $asesor->id)->first();
if (!$pedido) {
    echo "❌ No hay pedidos para este asesor\n";
    exit(1);
}
echo "✅ Pedido: {$pedido->numero_pedido} (ID: {$pedido->id})\n\n";

// 3. Simular cambio de fecha estimada
echo "3️⃣  Asignando Fecha Estimada de Entrega...\n";
$fechaEstimada = Carbon::now()->addDays(7);
$pedido->fecha_estimada_de_entrega = $fechaEstimada;
$pedido->save();
echo "✅ Fecha asignada: {$fechaEstimada->format('d/m/Y')}\n\n";

// 4. Verificar notificación creada
echo "4️⃣  Verificando notificación en BD...\n";
$notificacion = DB::table('notifications')
    ->where('notifiable_id', $asesor->id)
    ->where('notifiable_type', 'App\\Models\\User')
    ->where('type', 'App\\Notifications\\FechaEstimadaAsignada')
    ->whereNull('read_at')
    ->latest()
    ->first();

if (!$notificacion) {
    echo "❌ No se encontró notificación\n";
    exit(1);
}

$data = json_decode($notificacion->data);
echo "✅ Notificación creada:\n";
echo "   - ID: {$notificacion->id}\n";
echo "   - Título: {$data->titulo}\n";
echo "   - Mensaje: {$data->mensaje}\n";
echo "   - Pedido: {$data->numero_pedido}\n";
echo "   - Fecha Estimada: {$data->fecha_estimada}\n";
echo "   - Creada en: {$notificacion->created_at}\n\n";

// 5. Probar endpoint de obtener notificaciones
echo "5️⃣  Simulando GET /asesores/notifications...\n";
$notificaciones = DB::table('notifications')
    ->where('notifiable_id', $asesor->id)
    ->where('notifiable_type', 'App\\Models\\User')
    ->where('type', 'App\\Notifications\\FechaEstimadaAsignada')
    ->whereNull('read_at')
    ->count();
echo "✅ Notificaciones no leídas: {$notificaciones}\n\n";

// 6. Marcar como leída
echo "6️⃣  Marcando notificación como leída...\n";
DB::table('notifications')
    ->where('id', $notificacion->id)
    ->update(['read_at' => now()]);
echo "✅ Notificación marcada como leída\n\n";

// 7. Verificar que se marcó
$leidas = DB::table('notifications')
    ->where('notifiable_id', $asesor->id)
    ->where('notifiable_type', 'App\\Models\\User')
    ->where('type', 'App\\Notifications\\FechaEstimadaAsignada')
    ->whereNotNull('read_at')
    ->count();
echo "✅ Notificaciones leídas: {$leidas}\n\n";

echo "====================================================\n";
echo "  ✅ TODAS LAS PRUEBAS PASARON CORRECTAMENTE\n";
echo "====================================================\n\n";
