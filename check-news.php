<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\News;

echo "=== VERIFICACIÓN DE NOTIFICACIONES ===\n\n";

$total = News::count();
echo "Total de registros en 'news': {$total}\n";

$today = News::whereDate('created_at', today())->count();
echo "Registros de hoy: {$today}\n\n";

if ($today > 0) {
    echo "Últimos 5 registros de hoy:\n";
    $news = News::with('user')
        ->whereDate('created_at', today())
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($news as $n) {
        $user = $n->user ? $n->user->name : 'Sistema';
        echo "- [{$n->created_at}] {$n->event_type} | Usuario: {$user}\n";
        echo "  Tabla: {$n->table_name} | ID: {$n->record_id}\n";
        echo "  Descripción: {$n->description}\n\n";
    }
} else {
    echo "⚠️ NO HAY REGISTROS DE HOY\n";
    echo "Últimos 3 registros en general:\n";
    $news = News::with('user')
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();
    
    foreach ($news as $n) {
        $user = $n->user ? $n->user->name : 'Sistema';
        echo "- [{$n->created_at}] {$n->event_type} | Usuario: {$user}\n";
        echo "  {$n->description}\n\n";
    }
}

// Verificar columnas
echo "\n=== ESTRUCTURA DE TABLA ===\n";
$columns = \DB::select("SHOW COLUMNS FROM news");
foreach ($columns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}
