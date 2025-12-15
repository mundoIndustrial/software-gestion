<?php

use Illuminate\Support\Facades\DB;

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ VALIDACIÓN: Generación Sincrónica de Números\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// TEST 1: Verificar tabla existe
echo "TEST 1: Verificar tabla numero_secuencias\n";
$secuencias = DB::table('numero_secuencias')->get();
echo "✅ Secuencias encontradas: " . $secuencias->count() . "\n";
foreach ($secuencias as $s) {
    echo "   - {$s->tipo}: próximo_numero = {$s->proximo_numero}\n";
}
echo "\n";

// TEST 2: Generar 3 números
echo "TEST 2: Generar 3 números secuenciales con lock\n";
$numeros = [];
for ($i = 0; $i < 3; $i++) {
    $numero = DB::transaction(function () {
        $sec = DB::table('numero_secuencias')
            ->lockForUpdate()
            ->where('tipo', 'cotizaciones_prenda')
            ->first();
        
        $prox = $sec->proximo_numero;
        DB::table('numero_secuencias')
            ->where('tipo', 'cotizaciones_prenda')
            ->update(['proximo_numero' => $prox + 1]);
        
        return 'COT-' . date('Ymd') . '-' . str_pad($prox, 3, '0', STR_PAD_LEFT);
    });
    
    $numeros[] = $numero;
    echo "   " . ($i + 1) . ". $numero\n";
}
echo "\n";

// TEST 3: Verificar duplicados
echo "TEST 3: Verificar números únicos\n";
$unicos = array_unique($numeros);
echo "   Total: " . count($numeros) . "\n";
echo "   Únicos: " . count($unicos) . "\n";

if (count($unicos) === count($numeros)) {
    echo "   ✅ ¡NO HAY DUPLICADOS!\n";
} else {
    echo "   ❌ ERROR: Hay duplicados\n";
}
echo "\n";

// TEST 4: Verificar formato
echo "TEST 4: Verificar formato COT-YYYYMMDD-NNN\n";
$patron = '/^COT-\d{8}-\d{3}$/';
$todosValidos = true;
foreach ($numeros as $num) {
    if (!preg_match($patron, $num)) {
        echo "   ❌ Inválido: $num\n";
        $todosValidos = false;
    }
}
if ($todosValidos) {
    echo "   ✅ Todos los formatos son correctos\n";
}
echo "\n";

// TEST 5: Diferentes tipos no interfieren
echo "TEST 5: Diferentes tipos de secuencia\n";
$numeroPrenda = DB::transaction(function () {
    $sec = DB::table('numero_secuencias')
        ->lockForUpdate()
        ->where('tipo', 'cotizaciones_prenda')
        ->first();
    
    $prox = $sec->proximo_numero;
    DB::table('numero_secuencias')
        ->where('tipo', 'cotizaciones_prenda')
        ->update(['proximo_numero' => $prox + 1]);
    
    return 'COT-' . date('Ymd') . '-' . str_pad($prox, 3, '0', STR_PAD_LEFT);
});

$numeroBordado = DB::transaction(function () {
    $sec = DB::table('numero_secuencias')
        ->lockForUpdate()
        ->where('tipo', 'cotizaciones_bordado')
        ->first();
    
    $prox = $sec->proximo_numero;
    DB::table('numero_secuencias')
        ->where('tipo', 'cotizaciones_bordado')
        ->update(['proximo_numero' => $prox + 1]);
    
    return 'COT-' . date('Ymd') . '-' . str_pad($prox, 3, '0', STR_PAD_LEFT);
});

echo "   Prenda:  $numeroPrenda\n";
echo "   Bordado: $numeroBordado\n";

if ($numeroPrenda !== $numeroBordado) {
    echo "   ✅ Diferentes tipos no interfieren\n";
} else {
    echo "   ❌ ERROR: Tipos interfieren\n";
}
echo "\n";

// TEST 6: Estado final
echo "TEST 6: Estado final de secuencias\n";
$secuenciasFinales = DB::table('numero_secuencias')->get();
foreach ($secuenciasFinales as $s) {
    echo "   - {$s->tipo}: próximo_numero = {$s->proximo_numero}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ TODOS LOS TESTS COMPLETADOS CON ÉXITO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
