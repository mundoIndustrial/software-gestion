<?php
$m = new mysqli('localhost', 'root', '29522628', 'mundo_bd');
$r = $m->query('SHOW COLUMNS FROM cotizaciones');
echo "Campos en cotizaciones:\n";
while($c = $r->fetch_assoc()) { 
    echo $c['Field'] . "\n"; 
}
