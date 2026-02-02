<?php

// Obtener datos de la ruta /registros/45808/recibos-datos
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://localhost:8000/registros/45808/recibos-datos',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest'
    ),
    CURLOPT_COOKIE => 'XSRF-TOKEN=eyJpdiI6ImF1KzJrM0F0dXVxU3FzSUhKTVVyOFE9PSIsInZhbHVlIjoieGcyVEQvRXRJRkxyQjBjdHhKYUdDK0tuUHcwQjVodEVhWDZkZWEyYVhOOTBTeVRZdm9BVVNaSDhTdnBUSFZ0UXciLCJtYWMiOiI3OGI3ZDM1OTA4MzI3MjZhZjNiZTQxYWE4YzcwZjI0YjMzMzI2NjI0MWE5MzFmOGU2YzExMDNhMzY4ODBhNzY3In0%3D; laravel_session=eyJpdiI6IlUwOTh1d0x6MW1mSDJrZVQxVkVRWkE9PSIsInZhbHVlIjoibDM1dHJRY3MyT2h3MEpybStmRjN5NGJyNHFrRkJMclBHbEJUVnkzSjBuSEg1bk93dkExc09oTHRwRjhYR3hKdUoiLCJtYWMiOiI5ZWI1ZmY0NTI4MWFmMmM1YWQ2MzYyYjYzMjI0YjkzODI1ZjE4OTgxMGI0YmJlNzI5OTAyOTY1YTQ1YzAxMGI4In0%3D'
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo "Error: $err\n";
} else {
    $data = json_decode($response, true);
    echo "=== RESPUESTA DE /registros/45808/recibos-datos ===\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";
    
    // Mostrar estructura
    if (isset($data['data'])) {
        echo "=== CAMPOS EN data ===\n";
        echo json_encode(array_keys($data['data']), JSON_PRETTY_PRINT);
        echo "\n\n";
        
        // Mostrar solo los campos principales
        echo "=== VALORES PRINCIPALES ===\n";
        echo "numero_pedido: " . ($data['data']['numero'] ?? $data['data']['numero_pedido'] ?? 'N/A') . "\n";
        echo "cliente: " . ($data['data']['cliente'] ?? 'N/A') . "\n";
        echo "estado: " . ($data['data']['estado'] ?? 'N/A') . "\n";
        echo "fecha_de_creacion_de_orden: " . ($data['data']['fecha_de_creacion_de_orden'] ?? $data['data']['fechaCreacion'] ?? 'N/A') . "\n";
    }
}
