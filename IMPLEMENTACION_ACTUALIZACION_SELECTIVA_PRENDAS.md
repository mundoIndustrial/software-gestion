# Implementación: Actualización Selectiva de Relaciones de Prenda

## 📋 Resumen Ejecutivo

Se implementó un sistema de **actualización selectiva** para las relaciones de prenda (prendas_pedido). Esto significa que cuando un usuario edita una prenda, **solo se actualizan los campos que realmente está editando**, preservando todos los demás datos sin cambios.

**Patrón implementado:**
- ✅ Si el campo NO se envía → SKIP (null check)
- ✅ Si el campo viene vacío → DELETE ALL de esa relación
- ✅ Si el campo tiene datos → DELETE + INSERT (relaciones simples)

---

## 🔧 Cambios Realizados

### 1. Refactorización de `ActualizarPrendaCompletaUseCase.php`

**Ubicación:** `app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php`

#### Métodos actualizados con patrón selectivo:

1. **`actualizarTallas()`** (líneas 77-124)
   - Null check: Si `$dto->cantidadTalla === null`, retorna sin hacer nada
   - Empty check: Si es array vacío, elimina todas las tallas
   - Else: DELETE + INSERT de tallas nuevas
   - ✅ Preserva tallas no editadas

2. **`actualizarVariantes()`** (líneas 126-150)
   - Patrón idéntico: null → skip, empty → delete all, else → delete+insert
   - ✅ Preserva variantes no editadas

3. **`actualizarColoresTelas()`** (líneas 152-176)
   - Patrón idéntico
   - ✅ Preserva colores/telas no editadas

4. **`actualizarFotosTelas()`** (líneas 178-199)
   - Patrón idéntico
   - ✅ Preserva fotos de telas no editadas

5. **`actualizarFotos()`** (líneas 201-220)
   - Patrón idéntico
   - ✅ Preserva fotos de prenda no editadas

6. **`actualizarProcesos()`** (líneas 222-267)
   - Patrón idéntico
   - Incluye método helper `agregarImagenesProceso()` para reducir complejidad cognitiva
   - ✅ Preserva procesos no editados

#### Reducción de Complejidad Cognitiva:

- **Antes:** `actualizarProcesos()` tenía complejidad 33
- **Después:** Separada en `actualizarProcesos()` (complejidad reducida) + `agregarImagenesProceso()` (helper)
- ✅ Cumple con límite máximo de 15

#### Nuevo método helper:

```php
private function agregarImagenesProceso(
    PedidosProcesosPrendaDetalle $procesoCreado,
    array $proceso,
    ActualizarPrendaCompletaDTO $dto
): void
```

---

### 2. Refactorización de `ActualizarPrendaPedidoUseCase.php`

**Ubicación:** `app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php`

#### Cambios principales:

1. **Método principal `ejecutar()`** simplificado:
   ```php
   public function ejecutar(ActualizarPrendaPedidoDTO $dto)
   {
       // ... validaciones ...
       
       $this->actualizarCamposBasicos($prenda, $dto);
       $this->actualizarTallas($prenda, $dto);
       $this->actualizarVariantes($prenda, $dto);
       $this->actualizarColoresTelas($prenda, $dto);
       $this->actualizarProcesos($prenda, $dto);
       
       $prenda->load(...);
       return $prenda;
   }
   ```

2. **Métodos privados implementados:**
   - `actualizarCamposBasicos()` - Maneja nombre, descripción, de_bodega
   - `actualizarTallas()` - Con patrón selectivo
   - `actualizarVariantes()` - Con patrón selectivo
   - `actualizarColoresTelas()` - Con patrón selectivo
   - `actualizarProcesos()` - Con patrón selectivo

#### Resultado:
- ✅ Complejidad cognitiva reducida de 44 a ~10
- ✅ Código más legible y mantenible
- ✅ Lógica selectiva implementada en todas las relaciones

---

## 📊 Flujo de Actualización Selectiva

```
Usuario edita prenda en cartera
    ↓
Envía JSON con SOLO los campos editados
    ↓
ActualizarPrendaPedidoDTO/ActualizarPrendaCompletaDTO parsea datos
    ↓
Para cada relación:
    - Si campo NO vino (null) → SKIP todo
    - Si campo vino vacío → DELETE all registros
    - Si campo tiene datos → DELETE old + INSERT new
    ↓
Base de datos actualizada de forma selectiva
    ↓
Relaciones no editadas = SIN CAMBIOS
```

---

## 🧪 Casos de Uso

### Caso 1: Editar solo tallas
```json
{
  "prenda_id": 1,
  "cantidad_talla": {
    "NIÑOS": { "2": 5, "4": 3 }
  },
  "variantes": null,
  "colores_telas": null,
  "procesos": null
}
```
**Resultado:**
- ✅ Solo tabla `prenda_pedido_tallas` es actualizada
- ✅ Variantes, procesos, etc. permanecen sin cambios

### Caso 2: Editar variantes y procesos
```json
{
  "prenda_id": 1,
  "cantidad_talla": null,
  "variantes": [{ "tipo_manga_id": 1, ... }],
  "colores_telas": null,
  "procesos": [{ "tipo_proceso_id": 2, ... }]
}
```
**Resultado:**
- ✅ Solo tablas `prenda_pedido_variantes` y `pedidos_procesos_prenda_detalles` son actualizadas
- ✅ Tallas permanecen sin cambios

### Caso 3: Limpiar una relación
```json
{
  "prenda_id": 1,
  "cantidad_talla": [],
  "variantes": null,
  "colores_telas": null,
  "procesos": null
}
```
**Resultado:**
- ✅ Todos los registros en `prenda_pedido_tallas` son eliminados
- ✅ Otras tablas permanecen sin cambios

---

## 📁 Archivos Modificados

| Archivo | Cambios | Estado |
|---------|---------|--------|
| `ActualizarPrendaCompletaUseCase.php` | 6 métodos refactorizados + patrón selectivo | ✅ |
| `ActualizarPrendaPedidoUseCase.php` | Refactorizado en 5 métodos privados + patrón selectivo | ✅ |
| `ActualizarPrendaCompletaDTO.php` | Ya expandido con 6 propiedades | ✅ |
| `ActualizarPrendaPedidoDTO.php` | Ya expandido con 4 propiedades | ✅ |
| `ObtenerFacturaUseCase.php` | Ya implementado con transformación de tallas | ✅ |

---

## ✨ Ventajas del Diseño

### 1. **No destructivo**
   - Solo actualiza lo que el usuario edita
   - Preserva datos intactos de otras relaciones

### 2. **Flexible**
   - Soporta actualizaciones parciales
   - Null = omitir, Empty = limpiar, Data = actualizar

### 3. **Mantenible**
   - Cada relación en su propio método
   - Lógica consistente en todos los métodos
   - Fácil de entender y modificar

### 4. **Escalable**
   - Si se agregan nuevas relaciones, solo hay que copiar el patrón
   - Sin cambios en la lógica central

---

## 🔍 Testing

### Test Manual Recomendado

#### Test 1: Editar solo tallas
```bash
POST /asesores/pedidos/{id}/actualizar
{
  "cantidad_talla": {"NIÑOS": {"2": 10}},
  "variantes": null,
  "colores_telas": null,
  "procesos": null
}
```
- Verificar en DB: SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id = ?
- Verificar: SELECT * FROM prenda_pedido_variantes WHERE prenda_pedido_id = ? (sin cambios)

#### Test 2: Limpiar procesos
```bash
POST /asesores/pedidos/{id}/actualizar
{
  "procesos": [],
  "variantes": null,
  "colores_telas": null,
  "cantidad_talla": null
}
```
- Verificar en DB: SELECT * FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = ? (debe estar vacío)
- Verificar: SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id = ? (sin cambios)

#### Test 3: Editar múltiples relaciones
```bash
POST /asesores/pedidos/{id}/actualizar
{
  "cantidad_talla": {"NIÑOS": {"2": 5}},
  "variantes": [{"tipo_manga_id": 1}],
  "colores_telas": null,
  "procesos": null
}
```
- Verificar: Ambas tablas (tallas y variantes) actualizadas
- Verificar: coloresTelas sin cambios

---

## 📝 Documentación de API

### Endpoint: Actualizar Prenda (Parcial)

**URL:** `POST /asesores/pedidos/{id}/actualizar`

**Body (selectivo):**
```json
{
  "nombre_prenda": "opcional",
  "descripcion": "opcional",
  "de_bodega": false,
  "cantidad_talla": {
    "GENERO": {"TALLA": cantidad}
  },
  "variantes": [
    {
      "tipo_manga_id": 1,
      "tipo_broche_boton_id": 2,
      "manga_obs": "texto",
      "tiene_bolsillos": true,
      "bolsillos_obs": "texto"
    }
  ],
  "colores_telas": [
    {
      "color_id": 1,
      "tela_id": 2
    }
  ],
  "procesos": [
    {
      "tipo_proceso_id": 1,
      "ubicaciones": ["frente", "espalda"],
      "observaciones": "texto",
      "estado": "PENDIENTE"
    }
  ]
}
```

**Regla:** Cualquier campo que NO se envía (o es null) = **NO SE MODIFICA**

---

## 🎯 Mejoras Futuras

1. **Smart Upsert (UPDATE/INSERT/DELETE selectivo)**
   - Actualmente: DELETE + INSERT
   - Futuro: Comparar registros existentes, UPDATE si existe, INSERT si nuevo, DELETE si falta
   - Beneficio: Menos queries, mejor performance en grandes listas

2. **Validación en tiempo real**
   - Pre-validar IDs de referencias (tipo_manga_id, color_id, etc.)
   - Devolver errores específicos por relación

3. **Historial de cambios**
   - Registrar qué relaciones fueron editadas
   - Para auditoría y debugging

---

## ✅ Checklist de Validación

- ✅ `ActualizarPrendaCompletaUseCase` implementado con patrón selectivo
- ✅ `ActualizarPrendaPedidoUseCase` refactorizado con patrón selectivo
- ✅ Complejidad cognitiva reducida en ambos UseCases
- ✅ 6 métodos relacionales en ActualizarPrendaCompletaUseCase
- ✅ 5 métodos relacionales en ActualizarPrendaPedidoUseCase
- ✅ Null check implementado en todos
- ✅ Empty array check implementado en todos
- ✅ Patrón consistente en todos los métodos
- ✅ No hay errors en linting (solo warnings de código legacy no tocado)
- ✅ DTOs ya expandidos con todas las propiedades necesarias
- ✅ ObtenerFacturaUseCase ya implementado con transformación

---

## 📞 Contacto para Dudas

Cualquier duda sobre:
- Patrón de actualización selectiva → Ver métodos privados
- Complejidad cognitiva → Ver separación en métodos helper
- Casos de uso → Ver sección Testing

