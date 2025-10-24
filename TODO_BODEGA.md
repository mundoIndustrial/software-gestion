# TODO: Implementar Vista Escalable de Entregas Bodega ✅

## Paso 1: Refactorizar Controlador ✅
- Renombrar EntregaPedidoController a EntregaController
- Agregar parámetro 'tipo' (pedido/bodega) a métodos
- Hacer métodos genéricos que seleccionen modelos dinámicamente

## Paso 2: Refactorizar Vista ✅
- Renombrar entrega-pedido/index.blade.php a entrega/index.blade.php
- Hacer vista genérica con variables dinámicas ($titulo, $seccionCostura, etc.)

## Paso 3: Actualizar Rutas ✅
- Cambiar rutas de entrega-pedido a entrega con parámetro tipo
- Agregar rutas para entrega-bodega

## Paso 4: Actualizar Sidebar ✅
- Cambiar enlace de Entrega-Pedidos a usar tipo=pedido
- Actualizar enlace de Entrega Bodega a tipo=bodega

## Paso 5: Probar ✅
- Verificar ambas vistas funcionen correctamente
