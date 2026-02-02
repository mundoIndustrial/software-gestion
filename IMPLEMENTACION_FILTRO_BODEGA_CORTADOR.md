# 📋 IMPLEMENTACIÓN: FILTRO DE PRENDAS DE BODEGA PARA ROL CORTADOR

## 🎯 Objetivo
Las prendas con `de_bodega = TRUE` **NO deben mostrarse** en el rol **CORTADOR**. El cortador solo debe ver prendas de confección (de_bodega = FALSE).

## 📝 Análisis Realizado

Se identificaron **3 puntos de acceso** donde se obtienen las prendas:

### 1. QueryHandler (CQRS)
- **Archivo**: `app/Domain/Pedidos/QueryHandlers/ObtenerPrendasPorPedidoHandler.php`
- **Propósito**: Manejo de queries CQRS para obtener prendas de un pedido
- **Usado por**: Endpoints de API que usan el bus de queries

### 2. UseCase (ObtenerPedidoUseCase)
- **Archivo**: `app/Application/Pedidos/UseCases/ObtenerPedidoUseCase.php`
- **Propósito**: Obtener datos completos de un pedido (prendas, epps, procesos, imágenes)
- **Usado por**: Operario/Cortador para visualizar detalles del pedido

### 3. AbstractObtenerUseCase (Base compartida)
- **Archivo**: `app/Application/Pedidos/UseCases/Base/AbstractObtenerUseCase.php`
- **Propósito**: Clase base que todos los UseCases de obtención heredan
- **Usado por**: ObtenerPrendasPedidoUseCase, ObtenerPedidoUseCase y otros

## 🔧 Cambios Implementados

### ✅ Cambio 1: ObtenerPrendasPorPedidoHandler.php

**Agregados:**
- Import: `use Illuminate\Support\Facades\Auth;`
- Lógica de detección: Verifica si el usuario es CORTADOR
- Filtro condicional: `where('de_bodega', false)` solo para CORTADOR
- Log informativo para auditoría

**Código clave:**
```php
$usuario = Auth::user();
$esCortador = $usuario && $usuario->hasRole('cortador');

if ($esCortador) {
    $queryBuilder->where('de_bodega', false);
}
```

---

### ✅ Cambio 2: ObtenerPedidoUseCase.php

**Agregados:**
- Import: `use Illuminate\Support\Facades\Auth;`
- Variables de usuario y rol CORTADOR
- Filtro en la carga de prendas con `function($q) use ($esCortador)`
- Condición que aplica `where('de_bodega', false)` si es CORTADOR

**Código clave:**
```php
'prendas' => function($q) use ($esCortador, $usuario) {
    $q->withTrashed();
    
    if ($esCortador) {
        $q->where('de_bodega', false);
    }
    
    $q->with([...]);
}
```

---

### ✅ Cambio 3: AbstractObtenerUseCase.php

**Agregados:**
- Documentación sobre el filtro
- Obtención del usuario autenticado
- Construcción condicional del query
- Aplicación del filtro para CORTADOR
- Log informativo para auditoría

**Código clave:**
```php
$usuario = \Illuminate\Support\Facades\Auth::user();
$esCortador = $usuario && $usuario->hasRole('cortador');

$queryBuilder = \App\Models\PrendaPedido::where('pedido_produccion_id', $pedidoId);

if ($esCortador) {
    $queryBuilder->where('de_bodega', false);
}
```

---

## 🧪 Pruebas Realizadas

### Archivo de prueba: `test-filtro-bodega-cortador.php`

**Test 1: Sin autenticación** ✅
- **Resultado**: 2 prendas (todas)
- **Esperado**: 2 prendas
- **Estado**: ✅ CORRECTO

**Test 2: Usuario con rol CORTADOR** ✅
- **Resultado**: 1 prenda (solo confección)
- **Esperado**: 1 prenda (sin de_bodega)
- **Verificación**: Prenda de bodega (ID: 2) NO aparece
- **Estado**: ✅ CORRECTO

**Test 3: Usuario con rol ADMIN** ✅
- **Resultado**: 2 prendas (todas)
- **Esperado**: 2 prendas
- **Estado**: ✅ CORRECTO

---

## 📊 Impacto en Endpoints

| Endpoint | Método | Cambio | Estado |
|----------|--------|--------|--------|
| `/api/pedidos/{id}/prendas` | GET | QueryHandler | ✅ Aplicado |
| `/operario/pedido/{numero}` | GET | ObtenerPedidoUseCase | ✅ Aplicado |
| Cualquier Use Case que herede | - | AbstractObtenerUseCase | ✅ Aplicado |

---

## 🔐 Comportamiento por Rol

| Rol | de_bodega=FALSE | de_bodega=TRUE | Resultado |
|-----|-----------------|----------------|-----------|
| Sin autenticación | ✅ Visible | ✅ Visible | Ve todo |
| **CORTADOR** | ✅ Visible | ❌ Oculto | Ve solo confección |
| COSTURERO | ✅ Visible | ✅ Visible | Ve todo |
| ASESOR | ✅ Visible | ✅ Visible | Ve todo |
| ADMIN | ✅ Visible | ✅ Visible | Ve todo |

---

## 📝 Logs Generados

Se agregaron logs informativos en cada punto para auditoría:

```
[ObtenerPrendasPorPedidoHandler] Filtrando prendas de bodega para CORTADOR
[ObtenerPedidoUseCase] Filtrando prendas de bodega para CORTADOR
[AbstractObtenerUseCase::obtenerPrendas] Filtrando prendas de bodega para CORTADOR
```

---

## ✨ Características Adicionales

1. **Escalable**: El filtro se aplica en 3 niveles diferentes, cubriendo todos los caminos de acceso
2. **Auditable**: Se registran logs cuando se aplica el filtro
3. **Seguro**: Requiere autenticación válida con rol CORTADOR
4. **Retrocompatible**: Los demás roles no son afectados
5. **Eficiente**: El filtro se aplica en la query (BD level), no en aplicación

---

## 🚀 Próximos Pasos (Opcionales)

1. Agregar esta lógica a vistas Blade si existen consultas directas
2. Considerar añadir caché con invalidación cuando se actualiza `de_bodega`
3. Agregar endpoint para consultas de BODEGA vs CONFECCIÓN

---

**Fecha de implementación**: Febrero 2026  
**Estado**: ✅ COMPLETO Y PROBADO
