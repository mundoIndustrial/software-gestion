<?php

// Script para crear el endpoint de marcar vistas
echo "🔧 Creando endpoint para marcar notificaciones como vistas\n";
echo "================================================\n\n";

// Ruta a agregar en routes/web.php
$routeToAdd = "
// Marcar observaciones como vistas (para badges)
Route::post('/despacho/{pedido_id}/observaciones/marcar-vistas', [DespachoController::class, 'marcarObservacionesComoVistas'])
    ->name('despacho.observaciones.marcar-vistas')
    ->where('pedido_id', '[0-9]+');
";

echo "📍 Agrega esta ruta en routes/web.php:\n";
echo $routeToAdd . "\n\n";

// Método a agregar en DespachoController.php
$methodToAdd = "
/**
 * Marcar observaciones de un pedido como vistas (para badges)
 */
public function marcarObservacionesComoVistas(\$pedidoId)
{
    try {
        // Actualizar todas las observaciones no leídas del pedido
        \$updated = DB::table('pedido_observaciones_despacho')
            ->where('pedido_produccion_id', \$pedidoId)
            ->where('estado', 0) // 0 = no leída
            ->update(['estado' => 1]); // 1 = leída

        return response()->json([
            'success' => true,
            'message' => 'Observaciones marcadas como vistas',
            'updated_count' => \$updated
        ]);
    } catch (\\Exception \$e) {
        Log::error('Error marcando observaciones como vistas', [
            'pedido_id' => \$pedidoId,
            'error' => \$e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al marcar observaciones como vistas'
        ], 500);
    }
}
";

echo "🔧 Agrega este método en app/Http/Controllers/DespachoController.php:\n";
echo $methodToAdd . "\n\n";

echo "✅ Listo! Ahora los badges solo se quitarán cuando el usuario haga clic en el botón.\n";
echo "📋 El campo 'estado' se actualizará a 1 (leída) cuando el usuario abra el modal.\n";
echo "🔄 Los badges se mantendrán hasta que el usuario interactúe con ellos.\n\n";

echo "📝 Estructura de la tabla pedido_observaciones_despacho:\n";
echo "   - estado: 0 = no leída (con badge)\n";
echo "   - estado: 1 = leída (sin badge)\n\n";

echo "🧪 Para probar:\n";
echo "1. Agrega la ruta en routes/web.php\n";
echo "2. Agrega el método en DespachoController.php\n";
echo "3. Recarga la página de despacho\n";
echo "4. Los badges deberían mantenerse hasta hacer clic\n";
echo "5. Al hacer clic, el badge debería desaparecer\n";
echo "6. Verifica en la BD que el campo 'estado' se actualizó a 1\n";
?>
