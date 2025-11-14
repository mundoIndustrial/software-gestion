<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$kernel->handle($request = \Illuminate\Http\Request::capture());

use App\Models\RegistroPisoCorte;

$r = RegistroPisoCorte::orderBy('id', 'desc')->first();

if ($r) {
    echo "ID: {$r->id} | Cantidad: {$r->cantidad} | TD: {$r->tiempo_disponible} | Meta: {$r->meta} | Eff: {$r->eficiencia}\n";
} else {
    echo "No hay registros\n";
}
?>
