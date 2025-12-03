# 🎯 MATRIZ DE DECISIONES Y PREGUNTAS CLAVE

**Objetivo:** Aclarar decisiones pendientes antes de empezar el refactor

---

## ❓ PREGUNTA #1: ¿CUÁL ES LA VERSIÓN CORRECTA DE ORDERS-TABLE?

### Situación Actual
```
public/js/orders js/
├── orders-table.js      (Antigua, 2,300+ líneas, monolítica)
├── orders-table-v2.js   (Nueva, modular con modules/)
└── modules/             (9 módulos especializados)
    ├── rowManager.js
    ├── filterManager.js
    └── ... (7 más)
```

### La Pregunta
**¿Cuál está siendo usada realmente en producción?**

### Cómo Investigar

```bash
# 1. Ver qué templates cargan cada archivo
grep -r "orders-table.js" resources/views/ | grep -v "v2"
# Si hay resultados = orders-table.js se está usando

grep -r "orders-table-v2.js" resources/views/
# Si hay resultados = orders-table-v2.js se está usando

# 2. Ver en Chrome DevTools
# Ir a Network tab
# Filtrar por "orders-table"
# Ver qué archivo se descarga
```

### Decisión a Tomar

| Escenario | Decisión | Acción |
|-----------|----------|--------|
| Se usa **orders-table.js** | Mantener versión antigua | Refactorizar ese archivo en lugar de v2 |
| Se usa **orders-table-v2.js** | Mantener versión nueva | Eliminar orders-table.js, mantener v2 |
| Se usan **AMBAS** | Consolidar | Decide cuál es mejor, depreca la otra |
| No se sabe | Investigar | Ver headers HTTP, verificar qué funciona |

### Mi Recomendación

```
Basándome en los documentos encontrados (REFACTORIZACION-MODERN-TABLE-SOLID.md):
✅ orders-table-v2.js + modules/ es la versión MODERNA
❌ orders-table.js parece ser versión antigua

DECISIÓN SUGERIDA:
- Mantener: orders-table-v2.js + modules/
- Eliminar: orders-table.js
- Verificar: Que todas las templates usen v2
```

---

## ❓ PREGUNTA #2: ¿PUEDO ELIMINAR TABLAS ANTIGUAS DESPUÉS DEL REFACTOR?

### Situación
```sql
Actual:
├── registro_piso_produccion   (datos existentes)
├── registro_piso_polo         (datos existentes)
└── registro_piso_corte        (datos existentes)

Propuesto:
└── registro_piso              (tabla nueva)
    └── (datos migramos aquí)
```

### La Pregunta
**¿Debo eliminar `registro_piso_produccion`, `registro_piso_polo`, `registro_piso_corte`?**

### Opciones

**OPCIÓN A: Eliminar inmediatamente (Alto riesgo)**
```
PRO:
✓ BD más limpia
✓ Menos redundancia

CON:
✗ Si algo falla, recuperación difícil
✗ Reversión complicada
✗ Riesgo de perder datos

RECOMENDACIÓN: NO hacer esto inmediatamente
```

**OPCIÓN B: Mantener temporalmente (Bajo riesgo) ⭐**
```
PRO:
✓ Fallback si algo falla
✓ Fácil de revertir
✓ Seguro

CON:
✗ BD con 2 sistemas en paralelo (confusión)
✗ Más almacenamiento

RECOMENDACIÓN: Esta es la mejor opción
Pasos:
1. Crear registro_piso (nueva)
2. Mantener antiguas por 2 semanas
3. Migrar datos gradualmente
4. Verificar que todo funciona
5. Crear backup de tablas antiguas
6. RECIÉN ENTONCES: Eliminar
```

**OPCIÓN C: Mantener para siempre**
```
PRO:
✓ Cero riesgo
✓ Compatibilidad 100%

CON:
✗ Deuda técnica permanente
✗ Confusión futura

RECOMENDACIÓN: Solo si hay dependencias externas
(APIs, otros sistemas, clientes que consultan directamente)
```

### Mi Recomendación

```
PLAN PROPUESTO:

Semana 1:
✓ Crear tabla registro_piso
✓ Crear Model RegistroPiso
✓ Mantener tablas antiguas sin cambios

Semana 2:
✓ Migrar datos a registro_piso
✓ Cambiar queries para usar registro_piso
✓ Mantener tablas antiguas sin cambios

Semana 3:
✓ Verificar que todo funciona 1 semana completa
✓ Hacer backup de tablas antiguas
✓ Hacer backup de BD completa

Semana 4:
✓ Si todo está 100% OK
✓ Crear migración para eliminar tablas antiguas
✓ Ejecutar elimación

RESULTADO: 0 riesgo + datos seguros + reversión posible
```

---

## ❓ PREGUNTA #3: ¿TENGO DEADLINE PARA ESTO?

### La Pregunta
**¿Hay una fecha límite para completar el refactor?**

### Por Qué Importa
```
SIN DEADLINE:
- Puedo hacer cambios pequeños durante 5-6 semanas
- Bajo riesgo
- Sin presión

CON DEADLINE (ej: 2 semanas):
- Necesito priorizar lo más urgente
- Cambios más drásticos
- Mayor riesgo

CON DEADLINE (ej: 1 semana):
- Solo hacer lo imprescindible
- No tocar cosas que funcionen
- Máximo: Services + tabla unificada
```

### Mi Sugerencia

```
TIMELINE RECOMENDADO:

Ideal (Sin presión):        5 semanas completas
                           ↓
Aceptable (Con presión):   3 semanas (solo lo crítico)
                           ↓
Emergencia (Muy presión):  1 semana (foundation solamente)
```

### Escenarios

**Si tienes 5 semanas:**
```
✅ Plan de 5 semanas completo
✅ Cambios graduales
✅ Tests exhaustivos
✅ Documentación completa
```

**Si tienes 2 semanas:**
```
⚠️ Solo semana 1 del plan
⚠️ Services básicos
⚠️ Tabla BD unificada
⚠️ Sin consolidación frontend

PARA HACER DESPUÉS:
- Consolidar frontend
- Dividir controllers
- Tests avanzados
```

**Si tienes 1 semana:**
```
🔴 Cambios mínimos solamente
⚠️ Solo Services
⚠️ SIN cambios en Controllers
⚠️ SIN cambios en BD

RAZÓN: Riesgo muy alto en poco tiempo
MEJOR: Esperar a tener más tiempo
```

---

## ❓ PREGUNTA #4: ¿QUIÉN VA A TESTEAR ESTO?

### La Pregunta
**¿Tengo alguien para testing manual después de cada cambio?**

### Por Qué Importa

| Escenario | Impacto | Acción |
|-----------|---------|--------|
| **Yo mismo testo** | Lento pero seguro | Plan de 5 semanas OK |
| **Otro dev testea** | Más rápido | Plan de 3 semanas OK |
| **QA/Tester dedicado** | Muy rápido | Plan de 2 semanas OK |
| **Sin testing** | ❌ PROBLEMA | Esperar a tener recurso |

### Testing Checklist

Después de cada día, verificar:

```bash
# ✓ Logs sin errores
tail -f storage/logs/laravel.log

# ✓ Páginas cargan
curl http://localhost/tableros
curl http://localhost/ordenes

# ✓ Datos se ven
# Abrir en navegador manualmente

# ✓ Filtros funcionan
# Hacer filter en UI, verificar resultados

# ✓ Crear datos
# Crear un registro nuevo, verificar que se persiste

# ✓ Actualizar datos
# Editar un registro, verificar cambios

# ✓ Performance
# Medir tiempo carga antes/después
```

### Mi Recomendación

```
TESTING PLAN:

OPCIÓN A (Recomendada):
- Yo mismo: 15 min diarios (checklist rápido)
- Otro dev: 1 hora semanal (testing exhaustivo)
- Total: 2 horas/semana

OPCIÓN B (Si estoy solo):
- Yo mismo: 30 min diarios
- Plan se extiende a 6 semanas
- Pero es más seguro

OPCIÓN C (Mínimo):
- Yo mismo: 10 min diarios (solo checks críticos)
- Plan de 5 semanas + verificación extra

HERRAMIENTAS RECOMENDADAS:
✓ Chrome DevTools (Network, Console)
✓ Laravel Debugbar
✓ logs archivo storage/logs/laravel.log
✓ MySQL Workbench (para ver BD)
```

---

## ❓ PREGUNTA #5: ¿PUEDO REVERTIR CAMBIOS SI FALLA ALGO?

### La Pregunta
**¿Qué hago si los cambios rompen el sistema?**

### La Respuesta Corta

```
✅ SÍ, puedes revertir CUALQUIER cambio
   Tiene cobertura 100% de rollback
```

### Plan de Rollback

```
POR COMPONENTE:

1. Backend Services (App\Services)
   Rollback: rm -rf app/Services/* (restaurar desde git)
   Riesgo: BAJO
   Tiempo: 2 min

2. Controllers (cambios en TablerosController)
   Rollback: git checkout app/Http/Controllers/TablerosController.php
   Riesgo: BAJO
   Tiempo: 2 min

3. Models (métodos nuevos)
   Rollback: git checkout app/Models/
   Riesgo: BAJO
   Tiempo: 2 min

4. Base de Datos (tabla nueva)
   Rollback: php artisan migrate:rollback
   Riesgo: BAJO (si solo creó tabla)
   Tiempo: 1 min
   IMPORTANTE: Hacer backup ANTES

5. Frontend JS
   Rollback: git checkout public/js/
   Riesgo: BAJO
   Tiempo: 2 min
```

### Backup Strategy

**ANTES de empezar semana 1:**

```bash
# 1. Backup completo de BD
mysqldump -u usuario -p BD_name > backup_20250103.sql

# 2. Backup de código
git checkout -b backup/pre-refactor
git push origin backup/pre-refactor

# 3. En git, crear rama para refactor
git checkout -b feature/refactor-week-1
```

**Si algo falla:**

```bash
# Revertir código
git checkout main
git pull

# Restaurar BD
mysql -u usuario -p BD_name < backup_20250103.sql
```

### Mi Recomendación

```
SAFETY FIRST:

✅ Hacer backup de BD antes de empezar
✅ Trabajar en rama de git (feature/refactor-week-1)
✅ NO pushear a main hasta que todo funcione
✅ Verificar después de cada paso
✅ Keep original files commentaded, no borrados

RESULTADO: 0 riesgo, reversión instantánea posible
```

---

## ❓ PREGUNTA #6: ¿CONVIENE HACER TODO O SOLO LO PRIORITARIO?

### Situación

Existen 5 problemas identificados:

| # | Problema | Importancia | Dificultad | Tiempo |
|---|----------|-------------|-----------|--------|
| 1 | God Object Controller | 🔴 CRÍTICA | Medio | 2 sem |
| 2 | Tablas duplicadas | 🔴 CRÍTICA | Bajo | 1 sem |
| 3 | JS duplicado | 🔴 CRÍTICA | Medio | 1 sem |
| 4 | Models anémicos | 🟠 MEDIA | Bajo | 3 días |
| 5 | Sin Service Layer | 🟠 MEDIA | Bajo | 3 días |

### La Pregunta

**¿Debo refactor TODOS o solo algunos?**

### Opciones

**OPCIÓN A: Todo (5 semanas)**
```
✅ Software completamente mejorado
✅ 80% deuda técnica eliminada
❌ Mucho tiempo
❌ Mayor riesgo

RECOMENDACIÓN: Si tienes tiempo y recursos
```

**OPCIÓN B: Solo lo crítico (3 semanas)**
```
✅ 60% deuda técnica eliminada
✅ Menos tiempo
✅ Menor riesgo
❌ Queda trabajo futuro

RECOMENDACIÓN: Balance entre beneficio y riesgo
```

**OPCIÓN C: Mínimo viables (2 semanas)**
```
✅ 40% deuda técnica eliminada
✅ Base para futuro refactor
❌ No resuelve todo

RECOMENDACIÓN: Si hay mucha presión de tiempo
```

### Mi Recomendación (TOP PRIORITY)

```
PRIORIDAD 1 - Hacer PRIMERO (Semana 1-2):
├─ Problem #2: Tablas duplicadas → BD unificada
├─ Problem #5: Sin Service Layer → Crear Services
└─ IMPACTO: +40% mejora, bajo riesgo

PRIORIDAD 2 - Hacer DESPUÉS (Semana 3):
├─ Problem #1: God Object → Dividir Controller
├─ Problem #4: Models anémicos → Agregar métodos
└─ IMPACTO: +30% mejora

PRIORIDAD 3 - Hacer AL FINAL (Semana 4-5):
├─ Problem #3: JS duplicado → Consolidar
├─ Tests y documentación
└─ IMPACTO: +10% mejora

RESULTADO: Puedes parar en cualquier momento y tener mejoras
```

---

## 📋 TABLA DE DECISIONES - TEMPLATE PARA LLENAR

```markdown
# MIS DECISIONES

## Pregunta 1: ¿Cuál versión de orders-table?
RESPUESTA: [ ] orders-table.js  [ ] orders-table-v2.js  [ ] Investigar

## Pregunta 2: ¿Eliminar tablas antiguas?
RESPUESTA: [ ] Inmediatamente  [ ] Temporalmente (2 sem)  [ ] Nunca

## Pregunta 3: ¿Tengo deadline?
RESPUESTA: [ ] 5 semanas  [ ] 3 semanas  [ ] 2 semanas  [ ] 1 semana

## Pregunta 4: ¿Quién testea?
RESPUESTA: [ ] Yo mismo  [ ] Otro dev  [ ] QA/Tester  [ ] Compartido

## Pregunta 5: ¿Puedo revertir?
RESPUESTA: [ ] Sí (backup listo)  [ ] Sí (pero sin backup)  [ ] No

## Pregunta 6: ¿Todo o solo prioritario?
RESPUESTA: [ ] Todo (5 sem)  [ ] Crítico (3 sem)  [ ] Mínimo (2 sem)

---

MI PLAN FINAL:
Voy a hacer: ...
Timeline: ...
Recursos: ...
```

---

## 🎯 DECISIÓN RÁPIDA (2 MINUTOS)

Si no tienes tiempo para todas las preguntas, contesta estas 3:

```
1. ¿Cuánto tiempo tengo?
   [ ] 5 semanas → Plan completo
   [ ] 2-3 semanas → Lo crítico
   [ ] 1 semana → Mínimo

2. ¿Alguien me ayuda a testear?
   [ ] Sí → Más rápido
   [ ] No → Más lento

3. ¿Tengo backup de BD?
   [ ] Sí → Puedo empezar YA
   [ ] No → Hacer backup PRIMERO

---

RESULTADO: Con estas 3 respuestas puedo personalizar el plan
```

---

## 📞 RESUMEN DE ACCIONES

### Antes de empezar:
1. ✅ Contesta las 6 preguntas (este documento)
2. ✅ Haz backup de BD
3. ✅ Crea rama en git
4. ✅ Lee PLAN-ACCION-INMEDIATA-7-DIAS.md

### Día 1:
1. ✅ Auditoría (2 horas)
2. ✅ Documentar hallazgos
3. ✅ Planificar

### Día 2+:
1. ✅ Seguir el plan día a día
2. ✅ Testing después de cada paso
3. ✅ Verificar logs

### Si falla algo:
1. ✅ No entrar en pánico
2. ✅ Verificar logs
3. ✅ Revertir último cambio (git)
4. ✅ Intentar de nuevo lentamente

---

*Documento: Matriz de Decisiones  
Fecha: 3 Diciembre 2025  
Versión: 1.0*

