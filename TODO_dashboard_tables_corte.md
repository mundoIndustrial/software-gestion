# TODO: Hacer dinámicas las tablas de Producción por Horas y Producción por Operarios

## Información Recopilada
- El archivo `dashboard-tables-corte.blade.php` actualmente usa datos dummy para las tablas "Producción por Horas" y "Producción por Operarios".
- Los datos deben provenir de la tabla `registro_piso_corte`, filtrados por fecha aplicada en el controlador.
- El controlador `TablerosController` ya filtra los registros por fecha y pasa `$registrosCorte` a la vista.
- Necesitamos calcular agregados por hora y por operario: suma de cantidad, suma de meta, y eficiencia = (sum cantidad / sum meta) * 100.

## Plan
1. Modificar `TablerosController@index` para calcular datos dinámicos de horas y operarios basados en `$registrosCorte` filtrados.
2. Pasar los arrays `$horasData` y `$operariosData` a la vista `tableros.blade.php`.
3. Actualizar `dashboard-tables-corte.blade.php` para usar los datos dinámicos en lugar de dummy.
4. Asegurar que los totales se calculen correctamente.

## Archivos a Editar
- `app/Http/Controllers/TablerosController.php`: Agregar lógica para calcular `$horasData` y `$operariosData`.
- `resources/views/components/dashboard-tables-corte.blade.php`: Reemplazar datos dummy con variables dinámicas.

## Pasos de Implementación
1. En `TablerosController`, después de obtener `$registrosCorte`, calcular:
   - `$horasData`: Agrupar por `hora_id`, sumar `cantidad` y `meta`, calcular eficiencia.
   - `$operariosData`: Agrupar por `operario_id`, sumar `cantidad` y `meta`, calcular eficiencia.
2. Pasar estos datos a la vista compact.
3. En la vista, usar `@foreach($horasData as $row)` en lugar de dummy.
4. Calcular totales dinámicamente en la vista.

## Seguimiento
- [x] Modificar TablerosController para calcular horasData y operariosData
- [x] Pasar datos a la vista
- [x] Actualizar dashboard-tables-corte.blade.php
- [ ] Probar con datos reales
