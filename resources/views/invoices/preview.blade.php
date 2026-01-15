<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura - Pedido #{{ $orden->numero_pedido }}</title>
</head>
<body>
    <x-invoice-factura :orden="$orden" :mostrarProcesos="true" :mostrarEPP="true" />
</body>
</html>
