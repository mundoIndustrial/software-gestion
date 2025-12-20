#!/usr/bin/env php
<?php
require __DIR__ . '/bootstrap/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArrayInput([
        'command' => 'tinker'
    ]),
    new Symfony\Component\Console\Output\BufferedOutput()
);

// Ahora ejecutar en tinker
Psy\Shell::debug([
    'logoPedido' => \App\Models\LogoPedido::where('numero_pedido', 'LOGO-00011')->first(),
]);
