<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// Simular request
$request = Illuminate\Http\Request::create(
    '/dashboard/news?date=' . date('Y-m-d'),
    'GET'
);

// Simular usuario autenticado
$user = App\Models\User::first();
if ($user) {
    $app->instance('request', $request);
    Auth::login($user);
}

$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n\n";
echo "Response:\n";
echo $response->getContent();
echo "\n";
