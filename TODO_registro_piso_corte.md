# TODO: Refactorizar tabla registro_piso_corte

## Pasos a completar:

1. **Crear migración para tabla 'horas'**: Crear tabla con id, hora (1-12), rango (ej. 08:00am - 09:00am).

2. **Crear migración para tabla 'maquinas'**: Crear tabla con id, nombre_maquina, otros campos necesarios.

3. **Crear migración para tabla 'telas'**: Crear tabla con id, nombre_tela, otros campos necesarios.

4. **Crear migración para tabla 'tiempo_ciclos'**: Crear tabla pivot entre telas y maquinas con tiempo_ciclo.

5. **Crear migración para alterar 'registro_piso_corte'**: Cambiar columnas 'hora', 'cortador', 'maquina', 'tela' a foreign keys (hora_id, operario_id, maquina_id, tela_id). Agregar foreign key constraints.

6. **Actualizar modelo RegistroPisoCorte**: Agregar relaciones belongsTo para hora, operario (User), maquina, tela. Relación belongsToMany o hasOne para tiempo_ciclo.

7. **Actualizar TablerosController**: Modificar métodos para manejar nuevos campos, fetch data relacionada, validar foreign keys.

8. **Actualizar form_modal_piso_corte.blade.php**: Cambiar inputs a selects para hora, operario, maquina, tela. Agregar autocomplete para tela con opción de crear nueva. Auto-fill tiempo_ciclo al seleccionar tela y maquina.

9. **Actualizar tableros.js**: Agregar lógica JavaScript para autocomplete de tela (buscar coincidencias, permitir crear nueva), y auto-fill tiempo_ciclo via AJAX.

10. **Crear seeders**: Seed data inicial para horas (1-12 con rangos), maquinas, telas, tiempo_ciclos.

11. **Probar funcionalidad**: Ejecutar migraciones, probar formulario, verificar relaciones y lógica de auto-fill.

12. **Implementar cálculos correctos para piso de corte**: Actualizar lógica de cálculo de tiempo_disponible, meta y eficiencia considerando paradas programadas, tiempo extendido y tiempo trazado.

13. **Agregar visualización diferenciada para actividad "Extender/Trazar"**: Agregar clase CSS para resaltar filas con actividad "Extender/Trazar" en la tabla de corte.

## Progreso:
- [x] Paso 1
- [x] Paso 2
- [x] Paso 3
- [x] Paso 4
- [x] Paso 5
- [x] Paso 6
- [x] Paso 7
- [x] Paso 8
- [x] Paso 9
- [x] Paso 10
- [x] Paso 11
- [x] Paso 12
- [x] Paso 13
