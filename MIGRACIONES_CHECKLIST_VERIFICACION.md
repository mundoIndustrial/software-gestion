# ✅ CHECKLIST DE VERIFICACIÓN FINAL

**Propósito**: Verificar que toda la migración se completó correctamente  
**Cuándo usar**: Después de ejecutar `php artisan migrate:procesos-prenda`  
**Duración**: ~5 minutos  
**Criticidad**: ALTA - Ejecutar SIEMPRE antes de considerar "completo"

---

## 📋 FASE 1: VERIFICACIÓN PRE-MIGRACIÓN

Antes de empezar, verifica estos items:

```
PRE-REQUISITOS
═════════════════════════════════════════════════════════════

□ Base de datos accesible
  Comando: mysql -u user -p -e "SELECT 1"
  Resultado esperado: 1

□ Tabla original existe
  Comando: mysql -u user -p -e "SELECT COUNT(*) FROM tabla_original"
  Resultado esperado: >1000 registros

□ Tablas nuevas existen (vacías o parciales)
  Comando: mysql -u user -p -e "SHOW TABLES LIKE 'pedidos%'"
  Resultado esperado: Ver tablas listadas

□ Terminal en directorio correcto
  Ubicación: c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial
  Verificar: dir | find "artisan"

□ PHP funciona
  Comando: php --version
  Resultado esperado: PHP 8.0+

□ Backup de BD realizado
  Verificar: Archivo .sql existe en carpeta segura
  Tamaño esperado: >50MB
```

---

## 📋 FASE 2: VERIFICACIÓN DURANTE MIGRACIÓN

Mientras se ejecuta la migración:

```
DURANTE php artisan migrate:procesos-prenda
═════════════════════════════════════════════════════════════

□ Output muestra 5 pasos claramente:
  ✓ Veo "PASO 1: Creando usuarios"
  ✓ Veo "PASO 2: Creando clientes"
  ✓ Veo "PASO 3: Migrando pedidos"
  ✓ Veo "PASO 4: Migrando prendas"
  ✓ Veo "PASO 5: Migrando procesos"

□ Cada paso muestra estadísticas:
  ✓ "X creados / Y existentes"
  ✓ "Z migrados / W saltados"

□ No hay mensajes de error críticos
  ✓ Sin "Fatal error"
  ✓ Sin "Undefined table"
  ✓ Sin "Connection refused"

□ Tiempo razonable
  ✓ Tarda entre 5-10 minutos
  ✓ NO se congela >2 minutos en un paso

□ Al final: "✅ MIGRACIÓN COMPLETA EXITOSA"
  ✓ Veo mensaje final de éxito
  ✓ Número de registros migrados visible
```

---

## 📋 FASE 3: VERIFICACIÓN POST-MIGRACIÓN INMEDIATA

Justo después de que finaliza la migración:

```
VERIFICACIONES BÁSICAS
═════════════════════════════════════════════════════════════

□ Contar usuarios creados
  Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM users WHERE role='asesora'"
  Resultado esperado: 51

□ Contar clientes creados
  Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM clientes"
  Resultado esperado: 965

□ Contar pedidos migrados
  Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM pedidos_produccion"
  Resultado esperado: 2,260

□ Contar prendas migradas
  Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM prendas_pedido"
  Resultado esperado: 2,906

□ Contar procesos migrados
  Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM procesos_prenda"
  Resultado esperado: 17,000+

□ Verificar estructura de JSON en prendas
  Comando: mysql -u user -p database -e "SELECT cantidad_talla FROM prendas_pedido LIMIT 1"
  Resultado esperado: JSON válido como {"XS": 5, "S": 10}

□ Verificar que no hay duplicados
  Comando: mysql -u user -p database -e "SELECT id, COUNT(*) FROM pedidos_produccion GROUP BY id HAVING COUNT(*) > 1"
  Resultado esperado: (vacío - sin resultados)
```

---

## 📋 FASE 4: EJECUTAR VALIDACIÓN COMPLETA

Este es el paso MÁS IMPORTANTE:

```
COMANDO VALIDACIÓN
═════════════════════════════════════════════════════════════

Ejecuta:
  php artisan migrate:validate

Debe mostrar:
  □ 📊 ESTADÍSTICAS DE MIGRACIÓN:
     - Usuarios: 51
     - Clientes: 965
     - Pedidos: 2,260
     - Prendas: 2,906
     - Procesos: 17,000

  □ 🔗 VERIFICACIÓN DE RELACIONES:
     - Pedidos sin asesor: ≤600 (heredado de datos viejos)
     - Pedidos sin cliente: ≤20 (heredado de datos viejos)
     - Prendas sin pedido: 0
     - Procesos sin prenda: 0

  □ ✅ INTEGRIDAD DE DATOS:
     - % Completeness: ≥75% (76.46% es perfecto)

  □ ✅ MIGRACIÓN VALIDADA EXITOSAMENTE
```

---

## 📋 FASE 5: VERIFICACIÓN DE DATOS ESPECÍFICOS

Validar muestras de datos migrados:

```
VERIFICAR EJEMPLO DE USUARIO
═════════════════════════════════════════════════════════════

Comando:
  mysql -u user -p database -e "SELECT id, name, email FROM users WHERE role='asesora' LIMIT 1"

Verificar:
  □ id: existe y es número
  □ name: tiene valor (no vacío)
  □ email: tiene formato email válido
  □ role: es 'asesora' o similar


VERIFICAR EJEMPLO DE CLIENTE
═════════════════════════════════════════════════════════════

Comando:
  mysql -u user -p database -e "SELECT id, nombre, ciudad FROM clientes LIMIT 1"

Verificar:
  □ id: existe y es número
  □ nombre: tiene valor (no vacío)
  □ ciudad: tiene valor (puede estar vacío, OK)


VERIFICAR EJEMPLO DE PEDIDO
═════════════════════════════════════════════════════════════

Comando:
  mysql -u user -p database -e "SELECT p.id, c.nombre as cliente, u.name as asesor, p.fecha_creacion FROM pedidos_produccion p LEFT JOIN clientes c ON p.cliente_id=c.id LEFT JOIN users u ON p.asesor_id=u.id LIMIT 5"

Verificar:
  □ id: número válido
  □ cliente: nombre visible (puede ser NULL, OK)
  □ asesor: nombre visible (puede ser NULL, OK)
  □ fecha_creacion: fecha válida

  Nota: Si alguno es NULL, es heredado de tabla_original, es NORMAL


VERIFICAR EJEMPLO DE PRENDA
═════════════════════════════════════════════════════════════

Comando:
  mysql -u user -p database -e "SELECT id, nombre_prenda, cantidad_talla FROM prendas_pedido LIMIT 5"

Verificar:
  □ id: número válido
  □ nombre_prenda: texto (puede ser largo, OK)
  □ cantidad_talla: JSON válido con tallas y cantidades
    Ejemplo: {"XS": 5, "S": 10, "M": 15, "L": 8, "XL": 3}


VERIFICAR EJEMPLO DE PROCESO
═════════════════════════════════════════════════════════════

Comando:
  mysql -u user -p database -e "SELECT id, proceso, estado_proceso, fecha_inicio FROM procesos_prenda LIMIT 5"

Verificar:
  □ id: número válido
  □ proceso: una de: Creación, Corte, Costura, QC, Envío, etc.
  □ estado_proceso: una de: Pendiente, En Progreso, Completado, Pausado
  □ fecha_inicio: fecha válida (puede ser NULL, OK)
```

---

## 📋 FASE 6: VERIFICACIÓN DE INTEGRIDAD

Pruebas de relaciones y referencias:

```
VERIFICAR FOREIGN KEYS (Relaciones)
═════════════════════════════════════════════════════════════

1. Todos los pedidos tienen asesor_id válido (o NULL)
   Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM pedidos_produccion WHERE asesor_id IS NOT NULL AND asesor_id NOT IN (SELECT id FROM users)"
   Resultado esperado: 0

2. Todos los pedidos tienen cliente_id válido (o NULL)
   Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM pedidos_produccion WHERE cliente_id IS NOT NULL AND cliente_id NOT IN (SELECT id FROM clientes)"
   Resultado esperado: 0

3. Todas las prendas pertenecen a pedido válido
   Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM prendas_pedido WHERE pedido_id NOT IN (SELECT id FROM pedidos_produccion)"
   Resultado esperado: 0

4. Todos los procesos pertenecen a prenda válida
   Comando: mysql -u user -p database -e "SELECT COUNT(*) FROM procesos_prenda WHERE prenda_id NOT IN (SELECT id FROM prendas_pedido)"
   Resultado esperado: 0

✅ Si TODOS dan "0", integridad = PERFECTA
```

---

## 📋 FASE 7: VERIFICACIÓN EN APLICACIÓN

Probar en la interfaz web:

```
TEST EN BROWSER
═════════════════════════════════════════════════════════════

□ Acceder a: http://localhost/asesores/pedidos-produccion
  ✓ Carga sin errores
  ✓ Ve lista de pedidos (2,260+)
  ✓ Cada pedido muestra datos completos

□ Hacer clic en un pedido
  ✓ Ve detalles del pedido
  ✓ Ve prendas del pedido (con tallas en JSON)
  ✓ Ve procesos del pedido

□ Crear un nuevo pedido
  ✓ Selecciona cliente (965 opciones)
  ✓ Selecciona asesor (51 opciones)
  ✓ Guardá sin errores
  ✓ Redirige a lista de pedidos
  ✓ Ve toast "Creado exitosamente" (SweetAlert2)

□ Ver reportes (si existen)
  ✓ Reportes de prendas: Cargan datos
  ✓ Reportes de procesos: Cargan datos
  ✓ Queries complejas: Sin errores de DB

```

---

## 📋 FASE 8: VERIFICACIÓN DE ERRORES

Buscar problemas comunes:

```
ERRORES A REVISAR
═════════════════════════════════════════════════════════════

□ NO debería haber:
  ✗ "Duplicate entry" - indicaría registros duplicados
  ✗ "Foreign key constraint" - indicaría relaciones rotas
  ✗ "Syntax error" - indicaría SQL malformado
  ✗ "Access denied" - indicaría permisos de BD
  ✗ "Unknown column" - indicaría estructura incorrecta

□ Revisar logs:
  Comando: tail -f storage/logs/laravel.log
  Buscar: "error", "exception", "failed"

□ Si encuentras errores:
  ✓ Ejecuta: php artisan migrate:fix-errors
  ✓ Luego: php artisan migrate:validate
  ✓ Revisa si se corrigieron

□ Si persisten errores:
  ✓ Ejecuta: php artisan migrate:procesos-prenda --reset
  ✓ Restaura backup
  ✓ Contacta a soporte
```

---

## 📋 FASE 9: VERIFICACIÓN DE PERFORMANCE

Asegurar que la migración no degradó performance:

```
QUERIES DE PERFORMANCE
═════════════════════════════════════════════════════════════

□ Contar tiempo de query simple
  Comando: time mysql -u user -p database -e "SELECT COUNT(*) FROM pedidos_produccion"
  Resultado esperado: <1 segundo

□ Join entre 3 tablas (pedido-prenda-proceso)
  Comando: time mysql -u user -p database -e "SELECT COUNT(*) FROM pedidos_produccion p JOIN prendas_pedido pr ON p.id=pr.pedido_id JOIN procesos_prenda proc ON pr.id=proc.prenda_id"
  Resultado esperado: <5 segundos

□ Índices existen
  Comando: mysql -u user -p database -e "SHOW INDEXES FROM pedidos_produccion"
  Resultado esperado: Ver índices listados

□ Tamaño de tablas razonable
  Comando: mysql -u user -p database -e "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB' FROM information_schema.TABLES WHERE table_schema='mundoindustrial'"
  Resultado esperado: Tamaño total <500MB
```

---

## 📋 FASE 10: CHECKLIST FINAL

Resumen de verificación completa:

```
RESUMEN DE VERIFICACIÓN
═════════════════════════════════════════════════════════════

Migración ejecutada: ✓ □
Validación de datos: ✓ □
Verificación en BD: ✓ □
Verificación en web: ✓ □
Performance OK: ✓ □
Sin errores críticos: ✓ □
Integridad de datos: ✓ □
Completeness ≥75%: ✓ □
Backup actualizado: ✓ □

RESULTADO FINAL:

  ✅ TODO OK - LISTO PARA PRODUCCIÓN
  ⚠️  REVISAR DETALLES - Ver seción de errores
  ❌ FALLÓ - Ejecutar migrate:procesos-prenda --reset

Fecha de verificación: _______________
Verificado por: _______________
Firma: _______________
```

---

## 🆘 SI ALGO FALLA

Si encuentras problemas en cualquier fase:

```
PASO 1: Identifica dónde falló
  ✓ ¿Durante migración?
  ✓ ¿Durante validación?
  ✓ ¿En la aplicación?
  ✓ ¿En performance?

PASO 2: Consulta documentación
  ✓ MIGRACIONES_DOCUMENTACION.md → sección "Troubleshooting"
  ✓ MIGRACIONES_COMANDOS_RAPIDOS.md → tabla de errores

PASO 3: Intenta corregir
  ✓ Ejecuta: php artisan migrate:fix-errors
  ✓ Luego: php artisan migrate:validate
  ✓ Revisa si se solucionó

PASO 4: Si persiste
  ✓ Ejecuta: php artisan migrate:procesos-prenda --reset
  ✓ Restaura backup de BD
  ✓ Intenta nuevamente
  ✓ Contacta a soporte si continúa

PASO 5: Documentar
  ✓ Anota qué falló
  ✓ Qué comando ejecutaste
  ✓ Qué resultado obtuviste
  ✓ Comparte con equipo de soporte
```

---

## 📊 TABLA DE REFERENCIA RÁPIDA

| Verificación | Comando | Resultado Esperado |
|---|---|---|
| Usuarios | `SELECT COUNT(*) FROM users` | 51 |
| Clientes | `SELECT COUNT(*) FROM clientes` | 965 |
| Pedidos | `SELECT COUNT(*) FROM pedidos_produccion` | 2,260 |
| Prendas | `SELECT COUNT(*) FROM prendas_pedido` | 2,906 |
| Procesos | `SELECT COUNT(*) FROM procesos_prenda` | 17,000+ |
| Duplicados | GROUP BY con HAVING | 0 resultados |
| FK rotas | WHERE id NOT IN | 0 resultados |
| Completeness | migrate:validate | ≥75% |

---

## ⏱️ TIEMPO ESTIMADO

- Fase 1 (Pre-migración): 2 minutos
- Fase 2 (Durante): 7 minutos (automático)
- Fase 3 (Inmediata): 1 minuto
- Fase 4 (Validación): 1 minuto
- Fase 5 (Específica): 3 minutos
- Fase 6 (Integridad): 2 minutos
- Fase 7 (Aplicación): 5 minutos
- Fase 8 (Errores): 2 minutos
- Fase 9 (Performance): 2 minutos
- Fase 10 (Final): 1 minuto

**TOTAL: ~26 minutos**

---

## 📝 REGISTRO DE VERIFICACIÓN

Copia y completa esto después de ejecutar:

```
REGISTRO OFICIAL DE MIGRACIÓN
═════════════════════════════════════════════════════════════

Fecha de migración: _______________
Hora inicio: _______________ Hora fin: _______________
Ejecutado por: _______________
Ambiente: [ ] DEV [ ] STAGING [ ] PRODUCCIÓN

RESULTADOS:
  Usuarios creados: _______________
  Clientes creados: _______________
  Pedidos migrados: _______________
  Prendas migradas: _______________
  Procesos migrados: _______________
  Completeness: _______________

VERIFICACIONES:
  □ Dry-run validado
  □ Migración completada
  □ Validación ejecutada (0 errores)
  □ Datos verificados en BD
  □ Tests en aplicación pasados
  □ Performance aceptable

PROBLEMAS ENCONTRADOS:
  _______________
  _______________
  _______________

SOLUCIONES APLICADAS:
  _______________
  _______________
  _______________

OBSERVACIONES:
  _______________
  _______________
  _______________

ESTADO FINAL:
  [ ] ✅ Éxito - Listo para producción
  [ ] ⚠️  Con advertencias - Revisar notas
  [ ] ❌ Fallo - Necesita revertir

Aprobado por: _______________
Fecha de aprobación: _______________
```

---

**Versión**: 1.0  
**Última actualización**: 26 de Noviembre de 2025  
**Criticidad**: ALTA - Ejecutar siempre  
**Status**: ✅ Listo para usar
