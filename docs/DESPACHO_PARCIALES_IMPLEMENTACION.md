# Implementación: Guardado de Despachos Parciales por Talla

##  Estado de Implementación

La funcionalidad de guardado de despachos parciales por talla está **completamente implementada** siguiendo los requisitos especificados.

---

## 📋 Especificación Técnica Implementada

###  Objetivo
Guardar despachos parciales de prendas y EPP en múltiples entregas, sin validaciones matemáticas automáticas. Cada fila (prenda/EPP + talla) genera un registro independiente en `despacho_parciales`.

### 🔒 Campos NO Editables (Solo Lectura)
- **Descripción**: Nombre de la prenda/EPP (mostrarse de `PrendaPedido.nombre_prenda + talla`)
- **Talla**: Talla asociada (mostrarse de `PrendaPedidoTalla.talla`)
- **Cantidad**: Cantidad total (mostrarse de `PrendaPedidoTalla.cantidad` para prendas, `PedidoEpp.cantidad` para EPP)

Estos campos **NO se modifican** ni se validan, solo se usan como referencia visual.

### ✍️ Campos EDITABLES (Entrada Manual)
El usuario **debe escribir manualmente** estos valores sin cálculos automáticos:

1. **Pendiente Inicial** - Cantidad inicial pendiente de despachar
2. **Parcial 1** - Cantidad despachada en primer envío
3. **Pendiente 1** - Cantidad pendiente tras primer envío
4. **Parcial 2** - Cantidad despachada en segundo envío
5. **Pendiente 2** - Cantidad pendiente tras segundo envío
6. **Parcial 3** - Cantidad despachada en tercer envío
7. **Pendiente 3** - Cantidad pendiente tras tercer envío

**Regla estricta**: Los valores se guardan exactamente como el usuario los digita, sin validaciones matemáticas ni cálculos automáticos.

---

## 🏗️ Arquitectura Implementada

### Stack Técnico
- **Frontend**: Blade + JavaScript vanilla (sin dependencias)
- **Backend**: Laravel DDD (Domain-Driven Design)
- **Base de Datos**: Tabla `despacho_parciales`

### Capas Implementadas

#### 1. **Infrastructure Layer** (HTTP)
- **Controlador**: `DespachoController`
  - `index()` - Listar pedidos disponibles
  - `show($pedido)` - Mostrar tabla de despacho
  - `guardarDespacho()` - Procesar POST
  - `obtenerDespachos()` - Obtener datos guardados (GET)

#### 2. **Application Layer** (Use Cases)
- **`ObtenerFilasDespachoUseCase`**
  - `obtenerPrendas($pedidoId)` - Obtener prendas con tallas
  - `obtenerEpp($pedidoId)` - Obtener EPPs
  - `obtenerTodas($pedidoId)` - Obtener todo unificado

- **`GuardarDespachoUseCase`**
  - `ejecutar(ControlEntregasDTO)` - Guardar despachos
  - Mapea campos: `pendiente_inicial`, `parcial_1-3`, `pendiente_1-3`, `talla_id`
  - No realiza validaciones matemáticas

#### 3. **Domain Layer** (Servicios)
- **`DespachoGeneradorService`**
  - Genera filas desde prendas + EPP
  - Retorna `FilaDespachoDTO` con `tallaId`

- **`DespachoValidadorService`**
  - Valida solo valores negativos (no permite negativos)
  - NO valida coherencia matemática
  - NO valida contra cantidad disponible

- **`DesparChoParcialesPersistenceService`**
  - `crearYGuardarMultiples()` - Guardar batch de despachos
  - Pasa `talla_id` y `pendiente_inicial` a la entidad

#### 4. **Domain Layer** (Entidades)
- **`DesparChoParcial`**
  - Entidad immutable
  - Factory methods: `crear()`, `reconstruir()`
  - Getters para todos los campos incluyendo `tallaId()`

#### 5. **Infrastructure Layer** (Persistencia)
- **`DesparChoParcialesRepositoryImpl`**
  - Implementa `DesparChoParcialesRepository`
  - Convierte Eloquent ↔ Entidades Domain
  - Maneja `talla_id` correctamente

- **`DesparChoParcialesModel`** (Eloquent)
  - Tabla: `despacho_parciales`
  - Fillable: todos los campos incluyendo `talla_id`

#### 6. **Presentation Layer** (Vistas)
- **`resources/views/despacho/show.blade.php`**
  - Tabla con filas editables
  - JavaScript para capturar datos sin validaciones
  - Carga datos guardados automáticamente
  - Interfaz visual clara

---

##  Mapeo a Tabla `despacho_parciales`

```
Por cada fila de la tabla (prenda/EPP + talla):

pedido_id              → ID del pedido de producción
tipo_item              → 'prenda' o 'epp'
item_id                → ID de prenda_pedido_talla (prendas) o pedido_epp (EPP)
talla_id               → ID de talla (NULL para EPP)
pendiente_inicial      → Valor digitado manualmente
parcial_1              → Valor digitado manualmente
pendiente_1            → Valor digitado manualmente
parcial_2              → Valor digitado manualmente
pendiente_2            → Valor digitado manualmente
parcial_3              → Valor digitado manualmente
pendiente_3            → Valor digitado manualmente
observaciones          → Texto libre (opcional)
fecha_despacho         → Timestamp del registro
usuario_id             → Usuario autenticado
created_at/updated_at  → Auditoría

Cada registro es INDEPENDIENTE:
- NO se consolidan tallas
- NO se agrupan registros
- NO se sobrescriben datos existentes
- Se puede guardar múltiples veces la misma fila (crear nuevos registros)
```

---

##  Flujo Completo

### 1️⃣ Frontend: Cargar Tabla
```javascript
GET /despacho/{pedido}

Genera tabla con:
- Prendas: una fila por talla
- EPPs: una fila por EPP (sin talla)

Cada fila tiene:
- Campos de lectura (descripción, talla, cantidad)
- 7 inputs editables (pendiente_inicial, parcial_1-3, pendiente_1-3)
- Atributos data: tipo, id, talla_id
```

### 2️⃣ Frontend: Usuario Edita
```
El usuario escribe números en los inputs:
- SIN validación en tiempo real
- SIN cálculos automáticos
- SIN restricciones matemáticas
```

### 3️⃣ Frontend: Guardar
```javascript
POST /despacho/{pedido}/guardar

Body:
{
  "fecha_hora": "2026-01-29T15:30",
  "cliente_empresa": "Nombre Receptor",
  "despachos": [
    {
      "tipo": "prenda",
      "id": 123,
      "talla_id": 456,
      "pendiente_inicial": 100,
      "parcial_1": 30,
      "pendiente_1": 70,
      "parcial_2": 40,
      "pendiente_2": 30,
      "parcial_3": 25,
      "pendiente_3": 5
    },
    {
      "tipo": "epp",
      "id": 789,
      "talla_id": null,
      "pendiente_inicial": 50,
      "parcial_1": 15,
      "pendiente_1": 35,
      ...
    }
  ]
}
```

### 4️⃣ Backend: Validación Mínima
```php
DespachoValidadorService::validarMultiplesDespachos()

Valida:
✓ Valores negativos NO permitidos
✗ NO valida coherencia matemática
✗ NO calcula pendientes automáticos
✗ NO valida contra cantidad disponible
```

### 5️⃣ Backend: Persistencia
```php
GuardarDespachoUseCase::ejecutar()
  ↓
DesparChoParcialesPersistenceService::crearYGuardarMultiples()
  ↓
DesparChoParcial::crear() (Entidad Domain)
  ↓
DesparChoParcialesRepositoryImpl::guardarMultiples()
  ↓
DesparChoParcialesModel::create() (Eloquent)
```

### 6️⃣ Backend: Respuesta
```json
{
  "success": true,
  "message": "Control de entregas guardado correctamente",
  "pedido_id": 1,
  "despachos_procesados": 2,
  "despachos_persistidos": 2
}
```

### 7️⃣ Frontend: Confirmación
```
- Alert: "✓ Despacho guardado exitosamente"
- Botón muestra: "✓ Guardado" (2 segundos)
- Inputs se limpian
- Vuelve a cargar automáticamente datos guardados
```

---

## Archivos Modificados

### Entidad Domain (ampliada)
-  `app/Domain/Pedidos/Despacho/Entities/DesparChoParcial.php`
  - Agregado: campo `$tallaId`
  - Agregado: parámetro `tallaId` en constructores
  - Agregado: getter `tallaId()`
  - Actualizado: `convertirAArray()` incluye `talla_id` y `pendiente_inicial`

### Servicio de Persistencia (ampliado)
-  `app/Domain/Pedidos/Despacho/Services/DesparChoParcialesPersistenceService.php`
  - Actualizado: `crearYGuardarMultiples()` pasa `tallaId` y `pendiente_inicial`

### Repositorio (ampliado)
-  `app/Infrastructure/Repositories/Pedidos/Despacho/DesparChoParcialesRepositoryImpl.php`
  - Actualizado: `modeloAEntidad()` incluye `tallaId`
  - Actualizado: `entidadAArray()` incluye `talla_id`

### Vistas y Rutas (sin cambios)
-  `routes/despacho.php` - Ya existe y funciona
-  `resources/views/despacho/show.blade.php` - Ya envía datos correctamente

---

## 🧪 Pruebas

### Test Unitario
Ubicación: `tests/Feature/DespachoParcialesTest.php`

Cubre:
1.  Guardar despachos de prendas con talla
2.  Guardar despachos de EPP sin talla
3.  Validar que se guardan sin cálculos automáticos
4.  Permitir datos inconsistentes (sin validación matemática)

### Ejecutar Tests
```bash
php artisan test tests/Feature/DespachoParcialesTest.php
```

---

##  Cómo Usar

### Para Despachar un Pedido

1. **Acceder a módulo de despacho**
   ```
   GET /despacho
   ```

2. **Seleccionar un pedido**
   ```
   GET /despacho/{pedido_id}
   ```

3. **Llenar los datos de despacho**
   - Fecha y hora: automática, editable
   - Receptor: nombre de quien recibe
   - Para cada fila:
     - Escribir "Pendiente Inicial" (cantidad que se va a despachar)
     - Escribir "Parcial 1" (cantidad despachada en primer envío)
     - Escribir "Pendiente 1" (cantidad que queda después del primero)
     - ... repetir para parciales 2 y 3

4. **Guardar**
   - Click en "Guardar Despacho"
   - Confirmación visual

5. **Verificar en BD**
   ```sql
   SELECT * FROM despacho_parciales 
   WHERE pedido_id = ?
   ORDER BY created_at DESC;
   ```

---

## ⚙️ Configuración

### Middleware Requerido
- `auth` - Autenticación
- `check.despacho.role` - Validar rol de usuario

### Tabla BD
```sql
CREATE TABLE despacho_parciales (
    id BIGINT UNSIGNED PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    tipo_item ENUM('prenda', 'epp'),
    item_id BIGINT UNSIGNED NOT NULL,
    talla_id BIGINT UNSIGNED NULLABLE,
    pendiente_inicial INT DEFAULT 0,
    parcial_1 INT DEFAULT 0,
    pendiente_1 INT DEFAULT 0,
    parcial_2 INT DEFAULT 0,
    pendiente_2 INT DEFAULT 0,
    parcial_3 INT DEFAULT 0,
    pendiente_3 INT DEFAULT 0,
    observaciones TEXT NULLABLE,
    fecha_despacho TIMESTAMP,
    usuario_id BIGINT UNSIGNED NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos_produccion(id),
    FOREIGN KEY (usuario_id) REFERENCES users(id),
    INDEX (pedido_id, tipo_item),
    INDEX (item_id, tipo_item)
);
```

---

##  Ejemplo de Datos Guardados

```json
{
  "id": 1,
  "pedido_id": 42,
  "tipo_item": "prenda",
  "item_id": 123,
  "talla_id": 456,
  "pendiente_inicial": 100,
  "parcial_1": 30,
  "pendiente_1": 70,
  "parcial_2": 40,
  "pendiente_2": 30,
  "parcial_3": 25,
  "pendiente_3": 5,
  "observaciones": "Cliente empresa: ABC Corp",
  "fecha_despacho": "2026-01-29 15:30:00",
  "usuario_id": 1,
  "created_at": "2026-01-29 15:30:15",
  "updated_at": null,
  "deleted_at": null
}
```

---

##  Validación de Requisitos

| Requisito | Estado | Detalles |
|-----------|--------|----------|
|  Campos NO editables (descripción, talla, cantidad) | CUMPLIDO | Solo lectura en tabla |
|  Campos editables manualmente | CUMPLIDO | 7 campos sin validación |
|  Sin validaciones matemáticas | CUMPLIDO | Solo valida negativos |
|  Sin cálculos automáticos | CUMPLIDO | Valores exactos como se digitan |
|  Sin validación de coherencia | CUMPLIDO | Permite datos inconsistentes |
|  Registro independiente por fila | CUMPLIDO | Cada fila = 1 registro |
|  NO consolida tallas | CUMPLIDO | Talla_id único por registro |
|  NO sobrescribe datos | CUMPLIDO | INSERT, no UPDATE |
|  Mapeo completo a tabla | CUMPLIDO | Todos los campos mapeados |
|  Usuario autenticado | CUMPLIDO | Auth::id() guardado |
|  Timestamp automático | CUMPLIDO | fecha_despacho + created_at |

---

## 🐛 Troubleshooting

### Problema: Los datos no se guardan
**Solución**: Verificar que el usuario tiene rol `despacho` (middleware `check.despacho.role`)

### Problema: La tabla aparece vacía
**Solución**: Asegurar que el pedido tiene prendas con tallas y/o EPP asociados

### Problema: Los datos guardados no se cargan en el formulario
**Solución**: Verificar que `data-tipo` y `data-id` coinciden en ambas direcciones

### Problema: Validación de valores negativos falla
**Solución**: Es correcto, no permitimos valores negativos. Usar 0 si no aplica.

---

## 📚 Referencias

- Domain-Driven Design (DDD) en Laravel
- Repository Pattern
- Data Transfer Objects (DTOs)
- Eloquent ORM
- JavaScript Vanilla

---

## ✨ Ventajas del Diseño

 **Sin validaciones restrictivas** - El usuario tiene libertad total
 **Persistencia simple** - Almacena exactamente lo que se ingresa
 **Escalable** - Fácil agregar más parciales (parcial_4, etc.)
 **Auditable** - Quién guardó y cuándo
 **Transaccional** - Todo o nada
 **Modulable** - Cada capa puede reusarse

---

**Última actualización**: 29 de enero de 2026
**Versión**: 1.0 - Implementación Inicial
