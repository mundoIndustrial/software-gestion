# Integración del Sistema de Demoras en Insumos

## 📊 Arquitectura General

```
Frontend (Vue/JavaScript)
         ↓
    HTTP Request
         ↓
   [API Endpoint] (/api/insumos/calcular-demora)
         ↓
[InsumosApiController - Infrastructure] 
         ↓
[CalculadorDemoraService - Domain Service]
         ↓
[DiasDemora - ValueObject + CalculadorDiasService]
         ↓
   HTTP Response (JSON)
```

---

## 🏗️ Capas de la Aplicación

### 1. **Domain Layer** (app/Domain/Insumos/)

#### ValueObject: `DiasDemora`
- **Ubicación**: `app/Domain/Insumos/ValueObjects/DiasDemora.php`
- **Responsabilidad**: Encapsular días + estado + colores
- **Estados**:
  - `RAPIDO` (≤ 5 días) → Verde (#10b981)
  - `NORMAL` (5-10 días) → Amarillo (#f59e0b)
  - `LENTO` (10-20 días) → Naranja (#f97316)
  - `CRITICO` (> 20 días) → Rojo (#ef4444)

**Métodos Clave**:
```php
$demora = new DiasDemora(7);
$demora->getDias();          // 7
$demora->getEstado();        // "normal"
$demora->getClaseBg();       // "bg-yellow-100" (Tailwind)
$demora->getColorHex();      // "#f59e0b" (Para gráficos)
$demora->toArray();          // Array completo para JSON
(string) $demora;            // "7 días"
```

#### Domain Service: `CalculadorDemoraService`
- **Ubicación**: `app/Domain/Insumos/Services/CalculadorDemoraService.php`
- **Responsabilidad**: Orquestar cálculos usando CalculadorDiasService
- **Métodos**:
  - `calcularDemora($fecha_pedido, $fecha_llegada): DiasDemora`
  - `calcularDemoraParaMateriales(array $materiales): array`
  - `resumirDemorasPorEstado(array $materiales): array`
  - `esCritica($dias): bool`
  - `esNormal($dias): bool`

---

### 2. **Infrastructure Layer** (app/Infrastructure/)

#### API Controller: `InsumosApiController`
- **Ubicación**: `app/Infrastructure/Insumos/Controllers/Api/InsumosApiController.php`
- **Responsabilidad**: Exponer servicios de dominio al frontend
- **Endpoints**:
  
  ```php
  POST /api/insumos/calcular-demora
  // Request:
  {
      "fecha_pedido": "2026-03-01",
      "fecha_llegada": "2026-03-10"
  }
  
  // Response:
  {
      "success": true,
      "dias": 7,
      "estado": "normal",
      "texto": "7 días",
      "clase_bg": "bg-yellow-100",
      "clase_text": "text-yellow-700",
      "color_hex": "#f59e0b",
      "data": {...}
  }
  ```
  
  ```php
  POST /api/insumos/calcular-demoras-bulk
  // Para múltiples materiales
  ```
  
  ```php
  GET /api/insumos/demora-critica?dias=25
  // Evalúa si es crítica
  ```

#### Rutas API
- **Archivo**: `routes/api.php` (líneas ~810-835)
- **Grupo**: `prefix('insumos')`
- **Middleware**: `['auth']`

---

### 3. **Application Layer** (app/Services/)

#### Service: `MaterialesService`
- **Ubicación**: `app/Services/Insumos/MaterialesService.php`
- **Responsabilidad**: Lógica de negocio de materiales + integración con demoras
- **Métodos Nuevos/Actualizados**:
  
  ```php
  // Nuevo: Con soporte de demoras
  obtenerMaterialesFiltrados(
      $filtros = [],
      $perPage = 25,
      $conDemora = true  // ← Parámetro nuevo
  )
  
  // Nuevo: Enriquecer materiales con demoras
  obtenerResumenDemorasPorPedido($numeroPedido)
  
  // Privado: Enriquecimiento interno
  enriquecerMaterialesConDemora($materiales)
  ```

**Inyección De Dependencias**:
```php
public function __construct(
    MaterialesRepository $repository,
    CalculadorDemoraService $calculadorDemora = null
) {
    $this->repository = $repository;
    $this->calculadorDemora = $calculadorDemora ?? app(CalculadorDemoraService::class);
}
```

---

### 4. **HTTP Layer** (app/Http/Controllers/)

#### Controller: `MaterialesController`
- **Ubicación**: `app/Http/Controllers/Insumos/MaterialesController.php`
- **Cambios**:
  - `index()`: Ahora soporta `?con_demora=true/false`
  - `show()`: Retorna `resumen_demoras` junto con materiales

**Uso**:
```php
// En el blade o JS frontend:
GET /insumos/materiales?con_demora=true

// Retorna materiales enriquecidos con objeto demora:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "numero_pedido": "123",
            "descripcion": "Material A",
            "demora": {
                "dias": 7,
                "estado": "normal",
                "clase_bg": "bg-yellow-100",
                ...
            }
        }
    ]
}
```

---

## 🔌 Frontend Integration

### JavaScript (public/js/insumos/utilities.js)

```javascript
// Función para calcular demora con backend
async function calcularDemoraAsync(fechaPedido, fechaLlegada) {
    const response = await fetch('/api/insumos/calcular-demora', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            fecha_pedido: fechaPedido,
            fecha_llegada: fechaLlegada
        })
    });
    return await response.json();
}

// Uso:
const demora = await calcularDemoraAsync('2026-03-01', '2026-03-10');
console.log(demora.dias, demora.estado, demora.clase_bg);
```

### HTML Usage

```html
<div id="demora-container"></div>

<script>
(async () => {
    const demora = await window.calcularDemoraAsync('2026-03-01', '2026-03-10');
    
    const div = document.getElementById('demora-container');
    div.innerHTML = `
        <span class="px-3 py-1 rounded-full text-sm font-medium ${demora.clase_bg} ${demora.clase_text}">
            ${demora.texto}
        </span>
    `;
})();
</script>
```

---

## 📋 Flujo de Datos Completo

### Caso 1: Calcular demora simple
```
Frontend: calcularDemoraAsync('2026-03-01', '2026-03-10')
    ↓
POST /api/insumos/calcular-demora
    ↓
InsumosApiController::calcularDemora()
    ↓
CalculadorDemoraService::calcularDemora()
    ↓
CalculadorDiasService::calcularDiasHabiles() [Existing Service]
    ↓
return new DiasDemora(7)
    ↓
$demora->toArray() [JSON Response]
    ↓
Frontend: Recibe {dias: 7, estado: 'normal', ...}
```

### Caso 2: Obtener materiales con demoras
```
Frontend: GET /insumos/materiales?con_demora=true
    ↓
MaterialesController::index()
    ↓
MaterialesService::obtenerMaterialesFiltrados(..., true)
    ↓
enriquecerMaterialesConDemora($materiales)
    ↓
Para cada material:
    - CalculadorDemoraService::calcularDemora()
    - Agrega $material['demora'] = $demora->toArray()
    ↓
Frontend: Recibe materiales con ['demora'] en cada uno
```

---

## 🧪 Testing

### Test ValueObject DiasDemora

```php
// app/Tests/Unit/Domain/Insumos/DiasDemoraTest.php

public function test_estado_rapido()
{
    $demora = new DiasDemora(3);
    $this->assertEquals('rapido', $demora->getEstado());
    $this->assertEquals('bg-green-100', $demora->getClaseBg());
}

public function test_estado_critico()
{
    $demora = new DiasDemora(25);
    $this->assertEquals('critico', $demora->getEstado());
    $this->assertEquals('bg-red-100', $demora->getClaseBg());
}
```

### Test API Endpoint

```php
// app/Tests/Feature/Api/InsumosApiTest.php

public function test_calcular_demora()
{
    $response = $this->postJson('/api/insumos/calcular-demora', [
        'fecha_pedido' => '2026-03-01',
        'fecha_llegada' => '2026-03-08'
    ]);
    
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'estado' => 'normal',
        ])
        ->assertJsonStructure(['dias', 'estado', 'clase_bg', 'color_hex']);
}
```

---

## 📝 Estructura de Carpetas Final

```
app/
├── Domain/Insumos/
│   ├── ValueObjects/
│   │   └── DiasDemora.php                    ✅ CREADO
│   ├── Services/
│   │   └── CalculadorDemoraService.php       ✅ CREADO
│   ├── Entities/
│   ├── Repositories/
│   └── ...
├── Infrastructure/Insumos/
│   └── Controllers/Api/
│       └── InsumosApiController.php          ✅ CREADO
├── Services/Insumos/
│   └── MaterialesService.php                 ✅ ACTUALIZADO
├── Http/Controllers/Insumos/
│   └── MaterialesController.php              ✅ ACTUALIZADO
└── ...

routes/
└── api.php                                   ✅ ACTUALIZADO (Rutas insumos)

public/js/insumos/
└── utilities.js                              ✅ ACTUALIZADO (calcularDemoraAsync)
```

---

## ⚙️ Próximos Pasos Opcionales

1. **Tests Unitarios**
   - [ ] Test DiasDemora con rangos de días
   - [ ] Test CalculadorDemoraService
   - [ ] Test API endpoints

2. **Dashboard/Reportes**
   - [ ] Usar `resumirDemorasPorEstado()` para gráficos
   - [ ] Mostrar resumen por estado en vista

3. **Caché**
   - [ ] Cachear demoras calculadas
   - [ ] Cache key basado en fechas

4. **Eventos**
   - [ ] Event cuando una demora es CRITICA
   - [ ] Notificación a supervisor

---

## 🔑 Puntos Clave

✅ **DDD Bien Implementado**: Toda la lógica de negocio está en Domain
✅ **Separación de Responsabilidades**: Domain → Application → Infrastructure
✅ **ValueObject Inmutable**: DiasDemora es seguro y comparables por valor
✅ **Reutilizable**: API expone el servicio para cualquier cliente
✅ **Backward Compatible**: Parámetro opcional en MaterialesService
✅ **Lazy Loading**: CalculadorDemoraService se carga si no se inyecta

---

**Generado**: 12-03-2026
**Versión**: 1.0 - Integración Completa
