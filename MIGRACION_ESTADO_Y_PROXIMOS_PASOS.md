# MIGRACIÓN: ESTADO Y PRÓXIMOS PASOS

##  DECISIÓN TOMADA

 **SE MIGRA TODO DE `PedidoProduccion/` A `Pedidos/`**

**Razón:** `Pedidos/` cumple MEJOR con patrones DDD:
-  Extiende AggregateRoot
-  Usa ValueObjects (NumeroPedido, Estado)
-  Mejor encapsulación
-  Estructura más clara

---

## 📍 ESTADO ACTUAL

### Lo que ya existe en `Pedidos/`:
-  PedidoAggregate.php (bien implementado)
-  PrendaPedido.php (Entity)
-  PrendaFotoService.php (Domain Service)
-  ValueObjects (Estado, NumeroPedido)
-  Events (PedidoActualizado, PedidoCreado, PedidoEliminado)
-  Exceptions (EstadoPedidoInvalido, PedidoNoEncontrado)
-  Repositories (PedidoRepository.php)

### Lo que está en `PedidoProduccion/` y necesita migrar:
- Commands (5 archivos)
- CommandHandlers (5 archivos)
- Queries (5 archivos) ⚠️ YA MODIFICADOS EN SESIÓN ANTERIOR
- QueryHandlers (5 archivos) ⚠️ YA MODIFICADOS
- Services (30+ archivos)
- Aggregates (3 archivos: Logo, Prenda, PedidoProduccion)
- Events (4 archivos)
- Listeners (4 archivos)
- Repositories (3 archivos)
- DTOs, Validators, Traits, Strategies, Facades, etc.

---

##  PLAN POR FASES

Documento completo en: [PLAN_MIGRACION_FASES_PEDIDOPRODUCCION_A_PEDIDOS.md](PLAN_MIGRACION_FASES_PEDIDOPRODUCCION_A_PEDIDOS.md)

### Resumen rápido:

| Fase | Nombre | Archivos | Duración |
|------|--------|----------|----------|
| 1 | Crear estructura | Carpetas | 5 min |
| 2 | Migrar Aggregates | 3 | 15 min |
| 3 | ValueObjects/Entities | 5 | 10 min |
| 4 | Commands/Handlers | 10 | 20 min |
| 5 | Queries/Handlers | 10 | 20 min ⚠️ |
| 6 | Services | 30+ | 60 min |
| 7 | Events/Listeners/Repos | 10 | 20 min |
| 8 | Validators/Traits/etc | 15 | 20 min |
| 9 | Actualizar Controllers | 2 | 10 min ⚠️ |
| 10 | Actualizar Tests | 20+ | 30 min |
| 11 | Validación final | Verificación | 30 min ⚠️ |
| 12 | Eliminar antigua | Cleanup | 5 min |

**Total estimado:** 3-4 horas

---

## ⚠️ PUNTOS CRÍTICOS

### 1. FASE 5: Queries/QueryHandlers
**Estado:** YA FUERON MODIFICADOS EN LA SESIÓN ANTERIOR

Los siguientes handlers ya tienen el fix de fotos:
- ObtenerPedidoHandler.php
- ObtenerPrendasPorPedidoHandler.php
- BuscarPedidoPorNumeroHandler.php

**Acción:** Al migrar, mantener los cambios que hicimos.

### 2. FASE 9: Controllers
**Crítico:** Si los imports fallan, la aplicación no funciona

Controllers afectados:
- `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`
- `app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php`

**Acción:** Ejecutar tests inmediatamente después.

### 3. FASE 11: Validación
**Crítico:** NO saltear esta fase

Ejecutar:
```bash
grep -r "PedidoProduccion" app/ --include="*.php"
php artisan test tests/
```

---

##  CHECKLIST PRE-MIGRACIÓN

Antes de comenzar:

- [ ] Hacer un commit limpio: `git commit -am "Pre-migración: estado base"`
- [ ] Crear rama: `git checkout -b feature/migracion-pedidos`
- [ ] Verificar que no hay cambios sin guardar: `git status`
- [ ] Tests pasando: `php artisan test`
- [ ] Base de datos sincronizada

---

## 🛠️ HERRAMIENTAS DISPONIBLES

### Script de ayuda:
```bash
pwsh scripts/migracion-help.ps1
```

Este script:
-  Crea la estructura de directorios
-  Busca referencias a PedidoProduccion en el código
-  Verifica que estés listo para migrar

---

## 📖 DOCUMENTOS RELACIONADOS

- [VEREDICTO_CUAL_CARPETA_CUMPLE_DDD.md](VEREDICTO_CUAL_CARPETA_CUMPLE_DDD.md) - Análisis de por qué Pedidos es mejor
- [PLAN_MIGRACION_FASES_PEDIDOPRODUCCION_A_PEDIDOS.md](PLAN_MIGRACION_FASES_PEDIDOPRODUCCION_A_PEDIDOS.md) - Plan detallado
- [FIX_FOTOS_NO_CARGAN_MODAL_PRIMERA_VEZ.md](FIX_FOTOS_NO_CARGAN_MODAL_PRIMERA_VEZ.md) - Cambios ya hechos en QueryHandlers

---

## PRÓXIMO PASO

**¿Quieres que comience la FASE 1?**

Confirma y haré:
1. Crear estructura de directorios
2. Hacer primer commit: `Migración FASE 1: Crear estructura`
3. Pasar a FASE 2

---

## 💡 RECOMENDACIÓN

**Orden de ejecución sugerido:**

1.  FASE 1: Crear estructura (YA)
2. ⏳ FASE 2: Aggregates (después confirma)
3. ⏳ FASE 3-8: El resto
4. ⚠️ FASE 9: Controllers (después ejecutar tests)
5.  FASE 10: Tests
6.  FASE 11: Validación final
7.  FASE 12: Cleanup

**Hacer commit después de cada fase para poder rollback si algo falla.**
