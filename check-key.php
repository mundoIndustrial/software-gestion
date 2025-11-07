<?php
$c = json_decode(file_get_contents('resources/mundoindustrial-backups-d98b14a4bd34.json'), true);
echo "Primeros 100 caracteres de la clave:\n";
echo substr($c['private_key'], 0, 100) . "\n\n";
echo "¿Contiene \\n literal? " . (strpos($c['private_key'], '\\n') !== false ? 'SÍ' : 'NO') . "\n";
echo "¿Contiene salto de línea real? " . (strpos($c['private_key'], "\n") !== false ? 'SÍ' : 'NO') . "\n";
