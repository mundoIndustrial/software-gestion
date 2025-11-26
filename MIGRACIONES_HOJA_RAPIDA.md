# 📋 MIGRACIONES - HOJA DE REFERENCIA VISUAL

## 🚀 INICIAR MIGRACIÓN EN 3 PASOS

```
PASO 1: SIMULAR              PASO 2: EJECUTAR            PASO 3: VALIDAR
─────────────────────────────────────────────────────────────────────────

php artisan                  php artisan                  php artisan
migrate:procesos-prenda      migrate:procesos-prenda      migrate:validate
--dry-run                    

      ↓ (2-3 min)                   ↓ (5-10 min)              ↓ (1 min)
      
Ver qué se hará           Migrar 22K registros         Ver estadísticas
Sin cambios reales              ↓
Revisar output           ✅ 51 usuarios
                         ✅ 965 clientes
                         ✅ 2,260 pedidos
                         ✅ 2,906 prendas
                         ✅ 17,000 procesos

                         ¿Errores? → php artisan migrate:fix-errors
```

---

## 📊 TRANSFORMACIÓN DE DATOS

```
TABLA ORIGINAL (Vieja)              →    TABLAS NUEVAS (Normalizadas)
═══════════════════════════════════════════════════════════════════════

tabla_original                           users (asesoras)
├─ 2,260 filas                          ├─ 51 registros
├─ 50+ campos                           └─ id, name, email, etc.
├─ Datos mezclados                      
└─ Difícil de mantener              clientes
                                    ├─ 965 registros
registros_por_orden                 └─ id, nombre, email, etc.
├─ 2,906 filas                      
├─ Prendas por orden                pedidos_produccion
└─ Cantidad por talla               ├─ 2,260 registros
                                    ├─ id, cliente_id, asesor_id, etc.
                                    └─ Estructurado

                                    prendas_pedido
                                    ├─ 2,906 registros
                                    ├─ id, pedido_id, cantidad_talla (JSON)
                                    └─ Relación clara

                                    procesos_prenda
                                    ├─ 17,000 registros
                                    ├─ Tipo: Corte, Costura, QC, etc.
                                    └─ Con fechas y responsables
```

---

## ⚡ COMANDOS AL INSTANTE

```bash
# ▶️ PRIMERO SIEMPRE
php artisan migrate:procesos-prenda --dry-run

# ✨ EJECUTAR REAL (si --dry-run está OK)
php artisan migrate:procesos-prenda

# ✔️ VERIFICAR
php artisan migrate:validate

# 🔧 SI HAY ERRORES
php artisan migrate:fix-errors
php artisan migrate:validate

# ↩️ SI ALGO VA MAL
php artisan migrate:procesos-prenda --reset
# Restaurar backup BD si es necesario
```

---

## 📈 NÚMEROS CLAVE

| Métrica | Valor | Status |
|---------|-------|--------|
| Usuarios creados | 51 | ✅ |
| Clientes creados | 965 | ✅ |
| Pedidos migrados | 2,260 | ✅ |
| Prendas migradas | 2,906 | ✅ |
| Procesos migrados | 17,000 | ✅ |
| **TOTAL** | **22,182** | ✅ |
| Completeness | 76.46% | ✅ Aceptable |
| Errores | 0 | ✅ |

---

## 📁 ARCHIVOS CLAVE

```
app/Console/Commands/
├─ MigrateProcessesToProcesosPrend.php ← EJECUTA MIGRACIÓN
├─ ValidateMigration.php              ← VALIDA DATOS
├─ FixMigrationErrors.php             ← CORRIGE ERRORES
└─ RollbackProcessesMigration.php     ← REVIERTE

database/migrations/
└─ 2025_11_26_expand_nombre_prenda_field.php ← EXPANDE CAMPO

Documentación/
├─ MIGRACIONES_INDICE.md              ← COMIENZA AQUÍ
├─ MIGRACIONES_GUIA_PASO_A_PASO.md   ← INSTRUCCIONES
├─ MIGRACIONES_COMANDOS_RAPIDOS.md   ← REFERENCIAS
├─ MIGRACIONES_DOCUMENTACION.md      ← TÉCNICO
├─ MIGRACIONES_REFERENCIA_RAPIDA.md  ← RESUMEN
└─ MIGRACIONES_HOJA_RAPIDA.md        ← ESTE ARCHIVO
```

---

## 🎯 MATRIZ RÁPIDA DE DECISIÓN

```
¿Qué necesitas?              ¿Qué haces?
═════════════════════════════════════════════════════════════

Migrar por primera vez       → migrate:procesos-prenda --dry-run
                             → migrate:procesos-prenda
                             → migrate:validate

Ver qué va a pasar           → migrate:procesos-prenda --dry-run

Ejecutar de verdad           → migrate:procesos-prenda

Verificar si funcionó        → migrate:validate

Hay errores                  → migrate:fix-errors
                             → migrate:validate

Revertir todo                → migrate:procesos-prenda --reset

Entender cómo funciona       → Lee MIGRACIONES_DOCUMENTACION.md

Necesito comando rápido      → Lee MIGRACIONES_COMANDOS_RAPIDOS.md

Ver paso a paso              → Lee MIGRACIONES_GUIA_PASO_A_PASO.md
```

---

## 🔥 FLUJO DE EJECUCIÓN VISUALIZADO

```
╔═══════════════════════════════════════════════════════════════════╗
║                     MIGRACIÓN EN ACCIÓN                          ║
╚═══════════════════════════════════════════════════════════════════╝

1. PRE-MIGRACIÓN
   ✅ BD conectada
   ✅ Tabla original con datos
   ✅ Tablas nuevas vacías

2. DRY-RUN (simulate)
   ┌─────────────────────────┐
   │ Validar estructura       │
   │ Verificar datos          │  → No modifica nada
   │ Mostrar qué haría        │
   └─────────────────────────┘

3. MIGRACIÓN (real)
   ┌─────────────────────────┐
   │ Paso 1: Usuarios        │ → 51 users creados
   ├─────────────────────────┤
   │ Paso 2: Clientes        │ → 965 clientes creados
   ├─────────────────────────┤
   │ Paso 3: Pedidos         │ → 2,260 pedidos migrados
   ├─────────────────────────┤
   │ Paso 4: Prendas         │ → 2,906 prendas migradas
   ├─────────────────────────┤
   │ Paso 5: Procesos        │ → 17,000 procesos migrados
   └─────────────────────────┘

4. VALIDACIÓN
   ┌─────────────────────────┐
   │ Contar registros        │
   │ Verificar relaciones    │
   │ Mostrar estadísticas    │
   └─────────────────────────┘

5. POST-MIGRACIÓN
   ✅ Datos migrados correctamente
   ✅ Integridad verificada (76.46%)
   ✅ Sistema listo para usar
```

---

## ⚠️ ADVERTENCIAS IMPORTANTES

```
🚨 ANTES DE EJECUTAR:
   ✓ Backup de BD realizado y verificado
   ✓ Leído MIGRACIONES_GUIA_PASO_A_PASO.md
   ✓ Probado con --dry-run primero
   ✓ Conexión a BD funcionando

⚠️ DURANTE LA EJECUCIÓN:
   × NO cierres la terminal
   × NO interrumpas el proceso (Ctrl+C)
   × NO modifiques la BD mientras ejecuta
   × NO apagues la computadora

✅ DESPUÉS DE LA EJECUCIÓN:
   → Ejecuta migrate:validate para confirmar
   → Verifica datos en BD
   → Prueba UI con datos reales
   → Guarda backup de datos migrados
```

---

## 📊 TABLA DE PROCESOS MIGRADOS

```
Tipo de Proceso          Código    Registros
────────────────────────────────────────────
Creación de Orden        CREO        X
Corte                    CORE        X
Preparación              PREP        X
Costura                  COST        X
Revisión                 REVI        X
Control de Calidad       CCAL        X
Revisión Final           RFIN        X
Empaque                  EMPA        X
Envío                    ENVI        X
Entrega                  ENTE        X
Devolución               DEVO        X
Almacenamiento           ALMA        X
Otro                     OTRO        X
                                   ─────
                        TOTAL:   17,000
```

---

## 🎓 GLOSARIO RÁPIDO

| Término | Significa | Ej |
|---------|-----------|-----|
| **Dry-run** | Simular sin cambios | `--dry-run` |
| **Reset** | Deshacer y volver atrás | `--reset` |
| **Validate** | Verificar integridad | `migrate:validate` |
| **Completeness** | % de datos con todo | 76.46% OK |
| **Foreign Key** | Relación a otra tabla | pedido_id → pedidos |
| **JSON** | Formato de datos | `{"XS": 5, "S": 10}` |
| **Normalización** | Organizar datos | tabla grande → varias |

---

## 🔗 DOCUMENTACIÓN RELACIONADA

```
Necesitas...                              Lee...
═══════════════════════════════════════════════════════════════

Una guía paso a paso                      MIGRACIONES_GUIA_PASO_A_PASO.md
Entender toda la arquitectura             MIGRACIONES_DOCUMENTACION.md
Referencia técnica completa               MIGRACIONES_DOCUMENTACION.md
Comandos disponibles                      MIGRACIONES_COMANDOS_RAPIDOS.md
Resumen ejecutivo                         MIGRACIONES_REFERENCIA_RAPIDA.md
Un índice para orientarme                 MIGRACIONES_INDICE.md
Hoja de referencia rápida                 ESTE ARCHIVO
```

---

## ✨ ÚLTIMO CHECKLIST

Antes de presionar Enter en cualquier comando:

```
☐ ¿Leo la documentación adecuada?
☐ ¿Hice backup de la BD?
☐ ¿Estoy en el directorio correcto? (proyecto\v10\mundoindustrial)
☐ ¿La BD está accesible?
☐ ¿Tengo tiempo para esperar 5-10 minutos?
☐ ¿Probé con --dry-run primero?
☐ ¿Entiendo qué hace el comando que voy a ejecutar?

SI A TODO ✅ → Adelante, ejecuta
SI NO ALGO → Lee documentación primero
```

---

**Última actualización**: 26 de Noviembre de 2025  
**Versión**: 1.0  
**Tipo**: Hoja de referencia visual rápida  
**Estado**: ✅ Lista para usar
