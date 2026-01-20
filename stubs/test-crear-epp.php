<?php

use App\Domain\Shared\CQRS\CommandBus;
use App\Application\Commands\CrearEppCommand;

return function () {
    $commandBus = app(CommandBus::class);

    echo "🔵 Iniciando prueba de creación de EPP...\n\n";

    $nombre = 'Gafas de Seguridad Test ' . time();
    $categoria = 'OJOS';
    $codigo = 'GAF-SEG-' . time();
    $descripcion = 'Gafas de seguridad para protección ocular - Test: ' . date('Y-m-d H:i:s');

    echo "📝 Datos a crear:\n";
    echo "  - Nombre: $nombre\n";
    echo "  - Categoría: $categoria\n";
    echo "  - Código: $codigo\n";
    echo "  - Descripción: $descripcion\n\n";

    try {
        $command = new CrearEppCommand(
            nombre: $nombre,
            categoria: $categoria,
            codigo: $codigo,
            descripcion: $descripcion
        );

        $resultado = $commandBus->execute($command);

        echo " EPP creado exitosamente!\n";
        echo "📊 Resultado:\n";
        echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    } catch (\Exception $e) {
        echo " Error al crear EPP:\n";
        echo "   " . $e->getMessage() . "\n";
    }
};
