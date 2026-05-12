<?php

use App\Application\Services\Despacho\DespachoPendientesApplicationService;
use App\Models\PedidoProduccion;
use App\Models\ReciboPrenda;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$service = app(DespachoPendientesApplicationService::class);

$page = 1;
$perPage = 100;
$maxPages = 5;
$rows = [];

for ($i = 0; $i < $maxPages; $i++) {
    $payload = $service->obtenerPendientesUnificadosData('', 'todos', '', $page, $perPage);
    $data = $payload['data'] ?? [];

    if (empty($data)) {
        break;
    }

    foreach ($data as $row) {
        $rows[] = is_array($row) ? $row : (array) $row;
    }

    $hasMore = (bool) ($payload['pagination']['has_more'] ?? false);
    if (!$hasMore) {
        break;
    }

    $page++;
}

function resolverNumeroPedidoComoDetalle(int $entrada): ?int
{
    $pedido = PedidoProduccion::where('numero_pedido', $entrada)->first();
    if ($pedido) {
        return (int) $pedido->numero_pedido;
    }

    $recibo = ReciboPrenda::find($entrada);
    if ($recibo) {
        $pedidoPorRecibo = PedidoProduccion::where('numero_pedido', $recibo->numero_pedido)->first();
        if ($pedidoPorRecibo) {
            return (int) $pedidoPorRecibo->numero_pedido;
        }
    }

    $pedidoPorId = PedidoProduccion::find($entrada);
    if ($pedidoPorId) {
        return (int) $pedidoPorId->numero_pedido;
    }

    return null;
}

echo "Analizando " . count($rows) . " filas de pendientes unificados...\n\n";

$issues = [];
$ok = 0;

foreach ($rows as $row) {
    $id = (int) ($row['id'] ?? 0);
    $numero = (int) ($row['numero_pedido'] ?? 0);

    if ($id <= 0 || $numero <= 0) {
        continue;
    }

    $resolvedById = resolverNumeroPedidoComoDetalle($id);
    $resolvedByNumero = resolverNumeroPedidoComoDetalle($numero);

    $matchesById = $resolvedById === $numero;
    $matchesByNumero = $resolvedByNumero === $numero;

    if ($matchesByNumero && !$matchesById) {
        $issues[] = [
            'id' => $id,
            'numero_pedido_tabla' => $numero,
            'resuelto_con_id' => $resolvedById,
            'resuelto_con_numero' => $resolvedByNumero,
            'cliente_tabla' => (string) ($row['cliente'] ?? ''),
        ];
    } else {
        $ok++;
    }
}

if (empty($issues)) {
    echo "OK: no se detectaron cruces id/numero en las filas revisadas.\n";
    echo "Filas consistentes: {$ok}\n";
    exit(0);
}

echo "Se detectaron " . count($issues) . " posibles cruces id/numero:\n\n";
foreach ($issues as $idx => $issue) {
    $n = $idx + 1;
    echo "{$n}) id={$issue['id']} | numero_tabla=#{$issue['numero_pedido_tabla']} | ";
    echo "detalle_con_id=#{$issue['resuelto_con_id']} | detalle_con_numero=#{$issue['resuelto_con_numero']} | ";
    echo "cliente_tabla={$issue['cliente_tabla']}\n";
}

echo "\nRecomendacion: enlazar desde la tabla usando numero_pedido en vez de id.\n";
