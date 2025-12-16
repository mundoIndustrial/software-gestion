<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

$cols = \DB::select('DESCRIBE prenda_fotos_cot');
foreach ($cols as $c) {
    echo $c->Field . ' - ' . $c->Type . PHP_EOL;
}
