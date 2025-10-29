# TODO: Agregar filtro dinámico a dashboard-tables-corte.blade.php

## Objetivo
Implementar funcionalidad de filtro dinámico para las tablas de producción por horas y operarios en dashboard-tables-corte.blade.php sin recargar la página.

## Información Recopilada
- El componente dashboard-tables-corte.blade.php ya incluye el filtro de fechas.
- El controlador TablerosController.php calcula horasData y operariosData basados en registrosCorte filtrados.
- Las tablas muestran datos de producción por horas y operarios con totales.

## Plan
1. Agregar nueva ruta en routes/web.php para obtener datos filtrados de dashboard tables.
2. Agregar método getDashboardTablesData en TablerosController.php que devuelva horasData y operariosData en JSON.
3. Modificar dashboard-tables-corte.blade.php para agregar JavaScript que actualice las tablas dinámicamente al aplicar el filtro.
4. Asegurar que el filtro no afecte otros componentes.

## Archivos a modificar
- routes/web.php: Nueva ruta GET /tableros/dashboard-tables-data
- app/Http/Controllers/TablerosController.php: Nuevo método getDashboardTablesData()
- resources/views/components/dashboard-tables-corte.blade.php: Agregar JavaScript para filtro dinámico

## Dependencias
- No nuevas dependencias, usar fetch API existente.

## Seguimiento
- [x] Agregar ruta en routes/web.php
- [x] Implementar método getDashboardTablesData en controlador
- [x] Modificar top-controls.blade.php para actualizar dashboard-tables-corte.blade.php dinámicamente
- [x] Probar funcionalidad sin afectar otros filtros
