# PLAN DETALLADO - FASES 2, 3, 4 (PRÓXIMOS PASOS)

**Documento de planificación para completar el refactor**
**Fecha:** 2024
**Tiempo total estimado:** 12-22 horas

---

##  VISIÓN GENERAL

```
FASE 1: CONSOLIDACIÓN ..........................  COMPLETADA
├─ Tiempo: Ya hecho
├─ Resultado: Código legacy eliminado, rutas consolidadas
└─ Status: 100% listo

FASE 2: MIGRACIÓN FRONTEND ..................... ⏳ PRÓXIMA
├─ Tiempo: 4-6 horas
├─ Qué hacer: Actualizar JS y templates
└─ Documentación: QUICK_START_FASE2.md

FASE 3: CONSOLIDACIÓN BD ....................... ⏳ DESPUÉS
├─ Tiempo: 3-4 horas
├─ Qué hacer: Migrar datos, eliminar tabla legacy
└─ Documentación: Por crear

FASE 4: CLEANUP & TESTING ...................... ⏳ FINAL
├─ Tiempo: 5-8 horas
├─ Qué hacer: Eliminar código legacy, tests finales
└─ Documentación: Por crear

TOTAL: ~12-22 horas de trabajo ⏳
```

---

## 📝 FASE 2 - MIGRACIÓN FRONTEND (4-6 HORAS)

### Objetivo
Actualizar TODO el código frontend (JavaScript, Blade templates) para llamar a `/api/pedidos` en lugar de `/asesores/pedidos`.

###  Tareas Específicas

#### TAREA 2.1: Búsqueda de archivos (15 min)
```bash
# Comando para encontrar archivos
grep -r "asesores/pedidos" resources/ --include="*.js" --include="*.blade.php"
grep -r "CrearPedidoService" app/ --include="*.php" --exclude-dir=vendor

# Resultado esperado: Lista de ~5-15 archivos a actualizar
```

**Archivos típicos a encontrar:**
- [ ] `resources/views/asesores/pedidos/index.blade.php`
- [ ] `resources/views/asesores/pedidos/create.blade.php`
- [ ] `resources/views/asesores/pedidos/edit.blade.php`
- [ ] `resources/js/pedidos.js` (si existe)
- [ ] `resources/js/asesores.js` (si existe)
- [ ] `public/js/pedidos.js` (si existe legacy)
- [ ] Otros controllers que usen CrearPedidoService

#### TAREA 2.2: Actualizar cada archivo (3-4 horas)

Para cada archivo encontrado:

**Paso 1: Abrir en VS Code**
```
File → Open → archivo.js/.blade.php
```

**Paso 2: Reemplazar rutas**
```javascript
// Buscar:       /asesores/pedidos
// Reemplazar con: /api/pedidos

// Ejemplos:
fetch('/asesores/pedidos', ...)
→ fetch('/api/pedidos', ...)

fetch('/asesores/pedidos/confirm', ...)
→ fetch('/api/pedidos/${id}/confirmar', ...)

fetch('/asesores/pedidos/{id}/anular', ...)
→ fetch('/api/pedidos/${id}/cancelar', ...)
```

**Paso 3: Validar estructura de respuesta**
```javascript
// ANTES (legacy)
{
  success: true,
  borrador_id: 1,
  ...
}

// DESPUÉS (DDD)
{
  success: true,
  data: {
    id: 1,
    numero_pedido: "PED-001",
    ...
  }
}

// Cambiar acceso:
// ANTES: result.borrador_id
// DESPUÉS: result.data.id
```

**Paso 4: Agregar manejo de errores**
```javascript
// Manejo de 410 Gone (ruta deprecada)
.catch(error => {
  if (error.status === 410) {
    console.error("Usa nueva ruta:", error.nueva_ruta);
  }
});

// Ver: GUIA_MIGRACION_FRONTEND.md para más detalles
```

**Paso 5: Commit del archivo**
```bash
git add resources/views/asesores/pedidos/index.blade.php
git commit -m "Actualizar index.blade.php para usar /api/pedidos"
```

#### TAREA 2.3: Testing manual (1-2 horas)

Para cada archivo actualizado:

```bash
# 1. Ejecutar tests automáticos
php artisan test

# Resultado esperado: 16/16 pasando 

# 2. Testing manual
# Abrir navegador, ingresar a la aplicación
# Hacer clic en "Crear Pedido" (o similar)
# Verificar en Network tab del Dev Tools:
#   - Request va a /api/pedidos (NO /asesores/pedidos)
#   - Response status es 200/201 (NO 410)
#   - Response JSON tiene estructura correcta

# 3. Validar flujos completos
# Crear pedido → Confirmar → Listar → Obtener detalle
```

### 📍 Documentación para Fase 2
- QUICK_START_FASE2.md (inicio rápido)
- GUIA_MIGRACION_FRONTEND.md (ejemplos detallados)
- GUIA_API_PEDIDOS_DDD.md (referencia de endpoints)

###  Fase 2 está COMPLETA cuando:
- [x] Todos los archivos actualizados
- [x] No hay referencias a /asesores/pedidos
- [x] Tests pasando (16/16)
- [x] Testing manual completado
- [x] Cambios commiteados

---

## 📝 FASE 3 - CONSOLIDACIÓN BD (3-4 HORAS)

### Objetivo
Migrar datos de tabla legacy `pedidos_produccion` a tabla DDD `pedidos`, eliminando tabla vieja.

###  Tareas Específicas

#### TAREA 3.1: Crear migración (1 hora)

```bash
# Crear nueva migración
php artisan make:migration MigratePedidosProduccionToPedidos

# Estructura de migración:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Copiar datos de pedidos_produccion a pedidos
        DB::statement('
            INSERT INTO pedidos (
                numero_pedido, 
                cliente, 
                descripcion, 
                estado, 
                created_at, 
                updated_at
            )
            SELECT 
                numero_pedido,
                cliente,
                descripcion,
                CASE 
                    WHEN estado = "pendiente" THEN "PENDIENTE"
                    WHEN estado = "confirmado" THEN "CONFIRMADO"
                    WHEN estado = "cancelado" THEN "CANCELADO"
                    ELSE "PENDIENTE"
                END as estado,
                created_at,
                updated_at
            FROM pedidos_produccion
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        // Rollback: eliminar datos migrados
        DB::statement('DELETE FROM pedidos WHERE id > 0');
    }
};
```

**Ejecutar migración:**
```bash
php artisan migrate
```

#### TAREA 3.2: Validar integridad de datos (1 hora)

```bash
# Script de validación (crear en app/Console/Commands/)

php artisan tinker
>>> // Validar conteos
>>> DB::table('pedidos_produccion')->count()
>>> DB::table('pedidos')->count()
>>> // Deben ser iguales

>>> // Validar datos específicos
>>> DB::table('pedidos')->where('id', 1)->first()
>>> // Verificar que datos estén completos
```

#### TAREA 3.3: Eliminar tabla legacy (30 min)

```bash
# Crear nueva migración
php artisan make:migration DropPedidosProduccionTable

# Estructura:
Schema::dropIfExists('pedidos_produccion');
```

**Ejecutar:**
```bash
php artisan migrate
```

#### TAREA 3.4: Actualizar queries (1 hora)

Buscar cualquier query que aún use `pedidos_produccion`:

```bash
grep -r "pedidos_produccion" app/ --include="*.php"
```

Cambiar a usar tabla `pedidos` (modelo DDD):

```php
// ANTES
$pedido = DB::table('pedidos_produccion')
    ->where('id', $id)
    ->first();

// DESPUÉS
$pedido = Pedido::find($id);
// O mejor aún:
$pedido = $this->pedidoRepository->obtener($id);
```

###  Fase 3 está COMPLETA cuando:
- [x] Datos migrados correctamente
- [x] Integridad validada (conteos coinciden)
- [x] Tabla legacy eliminada
- [x] Queries actualizadas
- [x] Tests pasando

---

## 📝 FASE 4 - CLEANUP & TESTING (5-8 HORAS)

### Objetivo
Eliminar completamente código legacy, hacer suite final de tests, validar performance y seguridad.

###  Tareas Específicas

#### TAREA 4.1: Eliminar código legacy (2-3 horas)

**Archivo 1: Eliminar AsesoresAPIController**
```bash
# Opción A: Eliminar completamente
rm app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php

# Opción B: Mantener como historical reference (comentado)
# Dejar archivo pero con comentario:
# "Este controller fue deprecado en Fase 4.
#  Código movido a app/Application/Pedidos/UseCases/"
```

**Archivo 2: Eliminar Services legacy**
```bash
# Archivos a eliminar:
rm app/Services/CrearPedidoService.php
rm app/Services/AnularPedidoService.php
rm app/Services/ObtenerFotosService.php
# ... más si existen
```

**Archivo 3: Eliminar modelos legacy**
```bash
# Opción A: Eliminar completamente
rm app/Models/PedidoProduccion.php

# Opción B: Mantener como historical reference
# Mantener pero con comentario de deprecación
```

**Archivo 4: Limpiar Service Providers**
```php
// Buscar en app/Providers/
// Eliminar cualquier binding de:
//  - CrearPedidoService
//  - AnularPedidoService
//  - PedidoProduccionRepository
// Mantener solo bindings DDD

// app/Providers/DomainServiceProvider.php - debe ser ÚNICO provider
```

**Archivo 5: Limpiar rutas**
```php
// routes/web.php
// Eliminar cualquier ruta que apunte a AsesoresAPIController
// Mantener solo rutas de vistas (GET) en /asesores/pedidos
// Todas las operaciones deben ir a /api/pedidos en routes/api.php
```

#### TAREA 4.2: Suite completa de tests (1-2 horas)

```bash
# Ejecutar TODOS los tests
php artisan test

# Ejecutar tests específicos de pedidos
php artisan test tests/Unit/Domain/Pedidos/
php artisan test tests/Unit/Application/Pedidos/

# Crear nuevos tests si es necesario
# Para:
# - Integración completa (controller → use case → db)
# - Seguridad (permisos, autenticación)
# - Performance (carga de datos)

# Resultado esperado: 100% pasando
```

#### TAREA 4.3: Security audit (1-2 horas)

```bash
# Validar seguridad:

# 1. Autenticación
- [x] Endpoints /api/pedidos requieren auth
- [x] Solo usuarios autenticados pueden acceder

# 2. Autorización
- [x] Solo propietario puede ver/editar su pedido
- [x] Roles correctamente validados (asesor, supervisor, admin)

# 3. Validación de input
- [x] Todos los campos validados
- [x] XSS protegido
- [x] SQL injection protegido (Eloquent ORM)

# 4. Ratas limitadas (si aplica)
- [x] Rate limiting en endpoints críticos

# Herramientas:
php artisan tinker
>>> // Testear manualmente
>>> $pedido = new PedidoAggregate(...);
>>> $pedido->confirmar();
>>> // Verificar transiciones de estado

# Usar SonarQube o similar:
sonar-scanner
```

#### TAREA 4.4: Performance testing (1 hora)

```bash
# Benchmarking de endpoints

# Herramientas:
# - Laravel Telescope (built-in)
# - Apache Bench (ab)
# - Postman
# - K6

# Tests a hacer:
1. Crear 100 pedidos rápidamente
   └─ Tiempo de respuesta debe ser < 200ms
   
2. Listar 1000 pedidos
   └─ Debe completarse en < 500ms
   
3. Obtener detalle completo
   └─ Debe tener < 3 queries (N+1 problem)

4. Bajo carga (100 requests simultáneos)
   └─ Debe manejar sin timeout
```

#### TAREA 4.5: Documentación final (1 hora)

```bash
# Crear documento de finalización:
FASE4_COMPLETION_REPORT.md

Incluir:
- Resumen de cambios
- Resultados de tests
- Security audit report
- Performance metrics
- Conclusiones
- Date de deployment
```

###  Fase 4 está COMPLETA cuando:
- [x] Código legacy completamente eliminado
- [x] 100% tests pasando
- [x] Security audit completado
- [x] Performance validado
- [x] Documentación final creada
- [x] Listo para production deployment

---

## DEPLOYMENT

Una vez Fase 4 completada:

```bash
# 1. Final checks
php artisan test           # 100% pasando
php artisan config:cache  # Cache invalidado
php artisan migrate        # All migrations

# 2. Deploy
git add .
git commit -m "Refactor DDD Pedidos - Fases 1-4 completadas"
git push production main

# 3. Post-deploy
php artisan queue:restart  # Restart workers si aplica
php artisan horizon:pause  # Pause horizon si aplica

# 4. Monitoring
# Monitorear logs por 24 horas
# Validar que todo está funcionando
```

---

## 📊 TIMELINE ESTIMADO

```
AHORA:           Fase 1  completada
HOY (4-6h):      Fase 2 - Migración Frontend
MAÑANA (3-4h):   Fase 3 - Consolidación BD
PASADO (5-8h):   Fase 4 - Cleanup & Testing
PRÓX SEMANA:     Production Deployment

TOTAL: ~1 semana de trabajo (12-22 horas)
```

---

## 💾 CHECKLIST COMPLETO

### Fase 2
- [ ] Archivos encontrados (grep)
- [ ] Cada archivo actualizado
- [ ] Tests pasando (16/16)
- [ ] Testing manual completado
- [ ] Cambios commiteados

### Fase 3
- [ ] Migración creada
- [ ] Datos validados
- [ ] Tabla legacy eliminada
- [ ] Queries actualizadas
- [ ] Tests pasando

### Fase 4
- [ ] Código legacy eliminado
- [ ] Service providers limpios
- [ ] Rutas limpias
- [ ] 100% tests pasando
- [ ] Security audit OK
- [ ] Performance OK
- [ ] Documentación final

### Deployment
- [ ] Final checks hecho
- [ ] Push a main
- [ ] Post-deploy validado
- [ ] Monitoring activado

---

## 📞 REFERENCIAS

| Fase | Documentación |
|------|---------------|
| 2 | QUICK_START_FASE2.md, GUIA_MIGRACION_FRONTEND.md |
| 3 | Por crear - FASE3_MIGRACION_BD.md |
| 4 | Por crear - FASE4_CLEANUP.md |
| General | ESTADO_REFACTOR_RESUMEN.md, INDICE_REFACTOR_DDD_PEDIDOS.md |

---

## CONCLUSIÓN

El refactor está en **buen track**. Fase 1 completada con éxito, Fases 2-4 bien documentadas y planificadas.

**Próximo paso:** Ejecutar QUICK_START_FASE2.md

**Status:**  LISTO PARA PROCEDER

---

*Documento de planificación completo*
*Última actualización: 2024*
*Responsable: Team DDD*
