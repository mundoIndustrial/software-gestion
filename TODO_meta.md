# TODO: Implementar fórmula de Meta en Tableros

## Tareas Pendientes
- [x] Modificar el método `store` en `TablerosController` para calcular automáticamente `meta` al guardar nuevos registros.
- [x] Modificar el método `update` en `TablerosController` para recalcular automáticamente `meta` al editar celdas dependientes (tiempo_disponible, tiempo_ciclo).
- [ ] Probar la edición de celdas en las tablas de "piso produccion" y "piso polo" para verificar que la fórmula de Meta se aplique en tiempo real.

## Información Técnica
- Fórmula: (tiempo_disponible / tiempo_ciclo) * 0.9
- Campos dependientes: tiempo_disponible, tiempo_ciclo
- Archivos afectados: app/Http/Controllers/TablerosController.php
