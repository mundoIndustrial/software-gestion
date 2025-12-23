<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FOTOS EN DRAFT (PRENDA 36) ===\n";
$fotosDraft = DB::table('prenda_tela_fotos_cot')
  ->where('prenda_cot_id', 36)
  ->select('id', 'prenda_cot_id', 'prenda_tela_cot_id', 'ruta_original', 'ruta_webp')
  ->get();
  
foreach($fotosDraft as $f) {
  echo "ID: {$f->id} | Prenda: {$f->prenda_cot_id} | Tela: {$f->prenda_tela_cot_id} | Ruta: {$f->ruta_original}\n";
}

echo "\n=== FOTOS EN ENVIO (PRENDA 37) ===\n";
$fotosEnvio = DB::table('prenda_tela_fotos_cot')
  ->where('prenda_cot_id', 37)
  ->select('id', 'prenda_cot_id', 'prenda_tela_cot_id', 'ruta_original', 'ruta_webp')
  ->get();
  
foreach($fotosEnvio as $f) {
  echo "ID: {$f->id} | Prenda: {$f->prenda_cot_id} | Tela: {$f->prenda_tela_cot_id} | Ruta: {$f->ruta_original}\n";
}

echo "\n=== TELAS EN DRAFT (PRENDA 36) ===\n";
$telasDraft = DB::table('prenda_telas_cot')
  ->where('prenda_cot_id', 36)
  ->select('id', 'color_id', 'tela_id', 'variante_prenda_cot_id')
  ->get();
  
foreach($telasDraft as $t) {
  echo "ID: {$t->id} | Color: {$t->color_id} | Tela: {$t->tela_id} | Variante: {$t->variante_prenda_cot_id}\n";
}

echo "\n=== TELAS EN ENVIO (PRENDA 37) ===\n";
$telasEnvio = DB::table('prenda_telas_cot')
  ->where('prenda_cot_id', 37)
  ->select('id', 'color_id', 'tela_id', 'variante_prenda_cot_id')
  ->get();
  
foreach($telasEnvio as $t) {
  echo "ID: {$t->id} | Color: {$t->color_id} | Tela: {$t->tela_id} | Variante: {$t->variante_prenda_cot_id}\n";
}

echo "\n=== BUSQUEDA MANUAL (lo que debería encontrar) ===\n";
$busqueda = DB::table('prenda_tela_fotos_cot as ptf')
  ->join('prenda_telas_cot as ptc', 'ptf.prenda_tela_cot_id', '=', 'ptc.id')
  ->where('ptc.color_id', 40)
  ->where('ptc.tela_id', 22)
  ->where('ptc.variante_prenda_cot_id', 34)
  ->select('ptf.id', 'ptf.prenda_cot_id', 'ptf.ruta_original', 'ptc.color_id', 'ptc.tela_id', 'ptc.variante_prenda_cot_id')
  ->get();

echo "Resultados encontrados: " . count($busqueda) . "\n";
foreach($busqueda as $b) {
  echo "Foto ID: {$b->id} | Prenda: {$b->prenda_cot_id} | Color: {$b->color_id} | Tela: {$b->tela_id} | Variante: {$b->variante_prenda_cot_id}\n";
}
