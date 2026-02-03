<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Autenticar como supervisor
$user = DB::table('users')->where('email', 'yus22@gmail.com')->first();
if ($user) {
    Auth::loginUsingId($user->id);
    echo "✅ Autenticado como: {$user->name}\n\n";
}

// Usar Laravel testing para hacer request
$request = \Illuminate\Http\Request::capture();
$request->setUserResolver(function() use ($user) {
    return \App\Models\User::find($user->id);
});

echo "=== Verificando consecutivos en base de datos ===\n\n";

$consecutivos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', 16)
    ->where('activo', 1)
    ->where('prenda_id', 20)
    ->get();

echo "Consecutivos para Pedido 16, Prenda 20:\n";
foreach ($consecutivos as $cons) {
    echo "  ✓ {$cons->tipo_recibo}: {$cons->consecutivo_actual}\n";
}

echo "\nConsecutivos generales del pedido (sin prenda específica):\n";
$generales = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', 16)
    ->where('activo', 1)
    ->whereNull('prenda_id')
    ->get();

foreach ($generales as $cons) {
    echo "  ✓ {$cons->tipo_recibo}: {$cons->consecutivo_actual}\n";
}

echo "\n=== Resultado Esperado en el Endpoint ===\n";
$allConsecutivos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', 16)
    ->where('activo', 1)
    ->where(function($query) {
        $query->where('prenda_id', 20)->orWhereNull('prenda_id');
    })
    ->get();

$recibos = [
    'COSTURA' => null,
    'ESTAMPADO' => null,
    'BORDADO' => null,
    'DTF' => null,
    'SUBLIMADO' => null,
    'REFLECTIVO' => null
];

foreach ($allConsecutivos as $consecutivo) {
    $tipo = $consecutivo->tipo_recibo;
    if (array_key_exists($tipo, $recibos)) {
        $recibos[$tipo] = $consecutivo->consecutivo_actual;
    }
}

echo "Estructura recibos a devolver:\n";
foreach ($recibos as $tipo => $valor) {
    $v = $valor !== null ? (string)$valor : "null";
    echo "  ✓ {$tipo}: {$v}\n";
}
?>
