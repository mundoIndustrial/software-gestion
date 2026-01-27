# 🏗️ ARQUITECTURA: EDICIÓN SEGURA DE PRENDAS SEPARADA DE CREACIÓN

**Fecha:** 27 de enero de 2026  
**Estado:** ✅ Implementada  
**Contexto:** Separación de responsabilidades entre construcción de estado (creación) y modificación parcial (edición)

---

## 📋 ÍNDICE

1. [Visión General](#visión-general)
2. [Principios de Diseño](#principios-de-diseño)
3. [Arquitectura Separada](#arquitectura-separada)
4. [Componentes](#componentes)
5. [Reglas de Negocio](#reglas-de-negocio)
6. [Ejemplos de Uso](#ejemplos-de-uso)
7. [Endpoints API](#endpoints-api)
8. [Migración de Código](#migración-de-código)

---

## 🎯 VISIÓN GENERAL

### El Problema

Existía una lógica que **extrae toda la información desde el DOM** para crear prendas cuando el pedido no existe. Esta lógica funcionaba bien **SOLO para creación** pero NO debería reutilizarse para editar.

Razones:
- **Creación:** Construye estado completo desde cero (PUT)
- **Edición:** Modifica solo lo enviado (PATCH)
- **Diferencia crítica:** Editar NO es reconstruir

### La Solución

Implementamos **dos arquitecturas completamente separadas**:

```
┌─────────────────────────────────────────────────────────────┐
│                      ARQUITECTURA DUAL                       │
├──────────────────────────┬──────────────────────────────────┤
│                          │                                  │
│   CREACIÓN (Constructor) │   EDICIÓN (Parche)              │
│   ════════════════════   │   ════════════════               │
│                          │                                  │
│   • Extrae TODO del DOM  │   • Solo campos explícitos       │
│   • Construye completo   │   • Preserva lo no mencionado   │
│   • PUT (reemplaza)      │   • PATCH (modifica)             │
│   • PrendaDataBuilder    │   • EditPrendaPedidoDTO          │
│   • Responsable: JS      │   • Responsable: Backend         │
│                          │                                  │
│   Ubicación:             │   Ubicación:                     │
│   JavaScript Builder     │   Services + Controllers         │
│                          │                                  │
│   Modelos:               │   Modelos:                       │
│   prenda-data-builder.js │   PrendaPedidoEditService        │
│                          │   PrendaVariantePedidoEditService│
│                          │                                  │
└──────────────────────────┴──────────────────────────────────┘
```

---

## 🔒 PRINCIPIOS DE DISEÑO

### 1. **Separación Estricta de Responsabilidades**

```
CREACIÓN                          EDICIÓN
─────────────────────────────────────────────────────────────
Construye estado inicial      →   Modifica estado existente
Valida estructura completa    →   Valida cambios parciales
Crea relaciones nuevas        →   MERGE relaciones
Extrae datos de DOM           →   Recibe JSON explícito
SRP: Constructor              →   SRP: Modificador
```

### 2. **PATCH vs PUT**

```
PUT (Reemplazo completo - NO usamos para editar):
  Cliente envía: {"nombre": "X", "cantidad": 100}
  Sistema hace:  Borra TODO, recrea con lo enviado
  Resultado:     Perderían colores, telas, procesos ❌

PATCH (Modificación parcial - Sí usamos para editar):
  Cliente envía: {"nombre": "X"}
  Sistema hace:  Actualiza solo nombre
  Resultado:     Cantidad, colores, telas, procesos intactos ✅
```

### 3. **MERGE en Relaciones**

```
Relación actual en BD:     Payload enviado:
- Color 1 (id: 5)         [
- Color 2 (id: 7)           {"id": 5, "color_id": 10},  ← UPDATE
- Color 3 (id: 9)           {"color_id": 12}             ← CREATE
                          ]

Resultado final:
- Color con id 5 → Actualizado a color_id 10
- Color 2 (id: 7) → CONSERVADO (no mencionado)
- Color 3 (id: 9) → CONSERVADO (no mencionado)
- Color 4 → CREADO con color_id 12
```

### 4. **Garantías de Negocio**

✅ **Permitido:**
- Actualizar nombre, descripción, cantidad
- Agregar tallas nuevas
- MERGE de variantes/colores/telas
- Reducir cantidad si no hay procesos

❌ **Prohibido:**
- Editar procesos (endpoint separado)
- Reducir talla por debajo de cantidad en procesos
- Recrear relaciones completas
- Borrar relaciones sin request explícito

---

## 🏛️ ARQUITECTURA SEPARADA

### Flujo de CREACIÓN (Actual)

```
Frontend (JavaScript)
        ↓
   PrendaDataBuilder.js
   ├─ Extrae del DOM
   ├─ Construye completo
   └─ Envía POST
        ↓
   POST /crear-pedido
   (Controlador)
        ↓
   PrendaCreationService
   └─ Valida estructura
   └─ Crea en BD
```

### Flujo de EDICIÓN (Nuevo)

```
Frontend (JavaScript)
        ↓
   Estado JSON
   {
     "nombre_prenda": "Nuevo nombre",
     "tallas": [...]
   }
        ↓
   PATCH /api/prendas-pedido/{id}/editar
        ↓
   PrendaPedidoEditController
        ↓
   EditPrendaPedidoDTO (DTO específico para edición)
   └─ Solo acepta campos explícitos
   └─ ignora campos no enviados
        ↓
   PrendaEditSecurityValidator
   └─ Valida restricciones de negocio
        ↓
   PrendaPedidoEditService
   ├─ updateBasicFields() → Actualiza campos simples
   ├─ updateRelationships() → MERGE en relaciones
   └─ MergeRelationshipStrategy
        ↓
   BD (PATCH actualización)
```

---

## 🧩 COMPONENTES

### 1. DTOs de Edición

#### `EditPrendaPedidoDTO`

```php
class EditPrendaPedidoDTO
{
    public ?int $id;
    public ?string $nombre_prenda;
    public ?string $descripcion;
    public ?int $cantidad;
    public ?bool $de_bodega;
    public ?array $tallas;        // MERGE
    public ?array $variantes;     // MERGE
    public ?array $colores;       // MERGE
    public ?array $telas;         // MERGE
    
    // Garantía: Solo campos explícitos
    public function getExplicitFields(): array
    {
        return array_filter([...], fn($v) => $v !== null);
    }
}
```

**Diferencia con CreationDTO:**
- ✅ Todos los campos son opcionales (PATCH)
- ✅ Ignora campos no mencionados
- ✅ No fuerza estructura completa
- ✅ MERGE en relaciones (no replace)

#### `EditPrendaVariantePedidoDTO`

Idem para variantes con sus campos específicos.

### 2. Strategy de MERGE

#### `MergeRelationshipStrategy`

```php
public static function merge(
    Model $parent,
    string $relationship,
    array $payload,
    array $relationshipConfig = []
): void
{
    foreach ($payload as $item) {
        // Si viene ID → UPDATE
        if (isset($item['id'])) {
            $existingModel->update($item);
        } 
        // Si NO viene ID → CREATE
        else {
            $parent->$relationship()->create($item);
        }
    }
    // IMPORTANTE: No borra nada que no venga en payload
}
```

**Métodos disponibles:**
- `mergeColores()` - Actualiza colores con MERGE
- `mergeTelas()` - Actualiza telas con MERGE
- `mergeTallas()` - Actualiza tallas con MERGE
- `mergeVariantes()` - Actualiza variantes con MERGE

### 3. Validator de Restricciones

#### `PrendaEditSecurityValidator`

```php
public static function validateEdit(
    PrendaPedido $prenda,
    EditPrendaPedidoDTO $dto
): void
{
    // Valida cantidad vs procesos
    if ($dto->hasField('cantidad')) {
        self::validateCantidadChange($prenda, $dto->cantidad);
    }
    
    // Valida tallas vs procesos
    if ($dto->hasField('tallas')) {
        self::validateTallasChange($prenda, $dto->tallas);
    }
}
```

**Restricciones:**
- ✅ Cantidad NO menor que cantidad en procesos
- ✅ Talla NO reduce por debajo de procesos
- ✅ NO permite editar procesos desde aquí

### 4. Servicios de Edición

#### `PrendaPedidoEditService`

```php
public function edit(
    PrendaPedido $prenda,
    EditPrendaPedidoDTO $dto
): array
{
    DB::beginTransaction();
    try {
        // 1. Validar restricciones
        PrendaEditSecurityValidator::validateEdit($prenda, $dto);
        
        // 2. Actualizar campos simples
        $simpleFields = $dto->getSimpleFields();
        if (!empty($simpleFields)) {
            $this->updateBasicFields($prenda, $simpleFields);
        }
        
        // 3. MERGE relaciones
        $relationships = $dto->getRelationshipFields();
        if (!empty($relationships)) {
            $this->updateRelationships($prenda, $relationships);
        }
        
        DB::commit();
        return ['success' => true];
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

**Métodos:**
- `edit()` - Edición completa (PATCH)
- `updateBasic()` - Solo campos simples
- `updateTallas()` - Solo tallas
- `updateVariantes()` - Solo variantes
- `updateSingleVariante()` - Una variante específica
- `getCurrentState()` - Estado actual (para auditoría)

---

## 📋 REGLAS DE NEGOCIO

### Cantidad Total

```
❌ NO permitido: cantidad < cantidad_en_procesos

Ejemplo:
  Cantidad actual: 100
  Procesos asignados: 80
  
  ✅ Cambiar a 100, 150, 200... OK
  ❌ Cambiar a 70, 50, 30... ERROR
```

### Tallas

```
MERGE Strategy:
  Si viene {"id": 1, "cantidad": 50} → UPDATE
  Si viene {"genero": "dama", "talla": "M", "cantidad": 20} → CREATE
  Si NO viene → CONSERVA

Restricción:
  ❌ No reducir cantidad < cantidad_en_procesos para esa talla
```

### Variantes

```
Estructura de Merge:
  [
    {"id": 1, "tipo_manga_id": 2},  // UPDATE: solo actualiza tipo_manga_id
    {"tipo_manga_id": 3},             // CREATE: nueva variante
    {"id": 2, "tiene_bolsillos": true} // UPDATE: solo ese campo
  ]
  
Relaciones de variante (Colores, Telas):
  ├─ UPDATE si viene con "id"
  ├─ CREATE si viene sin "id"
  └─ CONSERVA si NO viene en payload
```

### Procesos

```
❌ NO se pueden editar desde este endpoint
❌ NO se pueden crear
❌ NO se pueden eliminar

→ Use endpoint separado: /api/procesos/{id}/editar
```

---

## 💡 EJEMPLOS DE USO

### Caso 1: Actualizar solo nombre

```http
PATCH /api/prendas-pedido/42/editar
Content-Type: application/json

{
  "nombre_prenda": "CAMISA POLO NUEVA"
}

Response:
{
  "success": true,
  "message": "Prenda actualizada exitosamente",
  "prenda_id": 42,
  "fields_updated": ["nombre_prenda"]
}
```

**Resultado en BD:**
- ✅ nombre_prenda = "CAMISA POLO NUEVA"
- ✅ Todas las demás propiedades intactas
- ✅ Relaciones sin cambios

---

### Caso 2: Actualizar cantidad con validación de procesos

```http
PATCH /api/prendas-pedido/42/editar
Content-Type: application/json

{
  "cantidad": 80  // Intenta reducir
}
```

**Validación:**
```
Cantidad en procesos: 50
Nueva cantidad: 80
✅ Permitido (80 >= 50)
```

```http
PATCH /api/prendas-pedido/42/editar
Content-Type: application/json

{
  "cantidad": 40  // Intenta reducir más
}
```

**Validación:**
```
Cantidad en procesos: 50
Nueva cantidad: 40
❌ Error: No se puede reducir cantidad por debajo de 50
```

---

### Caso 3: MERGE de tallas

```http
PATCH /api/prendas-pedido/42/editar/tallas
Content-Type: application/json

{
  "tallas": [
    {"id": 1, "cantidad": 60},                      // UPDATE: cambiar cantidad
    {"genero": "dama", "talla": "XL", "cantidad": 10} // CREATE: nueva talla
  ]
}
```

**Antes:**
```
Talla 1 (dama, M): 50
Talla 2 (dama, L): 30
```

**Después:**
```
Talla 1 (dama, M): 60     ← Actualizado
Talla 2 (dama, L): 30     ← Conservado
Talla 3 (dama, XL): 10    ← Creado
```

---

### Caso 4: MERGE de variantes con relaciones

```http
PATCH /api/prendas-pedido/42/editar
Content-Type: application/json

{
  "variantes": [
    {
      "id": 1,
      "tipo_manga_id": 2,
      "colores": [
        {"id": 5, "color_id": 3},  // UPDATE: cambiar color
        {"color_id": 7}             // CREATE: agregar color
      ]
    },
    {
      "tipo_manga_id": 3,           // CREATE: nueva variante
      "tiene_bolsillos": true
    }
  ]
}
```

**Garantías:**
- ✅ Variante 1: actualizada con tipo_manga_id y colores mergeados
- ✅ Variante 2: conservada (no mencionada)
- ✅ Nueva variante: creada
- ✅ Colores no mencionados: conservados
- ✅ Telas: intactas (no mencionadas)

---

### Caso 5: Editar solo una variante

```http
PATCH /api/prendas-pedido/42/variantes/1/editar
Content-Type: application/json

{
  "tipo_manga_id": 3,
  "tiene_bolsillos": true
}
```

---

### Caso 6: MERGE de colores en variante

```http
PATCH /api/prendas-pedido/42/variantes/1/colores
Content-Type: application/json

{
  "colores": [
    {"id": 2, "color_id": 5},  // UPDATE
    {"color_id": 8}             // CREATE
  ]
}
```

---

## 🌐 ENDPOINTS API

### Prenda Completa

```
PATCH  /api/prendas-pedido/{id}/editar
       Editar prenda completa (PATCH)

PATCH  /api/prendas-pedido/{id}/editar/campos
       Editar solo campos simples

PATCH  /api/prendas-pedido/{id}/editar/tallas
       Editar solo tallas (MERGE)

GET    /api/prendas-pedido/{id}/estado
       Obtener estado actual (para auditoría)
```

### Variantes

```
PATCH  /api/prendas-pedido/{prendaId}/variantes/{varianteId}/editar
       Editar variante completa

PATCH  /api/prendas-pedido/{prendaId}/variantes/{varianteId}/editar/campos
       Editar solo campos simples de variante

PATCH  /api/prendas-pedido/{prendaId}/variantes/{varianteId}/colores
       Editar solo colores (MERGE)

PATCH  /api/prendas-pedido/{prendaId}/variantes/{varianteId}/telas
       Editar solo telas (MERGE)

GET    /api/prendas-pedido/{prendaId}/variantes/{varianteId}/estado
       Obtener estado de variante (para auditoría)
```

---

## 🔄 MIGRACIÓN DE CÓDIGO

### ❌ ANTES (Incorrecto)

```javascript
// Frontend: Extraía datos del DOM y los enviaba como PUT
const datos = PrendaDataBuilder.extraerTodo();
await fetch(`/api/prendas-pedido/${id}`, {
    method: 'PUT',  // ❌ Reemplaza completo
    body: JSON.stringify(datos)
});

// Backend: Reemplazaba la prenda completa
// Pérdida de: colores, telas, procesos no mencionados
```

### ✅ DESPUÉS (Correcto)

```javascript
// Frontend: Envía solo lo que cambió
const cambios = {
    nombre_prenda: "Nuevo nombre"
    // Solo esto, nada más
};

await fetch(`/api/prendas-pedido/${id}/editar`, {
    method: 'PATCH',  // ✅ PATCH: modificación parcial
    body: JSON.stringify(cambios)
});

// Backend: Aplica PATCH seguro
// Garantía: Solo nombre cambia, todo lo demás intacto
```

---

## 📊 Comparativa Arquitectura

```
                    CREACIÓN          │    EDICIÓN
────────────────────────────────────────────────────────
HTTP Verb           POST              │    PATCH
DTO Type            CreationDTO       │    EditDTO
Responsable         JavaScript        │    Backend
Extraer datos       DOM               │    JSON
Relaciones          Replace all       │    MERGE
Campos no enviados  Required          │    Ignored
Procesos            ✅ No se tocan    │    ✅ Prohibido
────────────────────────────────────────────────────────
```

---

## 🎯 Checklist de Implementación

- ✅ DTOs separados (EditPrendaPedidoDTO, EditPrendaVariantePedidoDTO)
- ✅ Strategy MERGE (MergeRelationshipStrategy)
- ✅ Validator de restricciones (PrendaEditSecurityValidator)
- ✅ Servicio PrendaPedidoEditService
- ✅ Servicio PrendaVariantePedidoEditService
- ✅ Controller PrendaPedidoEditController
- ✅ Rutas API PATCH separadas
- ✅ Documentación
- ⏳ Tests automatizados (próxima fase)
- ⏳ Migración de JS frontend (próxima fase)

---

## 🔐 Garantías de Seguridad

✅ **Transacciones ACID** - Todas las operaciones en transacciones  
✅ **Validación de restricciones** - Cantidad vs procesos  
✅ **MERGE seguro** - No borra relaciones implícitamente  
✅ **Separación de responsabilidades** - Creación ≠ Edición  
✅ **Auditoría** - Estados antes/después disponibles  
✅ **Campos protegidos** - No se pueden editar IDs, timestamps, procesos  

---

## 📝 Notas de Desarrollo

### Para Frontend
- Use PATCH, no PUT
- Envíe solo cambios, no estado completo
- Consulte estado actual con GET si es necesario
- Manténgase separado del PrendaDataBuilder (creación)

### Para Backend
- Use el Service, no el DTO directamente
- Confíe en el Validator para restricciones
- Use MergeRelationshipStrategy para relaciones
- Log de cambios antes/después (auditoría)

### Para Testing
- Pruebe MERGE con IDs existentes
- Pruebe MERGE sin IDs (creates)
- Pruebe MERGE sin mencionar relaciones (conserva)
- Pruebe validación de cantidad vs procesos
- Pruebe campos protegidos (error 422)

---

**Fin de Documentación**  
Implementado: 27/01/2026
