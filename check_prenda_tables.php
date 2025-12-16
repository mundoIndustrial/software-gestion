<?php
$m = new mysqli('localhost', 'root', '29522628', 'mundo_bd');
$r = $m->query("SHOW TABLES");
echo "Tablas relacionadas con prendas/cotizaciones:\n";
while($t = $r->fetch_row()) { 
    $name = strtolower($t[0]);
    if (strpos($name, 'prenda') !== false || strpos($name, 'cot') !== false || strpos($name, 'producto') !== false) {
        echo "  - " . $t[0] . "\n";
    }
}
