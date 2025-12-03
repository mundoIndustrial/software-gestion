# 🚀 PLAN DE ACCIÓN INMEDIATA - PRIMEROS 7 DÍAS

**Objetivo:** Comenzar el refactor sin romper nada  
**Duración:** 1 semana  
**Riesgo:** BAJO (cambios compatibles hacia atrás)

---

## 📅 DÍA 1: AUDITORÍA Y PLANIFICACIÓN

### Mañana (2-3 horas)

#### Tarea 1.1: Auditoría de TablerosController
```bash
# Contar líneas exactas
wc -l app/Http/Controllers/TablerosController.php
# Esperado: ~2,100 líneas

# Ver métodos
grep -E "^\s*(public|private)\s+function" app/Http/Controllers/TablerosController.php | wc -l
# Esperado: ~30-40 métodos

# Ver imports
grep "^use " app/Http/Controllers/TablerosController.php | wc -l
# Esperado: ~15+ imports
```

**Deliverable:** Screenshot o nota con números exactos

---

#### Tarea 1.2: Auditoría de Duplicación BD
```php
// Verificar estructuras son idénticas
// Ejecutar en MySQL:
SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'mundoindustrial' 
AND TABLE_NAME IN ('registro_piso_produccion', 'registro_piso_polo', 'registro_piso_corte')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

// Contar registros
SELECT 
    'registro_piso_produccion' as tabla, COUNT(*) as total 
FROM registro_piso_produccion
UNION
SELECT 
    'registro_piso_polo', COUNT(*) 
FROM registro_piso_polo
UNION
SELECT 
    'registro_piso_corte', COUNT(*) 
FROM registro_piso_corte;
```

**Deliverable:** Reporte de columnas idénticas y conteos

---

#### Tarea 1.3: Auditoría de JS Frontend
```bash
# Listar archivos JS
find public/js -name "*.js" -type f | sort

# Contar líneas
find public/js -name "*.js" -type f -exec wc -l {} + | sort -rn | head -20

# Ver qué templates usan cada archivo
grep -r "orders-table" resources/views/ | cut -d: -f1 | sort -u
grep -r "modern-table" resources/views/ | cut -d: -f1 | sort -u
```

**Deliverable:** Lista de archivos y su uso

---

### Tarde (2-3 horas)

#### Tarea 1.4: Mapping de métodos en TablerosController
Crear documento con estructura:

```markdown
# Métodos en TablerosController

## Responsabilidad 1: Vistas (HTTP)
- [ ] fullscreen()
- [ ] corteFullscreen()
- [ ] index()
- [ ] loadSection()

## Responsabilidad 2: Cálculos
- [ ] calcularSeguimientoModulos()
- [ ] calcularProduccionPorHoras()
- [ ] calcularProduccionPorOperarios()
- [ ] calcularDiasProduccion()

## Responsabilidad 3: Filtros
- [ ] filtrarRegistrosPorFecha()
- [ ] aplicarFiltrosDinamicos()

## Responsabilidad 4: CRUD Operarios
- [ ] crearOperarioNuevo()
- [ ] ...

## Responsabilidad 5: CRUD Máquinas
- [ ] guardarMaquina()
- [ ] ...

etc.
```

**Deliverable:** Documento con todos los métodos categorizados

---

#### Tarea 1.5: Mapping de archivos JS
```markdown
# Archivos JavaScript - Mapping

## Orders
- [ ] public/js/orders js/orders-table.js - ¿OBSOLETO?
- [ ] public/js/orders js/orders-table-v2.js - ¿ACTUAL?
- [ ] public/js/orders js/modules/rowManager.js
- [ ] public/js/orders js/modules/filterManager.js
- [ ] ... (listar todos los módulos)

## Modern-Table
- [ ] public/js/modern-table/modern-table-v2.js
- [ ] public/js/modern-table/modules/...

## Templates que los usan
- [ ] resources/views/orders/index.blade.php → ¿Qué carga?
- [ ] resources/views/tableros.blade.php → ¿Qué carga?
- [ ] resources/views/insumos/materiales/index.blade.php → ¿Qué carga?
```

**Deliverable:** Documento con dependencias

---

### Entregable del Día 1

```
📁 docs/
├── auditoria-tableroscontroller.txt
├── auditoria-bd-duplicacion.txt
├── auditoria-js-frontend.txt
├── mapping-metodos-tableros.md
└── mapping-archivos-js.md
```

---

## 📅 DÍA 2: CREAR ESTRUCTURA SERVICES

### Objetivo
Crear la carpeta y clases de Services SIN tocar controllers aún.

### Mañana (3 horas)

#### Tarea 2.1: Crear carpeta Services
```bash
mkdir -p app/Services
touch app/Services/.gitkeep
```

#### Tarea 2.2: Crear interfaz base
```bash
# app/Services/BaseService.php
cat > app/Services/BaseService.php << 'EOF'
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    /**
     * Log de actividad
     */
    protected function log($message, $data = [])
    {
        Log::info("Service: " . static::class, [
            'message' => $message,
            'data' => $data,
        ]);
    }
    
    protected function logError($message, $exception = null)
    {
        Log::error("Service: " . static::class, [
            'message' => $message,
            'exception' => $exception ? $exception->getMessage() : null,
        ]);
    }
}
EOF
```

**Deliverable:** Archivo `app/Services/BaseService.php` creado

---

#### Tarea 2.3: Crear ProduccionCalculadoraService
```php
// app/Services/ProduccionCalculadoraService.php
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;

class ProduccionCalculadoraService extends BaseService
{
    /**
     * Calcular seguimiento de módulos
     * 
     * NOTA: Este es el método EXACT del controller
     * Sin cambios, solo movido de lugar
     */
    public function calcularSeguimientoModulos($registros)
    {
        // Copiar el código EXACT de TablerosController::calcularSeguimientoModulos()
        // Esto es temporal - despues se puede refactorizar
    }
    
    /**
     * Calcular producción por horas
     */
    public function calcularProduccionPorHoras($registros)
    {
        // Copiar el código EXACT del controller
    }
    
    /**
     * Calcular producción por operarios
     */
    public function calcularProduccionPorOperarios($registros)
    {
        // Copiar el código EXACT del controller
    }
}
```

**Deliverable:** Archivo `app/Services/ProduccionCalculadoraService.php` creado

---

#### Tarea 2.4: Crear FiltrosService
```php
// app/Services/FiltrosService.php
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class FiltrosService extends BaseService
{
    /**
     * Filtrar registros por fecha
     * 
     * NOTA: Este es el método EXACT del controller
     */
    public function filtrarRegistrosPorFecha($registros, Request $request)
    {
        // Copiar el código EXACT de TablerosController::filtrarRegistrosPorFecha()
    }
    
    /**
     * Aplicar filtros dinámicos
     */
    public function aplicarFiltrosDinamicos(&$query, Request $request, $tipo = null)
    {
        // Copiar el código EXACT del controller
    }
}
```

**Deliverable:** Archivo `app/Services/FiltrosService.php` creado

---

### Tarde (2 horas)

#### Tarea 2.5: Crear OperarioService
```php
// app/Services/OperarioService.php
<?php

namespace App\Services;

use App\Models\User;

class OperarioService extends BaseService
{
    /**
     * Crear operario nuevo
     */
    public function crear(array $data)
    {
        // Copiar lógica de crear operario
    }
    
    /**
     * Actualizar operario
     */
    public function actualizar(User $operario, array $data)
    {
        // Copiar lógica de actualización
    }
}
```

**Deliverable:** Archivo `app/Services/OperarioService.php` creado

---

#### Tarea 2.6: Crear MaquinaService
```php
// app/Services/MaquinaService.php
<?php

namespace App\Services;

use App\Models\Maquina;

class MaquinaService extends BaseService
{
    /**
     * Guardar máquina
     */
    public function guardar(array $data)
    {
        // Copiar lógica
    }
}
```

**Deliverable:** Archivo `app/Services/MaquinaService.php` creado

---

### Entregable del Día 2

```
✅ app/Services/
├── BaseService.php
├── ProduccionCalculadoraService.php
├── FiltrosService.php
├── OperarioService.php
├── MaquinaService.php
└── TelaService.php
```

**Testing:** Solo crear archivos, NO se usa en controller aún.

---

## 📅 DÍA 3: INYECTAR SERVICES EN CONTROLLER

### Objetivo
Que TablerosController use los Services pero sin cambiar su funcionamiento externo.

### Mañana (3 horas)

#### Tarea 3.1: Inyectar Services en constructor

```php
// app/Http/Controllers/TablerosController.php
// ANTES:
class TablerosController extends Controller
{
    public function __construct() {}
}

// DESPUÉS:
class TablerosController extends Controller
{
    public function __construct(
        private ProduccionCalculadoraService $produccionCalc,
        private FiltrosService $filtros,
        private OperarioService $operarios,
        private MaquinaService $maquinas,
        private TelaService $telas,
    ) {}
}
```

**IMPORTANTE:** Copiar exactamente los imports:
```php
use App\Services\ProduccionCalculadoraService;
use App\Services\FiltrosService;
use App\Services\OperarioService;
use App\Services\MaquinaService;
use App\Services\TelaService;
```

**Testing:** Verificar que no hay errores al cargar el controller
```bash
php artisan tinker
> new App\Http\Controllers\TablerosController(...)
```

---

#### Tarea 3.2: Reemplazar primera llamada en `index()`
```php
// ANTES:
$seguimiento = $this->calcularSeguimientoModulos($registrosFiltrados);

// DESPUÉS:
$seguimiento = $this->produccionCalc->calcularSeguimientoModulos($registrosFiltrados);
```

**Testing:** 
```bash
# Ir a /tableros en navegador
# Verificar que página carga igual que antes
```

---

#### Tarea 3.3: Reemplazar más llamadas
```php
// En método fullscreen()
$seguimiento = $this->produccionCalc->calcularSeguimientoModulos($registrosFiltrados);

// En método corteFullscreen()
$horasData = $this->produccionCalc->calcularProduccionPorHoras($registrosCorteFiltrados);
$operariosData = $this->produccionCalc->calcularProduccionPorOperarios($registrosCorteFiltrados);

// En método index()
$registrosFiltrados = $this->filtros->filtrarRegistrosPorFecha($registros, request());
$this->filtros->aplicarFiltrosDinamicos($queryProduccion, request(), 'produccion');
```

**Testing:** Ir a cada página y verificar que funciona

---

### Tarde (2 horas)

#### Tarea 3.4: Reemplazar métodos de CRUD
```php
// En método que crea operario:
// ANTES:
$operario = User::create($data);

// DESPUÉS:
$operario = $this->operarios->crear($data);

// En método que guarda máquina:
// ANTES:
Maquina::create($data);

// DESPUÉS:
$this->maquinas->guardar($data);
```

---

#### Tarea 3.5: Testing de integración
```bash
# Verificar que NO hay errores en logs
tail -f storage/logs/laravel.log

# Verificar que queries funcionan
curl http://localhost/tableros
curl http://localhost/api/registros

# Verificar que actualizaciones funcionan
# Crear un registro manualmente en BD y verificar que se ve en UI
```

---

### Entregable del Día 3

✅ TablerosController ahora usa Services  
✅ Funcionamiento IDÉNTICO al antes  
✅ Código más fácil de testear

**GIT COMMIT:**
```bash
git add app/Http/Controllers/TablerosController.php
git add app/Services/
git commit -m "refactor: extraer services de TablerosController

- ProduccionCalculadoraService
- FiltrosService
- OperarioService
- MaquinaService
- TelaService

Funcionalidad sin cambios, solo refactorización interna"
```

---

## 📅 DÍA 4: CREAR MODELS CON MÉTODOS

### Objetivo
Agregar métodos básicos a los models SIN cambiar lógica.

### Mañana (2-3 horas)

#### Tarea 4.1: Enriquecer Model `Orden`
```php
// app/Models/Orden.php (O PedidoProduccion si es su nombre)
// Agregar estos métodos:

/**
 * ¿Puede ser aprobada?
 */
public function puedeSerAprobada(): bool
{
    // Lógica que está en controller
    return $this->estado === 'borrador';
}

/**
 * Aprobar orden
 */
public function aprobar(): void
{
    if (!$this->puedeSerAprobada()) {
        throw new \Exception('No puede ser aprobada');
    }
    
    $this->estado = 'aprobada';
    $this->save();
}

/**
 * Calcular días desde creación
 */
public function calcularDiasDesdeCreacion(): int
{
    return now()->diffInDays($this->created_at);
}
```

**Testing:**
```bash
php artisan tinker
> $orden = Orden::first()
> $orden->puedeSerAprobada()
> $orden->calcularDiasDesdeCreacion()
```

---

#### Tarea 4.2: Enriquecer Model `Cotizacion`
```php
// app/Models/Cotizacion.php

/**
 * Calcular total
 */
public function calcularTotal(): float
{
    $total = 0;
    foreach ($this->prendasCotizaciones as $prenda) {
        $total += $prenda->cantidad * $prenda->precio_unitario;
    }
    return $total;
}

/**
 * ¿Está lista para enviar?
 */
public function estaListaParaEnviar(): bool
{
    return $this->estado === 'aprobada' && 
           $this->prendasCotizaciones()->count() > 0;
}

/**
 * Obtener tipo de cotización de forma más clara
 */
public function obtenerTipo(): string
{
    // La lógica que ya existe
    return $this->obtenerTipoCotizacion();
}
```

---

### Tarde (2 horas)

#### Tarea 4.3: Documentar métodos nuevos
```bash
# Crear documento
cat > docs/NUEVOS_METODOS_MODELS.md << 'EOF'
# Nuevos Métodos en Models

## Orden / PedidoProduccion
- puedeSerAprobada(): bool
- aprobar(): void
- calcularDiasDesdeCreacion(): int

## Cotizacion
- calcularTotal(): float
- estaListaParaEnviar(): bool
- obtenerTipo(): string

(Más por venir...)
EOF
```

---

### Entregable del Día 4

✅ Models con métodos de negocio  
✅ Lógica documentada  
✅ Preparado para refactorización controllers

**GIT COMMIT:**
```bash
git add app/Models/
git commit -m "refactor: agregar métodos de lógica a models

- Orden::puedeSerAprobada()
- Orden::aprobar()
- Cotizacion::calcularTotal()
- Cotizacion::estaListaParaEnviar()

Prepara models para refactorización de controllers"
```

---

## 📅 DÍA 5: CREAR TABLA UNIFICADA BD

### Objetivo
Crear nueva tabla `registro_piso` sin afectar las antiguas.

### Mañana (2 horas)

#### Tarea 5.1: Crear migración
```bash
php artisan make:migration create_registro_piso_table --create=registro_piso
```

#### Tarea 5.2: Definir estructura en migración
```php
// database/migrations/YYYY_MM_DD_HHMMSS_create_registro_piso_table.php

Schema::create('registro_piso', function (Blueprint $table) {
    $table->id();
    
    // Campo discriminador
    $table->enum('tipo', ['produccion', 'polos', 'corte']);
    
    // Campos comunes a todas las tablas
    $table->date('fecha');
    $table->string('modulo')->nullable();
    $table->string('orden_produccion')->nullable();
    $table->integer('cantidad')->default(0);
    // ... copiar otros campos que son comunes ...
    
    // Timestamps
    $table->timestamps();
    
    // Índices
    $table->index('tipo');
    $table->index('fecha');
    $table->index(['tipo', 'fecha']);
});
```

---

#### Tarea 5.3: Crear Model
```php
// app/Models/RegistroPiso.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroPiso extends Model
{
    protected $table = 'registro_piso';
    
    protected $fillable = [
        'tipo', 'fecha', 'modulo', 'orden_produccion',
        'cantidad', // ... otros campos
    ];
    
    protected $casts = [
        'tipo' => 'string',
        'fecha' => 'date',
    ];
    
    // Scopes para filtrar por tipo
    public function scopeProduccion($query)
    {
        return $query->where('tipo', 'produccion');
    }
    
    public function scopePolos($query)
    {
        return $query->where('tipo', 'polos');
    }
    
    public function scopeCorte($query)
    {
        return $query->where('tipo', 'corte');
    }
}
```

---

### Tarde (2-3 horas)

#### Tarea 5.4: Ejecutar migración
```bash
# Correr migración
php artisan migrate

# Verificar que tabla fue creada
php artisan tinker
> DB::table('registro_piso')->count()
# Debe ser 0
```

---

#### Tarea 5.5: Crear seeder para test
```php
// database/seeders/RegistroPisoSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RegistroPiso;

class RegistroPisoSeeder extends Seeder
{
    public function run()
    {
        // Crear 10 registros de prueba
        for ($i = 0; $i < 10; $i++) {
            RegistroPiso::create([
                'tipo' => ['produccion', 'polos', 'corte'][rand(0, 2)],
                'fecha' => now()->subDays(rand(0, 30)),
                'modulo' => 'Módulo ' . rand(1, 5),
                'orden_produccion' => 'OP-' . rand(1000, 9999),
                'cantidad' => rand(10, 100),
            ]);
        }
    }
}
```

#### Tarea 5.6: Probar Model
```bash
php artisan tinker
> RegistroPiso::produccion()->count()
> RegistroPiso::polos()->count()
> RegistroPiso::corte()->count()
```

---

### Entregable del Día 5

✅ Tabla `registro_piso` creada  
✅ Model `RegistroPiso` funcional  
✅ Datos de prueba cargados  
✅ Queries funcionan correctamente

**GIT COMMIT:**
```bash
git add database/migrations/
git add app/Models/RegistroPiso.php
git add database/seeders/RegistroPisoSeeder.php
git commit -m "feat: crear tabla unificada registro_piso

Nueva tabla consolida:
- registro_piso_produccion
- registro_piso_polo
- registro_piso_corte

Tablas antiguas se mantienen por compatibilidad"
```

---

## 📅 DÍA 6: CONSOLIDAR JAVASCRIPT

### Objetivo
Identificar y consolidar archivos JS duplicados.

### Mañana (2-3 horas)

#### Tarea 6.1: Auditoría definitiva
```bash
# Ver exactamente qué se está cargando en cada template
grep -n "orders-table" resources/views/orders/index.blade.php
grep -n "modern-table" resources/views/tableros.blade.php
grep -n "tableros.js" resources/views/tableros.blade.php

# Crear documento con matriz de dependencias
cat > docs/CONSOLIDACION-JS-PLAN.md << 'EOF'
# Consolidación JavaScript

## Matrices de Uso

### orders-table.js vs orders-table-v2.js
- Usado en: [listar templates]
- Versión moderna: orders-table-v2.js + modules/
- Acción: ELIMINAR orders-table.js

### modern-table.js vs orders js/modules/
- ¿Son iguales o diferentes?
- Si iguales: fusionar
- Si diferentes: separar claramente

### tableros.js
- Usado en: resources/views/tableros.blade.php
- Dependencias: modern-table.js ?
- Necesario: SÍ (lógica de tableros)
EOF
```

---

#### Tarea 6.2: Documentar decisiones
```markdown
# DECISIONES DE CONSOLIDACIÓN

## 1. Orders
✅ MANTENER: orders js/orders-table-v2.js + modules/
❌ ELIMINAR: orders js/orders-table.js (antiguo)

Razón: V2 es más modular y mantenible

## 2. Modern Table
[Necesita investigación]
- ¿Es diferente a orders?
- ¿Se puede consolidar?

## 3. Tableros
✅ MANTENER: tableros.js
❌ REVISAR: tableros-pagination.js (¿Es parte de tableros.js?)

## 4. Archivos huérfanos
- Buscar qué templates NO cargan estos archivos
- Eliminar si no se usan
```

---

### Tarde (2 horas)

#### Tarea 6.3: Crear plan de consolidación
```bash
# En archivo CONSOLIDACION-JS-PASOS.md
cat > docs/CONSOLIDACION-JS-PASOS.md << 'EOF'
# Pasos de Consolidación (No hacer aún, solo planificar)

## Semana 2 - Cuando esté todo el refactor backend listo:

### Paso 1: Backup
- git checkout -b feature/js-consolidation
- Hacer backup de public/js/

### Paso 2: Eliminar duplicados
- Eliminar orders-table.js (si es antiguo)
- Fusionar o separar modern-table vs orders

### Paso 3: Testing
- Verificar que todas las tablas funcionen
- Verificar en navegadores (Chrome, Firefox)

### Paso 4: Merge
- Hacer PR con cambios
- Code review
- Merge
EOF
```

---

### Entregable del Día 6

✅ Auditoría definitiva de JS  
✅ Plan de consolidación documentado  
✅ Decisiones formalizadas  
✅ LISTO para implementar semana próxima

---

## 📅 DÍA 7: INTEGRACIÓN Y PRUEBAS

### Objetivo
Verificar que todo funciona junto después de 6 días de cambios.

### Mañana (3 horas)

#### Tarea 7.1: Ejecutar suite de tests
```bash
# Tests unitarios
php artisan test

# Si no hay tests, crear uno rápido para verificar
php artisan make:test ProduccionCalculadoraServiceTest

# En el test:
public function test_calcula_seguimiento_modulos()
{
    $service = new ProduccionCalculadoraService();
    $registros = RegistroPisoProduccion::limit(10)->get();
    
    $resultado = $service->calcularSeguimientoModulos($registros);
    
    $this->assertIsArray($resultado);
}
```

---

#### Tarea 7.2: Testing manual en navegador
```
[ ] Ir a http://localhost/tableros
    - ¿Carga la página?
    - ¿Se ven los datos?
    - ¿Funcionan los filtros?

[ ] Ir a http://localhost/tableros?section=corte
    - ¿Carga sin errores?

[ ] Ir a http://localhost/ordenes
    - ¿Carga correctamente?
    - ¿Se pueden crear órdenes?

[ ] Abrir Developer Tools (F12)
    - ¿Hay errores en console?
    - ¿Hay advertencias?

[ ] Crear un registro nuevo
    - ¿Se persiste en BD?
    - ¿Se ve en la UI?

[ ] Filtrar datos
    - ¿Filtra correctamente?
    - ¿Los resultados son precisos?
```

---

#### Tarea 7.3: Verificar logs
```bash
# No debe haber errores
tail -100 storage/logs/laravel.log | grep -i error

# Debe mostrar algo como:
# Log entry (sin errores)

# Si hay errores, investigar y documentar
```

---

### Tarde (2 horas)

#### Tarea 7.4: Documentar estado actual
```markdown
# RESUMEN ESTADO DESPUÉS DE 7 DÍAS

## ✅ COMPLETADO

### Backend
- [ ] ProduccionCalculadoraService funcional
- [ ] FiltrosService funcional
- [ ] OperarioService funcional
- [ ] MaquinaService funcional
- [ ] TelaService funcional

### Models
- [ ] Orden con métodos nuevos
- [ ] Cotizacion con métodos nuevos
- [ ] RegistroPiso Model creado

### Base de Datos
- [ ] Tabla registro_piso creada
- [ ] Model RegistroPiso funcional
- [ ] Scopes (produccion, polos, corte) funcionan

### Testing
- [ ] Todas las vistas cargan
- [ ] Sin errores en console
- [ ] Datos persisten correctamente

## 📊 MÉTRICAS

| Métrica | Valor |
|---------|-------|
| Líneas TablerosController | 2,118 → 1,200 (aprox) |
| Services creados | 5 |
| Models mejorados | 2+ |
| Funcionalidad rota | 0% |
| Performance afectada | 0% |

## ⬜ PENDIENTE

- [ ] Refactorizar TablerosController aún más
- [ ] Dividir en sub-controllers
- [ ] Consolidar JS frontend
- [ ] Crear tests unitarios completos
- [ ] Documentación completa

## 🎯 PRÓXIMOS PASOS

Semana 2:
- Dividir TablerosController en sub-controllers
- Migrar datos de tablas antiguas a registro_piso
- Crear tests más completos
```

---

#### Tarea 7.5: Hacer commit final y crear PR
```bash
# Ver cambios totales
git diff --stat

# Hacer commit final
git add -A
git commit -m "refactor week-1: estructura services, models, y tabla BD

🔄 Backend:
- ProduccionCalculadoraService
- FiltrosService
- OperarioService, MaquinaService, TelaService

📦 Models enriquecidos:
- Orden::puedeSerAprobada()
- Cotizacion::calcularTotal()

🗄️ Base de datos:
- Nueva tabla registro_piso (unificada)
- Model RegistroPiso con scopes

✅ Todo funcional, sin breaking changes"

# Crear rama para semana 2
git checkout -b feature/refactor-week-2-controllers
```

---

### Entregable del Día 7

✅ Sistema funcional después de refactor  
✅ Sin errores en logs  
✅ Documentación actualizada  
✅ LISTO para Semana 2

---

## 📋 CHECKLIST FINAL - 7 DÍAS

```
SEMANA 1 - AUDITORÍA Y FOUNDATION

Día 1: Auditoría
✅ TablerosController mapeado (2,118 líneas)
✅ BD duplicación documentada
✅ JS frontend auditado
✅ Métodos categorizados

Día 2: Crear Services
✅ BaseService
✅ ProduccionCalculadoraService
✅ FiltrosService
✅ OperarioService, MaquinaService, TelaService

Día 3: Inyectar Services
✅ Services en TablerosController
✅ Métodos reemplazados sin cambiar funcionalidad
✅ Testing en navegador

Día 4: Enriquecer Models
✅ Orden con métodos nuevos
✅ Cotizacion con métodos nuevos
✅ Documentación de métodos nuevos

Día 5: Tabla BD Unificada
✅ Migración creada
✅ Model RegistroPiso
✅ Scopes produccion, polos, corte
✅ Datos de prueba

Día 6: Plan JS Consolidation
✅ Auditoría final de JS
✅ Plan documentado
✅ Decisiones formalizadas

Día 7: Integración Total
✅ Todo funciona junto
✅ Sin errores
✅ Documentación actualizada

TOTAL: 7 días de refactor seguro y progresivo
```

---

## 🚨 SI ALGO FALLA

```
Problema: Error en TablerosController después de cambios
Solución: git checkout app/Http/Controllers/TablerosController.php
Impacto: Vuelve a versión anterior, puedes intentar nuevamente

Problema: Migración BD no funciona
Solución: php artisan migrate:rollback
Impacto: Elimina tabla registro_piso, vuelves al estado anterior

Problema: Service no se inyecta correctamente
Solución: Verificar namespace y que existe el provider

Recuerda: TODO está en git, puedes revertir en cualquier momento
```

---

*Este plan es incremental, seguro y NO rompe nada existente.*  
*Puedes empezar MAÑANA mismo sin riesgo.*

