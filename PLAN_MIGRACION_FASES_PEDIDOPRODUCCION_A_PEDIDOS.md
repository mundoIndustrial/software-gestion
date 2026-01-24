#  PLAN DE MIGRACIÓN POR FASES: PedidoProduccion → Pedidos

##  RESUMEN
Mover todo de `/app/Domain/PedidoProduccion/` a `/app/Domain/Pedidos/` de forma segura y progresiva.

**Riesgo:** BAJO (si seguimos las fases)
**Duración estimada:** 2-3 horas
**Validación:** Al final de cada fase

---

## FASES DE MIGRACIÓN

###  FASE 0: PREPARACIÓN (YA HECHO)
- [x] Análisis de qué migrar
- [x] Documento de veredicto creado
- [x] Plan de fases definido

---

## 🔧 FASE 1: CREAR ESTRUCTURA EN PEDIDOS/

**Objetivo:** Crear carpetas necesarias para recibir los archivos

**Archivos a crear (carpetas vacías):**
```
app/Domain/Pedidos/
├── Commands/                (CREAR)
├── CommandHandlers/         (CREAR)
├── Queries/                 (CREAR)
├── QueryHandlers/           (CREAR)
├── DTOs/                    (CREAR)
├── Listeners/               (CREAR)
├── Validators/              (CREAR)
├── Strategies/              (CREAR)
├── Traits/                  (CREAR)
├── Facades/                 (CREAR)
├── Aggregates/              (CREAR)
├── Events/                  (VERIFICAR - puede existir)
├── Services/                (VERIFICAR - puede existir)
└── Repositories/            (VERIFICAR - puede existir)
```

**Acciones:**
```powershell
# PowerShell - Crear directorios
New-Item -ItemType Directory -Path "app/Domain/Pedidos/Commands" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/CommandHandlers" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/Queries" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/QueryHandlers" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/DTOs" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/Listeners" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/Validators" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/Strategies" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/Traits" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/Facades" -Force
New-Item -ItemType Directory -Path "app/Domain/Pedidos/Aggregates" -Force
```

**Validación:**
```bash
ls -la app/Domain/Pedidos/  # Verificar que existan todas las carpetas
```

**Status:** ⏳ PENDIENTE

---

## 📦 FASE 2: MIGRAR AGGREGATES

**Objetivo:** Mover los 3 Aggregates de PedidoProduccion a Pedidos

**Archivos a mover:**
```
From: app/Domain/PedidoProduccion/Aggregates/
├── LogoPedidoAggregate.php
├── PedidoProduccionAggregate.php
└── PrendaPedidoAggregate.php

To: app/Domain/Pedidos/Aggregates/
```

**Cambios de namespace en cada archivo:**

1. **LogoPedidoAggregate.php**
   - FROM: `namespace App\Domain\PedidoProduccion\Aggregates;`
   - TO: `namespace App\Domain\Pedidos\Aggregates;`
   - Actualizar imports internos de `PedidoProduccion` → `Pedidos`

2. **PedidoProduccionAggregate.php**
   - FROM: `namespace App\Domain\PedidoProduccion\Aggregates;`
   - TO: `namespace App\Domain\Pedidos\Aggregates;`
   - **IMPORTANTE:** Renombrar clase a `PedidoAggregate` (unificar con el existente)
   - O mantener nombre y consolidar después

3. **PrendaPedidoAggregate.php**
   - FROM: `namespace App\Domain\PedidoProduccion\Aggregates;`
   - TO: `namespace App\Domain\Pedidos\Aggregates;`
   - Actualizar imports

**Tests a ejecutar:**
```bash
php artisan test tests/Unit/Domain/Pedidos/ --filter Aggregate
# O donde estén los tests de Aggregates
```

**Validación:**
- [ ] No hay errores de namespace
- [ ] Tests de Aggregates pasan
- [ ] No hay referencias circulares

**Status:** ⏳ PENDIENTE

---

## 🔄 FASE 3: MIGRAR VALUEOBJECTS Y ENTITIES

**Objetivo:** Mover VO y Entity si existen en PedidoProduccion

**Archivos a revisar:**
```
app/Domain/PedidoProduccion/ValueObjects/  (¿Existen?)
app/Domain/PedidoProduccion/Entities/      (¿Existen?)
```

**Acciones:**
- Si existen en PedidoProduccion, moverlos a Pedidos/
- Actualizar namespaces
- Consolidar con los existentes en Pedidos/

**Status:** ⏳ PENDIENTE

---

## 📤 FASE 4: MIGRAR COMMANDS Y COMMANDHANDLERS

**Objetivo:** Mover sistema de comandos

**Archivos a mover:**
```
From: app/Domain/PedidoProduccion/Commands/
├── ActualizarPedidoCommand.php
├── AgregarPrendaAlPedidoCommand.php
├── CambiarEstadoPedidoCommand.php
├── CrearPedidoCommand.php
└── EliminarPedidoCommand.php

From: app/Domain/PedidoProduccion/CommandHandlers/
├── ActualizarPedidoHandler.php
├── AgregarPrendaAlPedidoHandler.php
├── CambiarEstadoPedidoHandler.php
├── CrearPedidoHandler.php
└── EliminarPedidoHandler.php

To: app/Domain/Pedidos/Commands/ y CommandHandlers/
```

**Cambios necesarios:**

En cada archivo Command:
```php
// FROM
namespace App\Domain\PedidoProduccion\Commands;

// TO
namespace App\Domain\Pedidos\Commands;
```

En cada CommandHandler:
```php
// FROM
namespace App\Domain\PedidoProduccion\CommandHandlers;
use App\Domain\PedidoProduccion\Commands\*Command;
use App\Domain\PedidoProduccion\Repositories\*;

// TO
namespace App\Domain\Pedidos\CommandHandlers;
use App\Domain\Pedidos\Commands\*Command;
use App\Domain\Pedidos\Repositories\*;
```

**Tests:**
```bash
php artisan test tests/Unit/Domain/Pedidos/ --filter Command
```

**Status:** ⏳ PENDIENTE

---

## 🔍 FASE 5: MIGRAR QUERIES Y QUERYHANDLERS

**Objetivo:** Mover sistema de queries (CRÍTICO - usado actualmente)

**Archivos a mover:**
```
From: app/Domain/PedidoProduccion/Queries/
├── BuscarPedidoPorNumeroQuery.php
├── FiltrarPedidosPorEstadoQuery.php
├── ListarPedidosQuery.php
├── ObtenerPedidoQuery.php
└── ObtenerPrendasPorPedidoQuery.php

From: app/Domain/PedidoProduccion/QueryHandlers/
├── BuscarPedidoPorNumeroHandler.php
├── FiltrarPedidosPorEstadoHandler.php
├── ListarPedidosHandler.php
├── ObtenerPedidoHandler.php
└── ObtenerPrendasPorPedidoHandler.php

To: app/Domain/Pedidos/Queries/ y QueryHandlers/
```

**⚠️ ESTOS SON CRÍTICOS - YA FUERON MODIFICADOS EN SESIÓN ANTERIOR**

**Cambios necesarios:**

En QueryHandlers (ya hicimos cambios):
```php
// FROM
namespace App\Domain\PedidoProduccion\QueryHandlers;

// TO
namespace App\Domain\Pedidos\QueryHandlers;

// Y actualizar imports internos
use App\Domain\Pedidos\Queries\*;
```

**Tests:**
```bash
php artisan test tests/Feature/Http/Controllers/ --filter "pedido|prenda"
```

**Status:** ⏳ PENDIENTE (OJO: Ya tenemos cambios previos)

---

## 🔧 FASE 6: MIGRAR SERVICES

**Objetivo:** Mover todos los servicios (~30+ archivos)

**Carpeta origen:**
```
app/Domain/PedidoProduccion/Services/
├── CaracteristicasPrendaService.php
├── ClienteService.php
├── ... (30+ más)
└── PrendaVarianteService.php
```

**Destino:**
```
app/Domain/Pedidos/Services/
```

**Cambios:**
- Actualizar namespaces en cada archivo
- Buscar imports de `PedidoProduccion` → `Pedidos`
- Buscar imports de `PedidoProduccionRepository` → actualizar

**Validación:**
```bash
php artisan test tests/Unit/Domain/Pedidos/Services/
```

**Status:** ⏳ PENDIENTE

---

## 📢 FASE 7: MIGRAR EVENTS, LISTENERS Y REPOSITORIES

**Objetivo:** Mover infraestructura de eventos y persistencia

**Archivos a mover:**

Events:
```
From: app/Domain/PedidoProduccion/Events/
├── LogoPedidoCreado.php
├── PedidoProduccionCompletado.php
├── PedidoProduccionCreado.php
└── PrendaPedidoAgregada.php

To: app/Domain/Pedidos/Events/
```

Listeners:
```
From: app/Domain/PedidoProduccion/Listeners/
├── ActualizarCachePedidos.php
├── ActualizarEstadisticasPrendas.php
├── NotificarClientePedidoCreado.php
└── RegistrarAuditoriaPedido.php

To: app/Domain/Pedidos/Listeners/
```

Repositories:
```
From: app/Domain/PedidoProduccion/Repositories/
├── CotizacionRepository.php
├── LogoPedidoRepository.php
└── PedidoProduccionRepository.php

To: app/Domain/Pedidos/Repositories/
```

**Cambios:**
- Todos los namespaces: `PedidoProduccion` → `Pedidos`
- Actualizar imports

**Status:** ⏳ PENDIENTE

---

## FASE 8: MIGRAR VALIDATORS, TRAITS, STRATEGIES, FACADES

**Objetivo:** Mover los archivos restantes

**Archivos:**
```
app/Domain/PedidoProduccion/Validators/    → Pedidos/Validators/
app/Domain/PedidoProduccion/Traits/         → Pedidos/Traits/
app/Domain/PedidoProduccion/Strategies/     → Pedidos/Strategies/
app/Domain/PedidoProduccion/Facades/        → Pedidos/Facades/
app/Domain/PedidoProduccion/DTOs/           → Pedidos/DTOs/
app/Domain/PedidoProduccion/Entities/       → Pedidos/Entities/
```

**Cambios:**
- Actualizar todos los namespaces

**Status:** ⏳ PENDIENTE

---

## 🔌 FASE 9: ACTUALIZAR IMPORTS EN CONTROLLERS

**Objetivo:** Cambiar todos los imports en Controllers

**Archivos afectados:**
```
app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php
app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php
```

**Cambios en PedidosProduccionController.php:**

FROM:
```php
use App\Domain\PedidoProduccion\Queries\ObtenerPedidoQuery;
use App\Domain\PedidoProduccion\Queries\ListarPedidosQuery;
use App\Domain\PedidoProduccion\Queries\FiltrarPedidosPorEstadoQuery;
use App\Domain\PedidoProduccion\Queries\BuscarPedidoPorNumeroQuery;
use App\Domain\PedidoProduccion\Queries\ObtenerPrendasPorPedidoQuery;
use App\Domain\PedidoProduccion\Commands\CrearPedidoCommand;
use App\Domain\PedidoProduccion\Commands\ActualizarPedidoCommand;
use App\Domain\PedidoProduccion\Commands\CambiarEstadoPedidoCommand;
use App\Domain\PedidoProduccion\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\PedidoProduccion\Commands\EliminarPedidoCommand;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
```

TO:
```php
use App\Domain\Pedidos\Queries\ObtenerPedidoQuery;
use App\Domain\Pedidos\Queries\ListarPedidosQuery;
use App\Domain\Pedidos\Queries\FiltrarPedidosPorEstadoQuery;
use App\Domain\Pedidos\Queries\BuscarPedidoPorNumeroQuery;
use App\Domain\Pedidos\Queries\ObtenerPrendasPorPedidoQuery;
use App\Domain\Pedidos\Commands\CrearPedidoCommand;
use App\Domain\Pedidos\Commands\ActualizarPedidoCommand;
use App\Domain\Pedidos\Commands\CambiarEstadoPedidoCommand;
use App\Domain\Pedidos\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\Pedidos\Commands\EliminarPedidoCommand;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
```

**Cambios en AsesoresController.php:**

FROM:
```php
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
```

TO:
```php
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
```

**Status:** ⏳ PENDIENTE

---

## 🧪 FASE 10: ACTUALIZAR IMPORTS EN TESTS

**Objetivo:** Actualizar imports en archivos de test

**Buscar y reemplazar:**
```
FROM: App\Domain\PedidoProduccion
TO:   App\Domain\Pedidos
```

**Archivos a revisar:**
- `tests/Unit/Domain/PedidoProduccion/`
- `tests/Feature/Domain/`
- Mover a `tests/Unit/Domain/Pedidos/` si no existe

**Status:** ⏳ PENDIENTE

---

##  FASE 11: VALIDACIÓN FINAL

**Objetivo:** Verificar que no haya referencias faltantes

**Checklist:**

```bash
# 1. Buscar referencias a PedidoProduccion en todo el código
grep -r "PedidoProduccion" app/ --include="*.php" | grep -v "/app/Domain/PedidoProduccion/"

# 2. Buscar imports rotos
grep -r "use App\\\\Domain\\\\PedidoProduccion" app/ --include="*.php"

# 3. Ejecutar tests
php artisan test tests/Unit/Domain/Pedidos/
php artisan test tests/Feature/Http/Controllers/

# 4. Validar estructura
ls -la app/Domain/Pedidos/
```

**Status:** ⏳ PENDIENTE

---

## 🗑️ FASE 12: ELIMINAR CARPETA ANTIGUA

**Objetivo:** Remover PedidoProduccion completamente

**Acciones:**
```bash
# SOLO después de validar Fase 11
rm -rf app/Domain/PedidoProduccion/
```

**Verificación:**
```bash
# Confirmar que no exista
ls app/Domain/ | grep -i pedido  # Solo debería mostrar "Pedidos"
```

**Status:** ⏳ PENDIENTE

---

## 📊 RESUMEN DE FASES

| Fase | Objetivo | Archivos | Riesgo | Status |
|------|----------|----------|--------|--------|
| 0 | Preparación | Análisis | BAJO |  HECHO |
| 1 | Crear estructura | Carpetas | BAJO | ⏳ |
| 2 | Aggregates | 3 | BAJO | ⏳ |
| 3 | ValueObjects/Entities | ~5 | BAJO | ⏳ |
| 4 | Commands/Handlers | 10 | BAJO | ⏳ |
| 5 | Queries/Handlers | 10 | ALTO | ⏳ |
| 6 | Services | 30+ | MEDIO | ⏳ |
| 7 | Events/Listeners/Repos | 10 | BAJO | ⏳ |
| 8 | Validators/Traits/etc | ~15 | BAJO | ⏳ |
| 9 | Controllers | 2 | CRÍTICO | ⏳ |
| 10 | Tests | 20+ | BAJO | ⏳ |
| 11 | Validación | Verificación | CRÍTICO | ⏳ |
| 12 | Eliminar antigua | Cleanup | BAJO | ⏳ |

---

## ⚠️ PUNTOS CRÍTICOS

1. **FASE 5 (Queries/QueryHandlers):** YA tienen cambios de sesión anterior
   - Los handlers ya están actualizados con las relaciones correctas
   - Necesitamos cuidado al migrar

2. **FASE 9 (Controllers):** Si falla, la aplicación no funciona
   - Verificar cada import
   - Ejecutar tests después

3. **FASE 11 (Validación):** NO saltear
   - Buscar TODOS los "PedidoProduccion" en el código
   - Ejecutar test suite completo

---

##  COMENZAR

¿Comenzamos por la **FASE 1: Crear estructura**?

Si dices "sí", crearé los directorios faltantes.
