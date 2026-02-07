# 📋 Plan de Acción - Refactorización Frontend/Backend

## Problemas Identificados en Prioridad

### 🔴 CRÍTICO (Bloquea escalabilidad)

#### 1. Gestión de Orden de Items (REPLANTEAR)
- [ ] Backend debe retornar items ya ordenados
- [ ] Eliminar `this.prendas`, `this.epps`, `this.ordenItems`
- [ ] Frontend solo almacena: `this.items = []`
- [ ] Archivo afectado: `gestion-items-pedido.js` líneas 93-157

**Impacto:**
- Actualizar refrescar página pierde items
- Difícil de sincronizar con otros clientes

**Tiempo:** 4-6 horas de refactorización

---

#### 2. Reconstrucción de Índices en Frontend (MOVER AL BACKEND)
- [ ] Backend maneja eliminación y reordenamiento
- [ ] Frontend solo llama: `await apiService.eliminarItem(itemId)`
- [ ] Simplificar método `eliminarItem()` (líneas 258-325)
- [ ] Eliminar construcción de índices manual

**Impacto:**
- Si hay error en índices, afecta todo el sistema
- Código frágil difícil de mantener

**Tiempo:** 3-4 horas

---

#### 3. Acoplamiento a gestorPrendaSinCotizacion (REFACTORIZAR)
- [ ] Implementar EventBus centralizado
- [ ] Remover llamadas via `window.gestorPrendaSinCotizacion`
- [ ] Todos escuchan eventos en lugar de ser acoplados
- [ ] Línea 318: `window.gestorPrendaSinCotizacion?.eliminar()`

**Impacto:**
- Múltiples fuentes de verdad
- Testing imposible
- Difícil agregar nuevos gestores

**Tiempo:** 5-6 horas (incluye crear EventBus)

---

### 🟠 ALTO (Seguridad)

#### 4. Validación de Reglas de Negocio (DUPLICADA)
- [ ] Remover validaciones de frontend (excepto UI básica)
- [ ] Backend es responsable de validar tallas, procesos, variantes
- [ ] Frontend solo valida: campos requeridos, longitud máxima
- [ ] Líneas 476-482: validación de tallas

**Validaciones que DEBEN estar en backend:**
```
- Debe tener al menos una talla
- Procesos deben coincidir con tela seleccionada
- Variantes deben tener stock disponible
- Costura debe tener materia prima
- Prendas no pueden tener procesos duplicados
```

**Impacto:**
- Usuario puede bypassear reglas (F12 → console)
- Inconsistencia entre cliente y servidor

**Tiempo:** 2-3 horas

---

#### 5. Variable `esEdicion` sin definir (BUG)
- [ ] Línea 490: definir `esEdicion`
- [ ] Debería ser: `const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined`
- [ ] O mejor aún: usar solo `this.prendaEditIndex`

**Impacto:**
- Error en console
- Lógica de edición puede no funcionar

**Tiempo:** 10 minutos (rápido)

---

### 🟡 MEDIO (Mantenibilidad)

#### 6. Construcción de Datos Compleja en Frontend
- [ ] Simplificar `construirPrendaDesdeFormulario()`
- [ ] Frontend solo recolecta datos del formulario
- [ ] Backend procesa, valida y transforma estructura
- [ ] Frontend no debe conocer estructura interna de datos

**Archivos:**
- `prendaFormCollector.js` - Simplificar

**Tiempo:** 3-4 horas

---

#### 7. ItemFormCollector - Responsabilidades Confusas
- [ ] Separar: recolectar datos ≠ validar negocio
- [ ] `recolectarDatosPedido()` probablemente hace más de lo que debe
- [ ] Debería solo hacer: `getFormValues()` con estructura simple

**Archivos:**
- Archivo: Buscar `ItemFormCollector` en codebase

**Tiempo:** 2-3 horas

---

## Backend - Cambios Requeridos

### Controladores que necesitan actualizar respuestas:

#### 1. PrendaController (o similar)
```php
// ANTES: podría retornar solo ID
return response()->json(['id' => $prenda->id]);

// DESPUÉS: retornar items completos y ordenados
return response()->json([
    'success' => true,
    'items' => $this->obtenerItemsActualizados(),  // Ya ordenados
    'message' => 'Prenda agregada'
]);
```

#### 2. ItemController.destroy()
```php
// DEBE HACER:
- Validar que el item pertenece al pedido/usuario
- Eliminar cascada de procesos/variantes
- Recuperar y retornar items actualizados
- En el order correcto

// RESPUESTA:
{
  "success": true,
  "items": [/* lista actualizada */],
  "relatedDeleted": { "procesos": 3 }
}
```

#### 3. Validaciones en FormRequest
```php
// app/Http/Requests/CreatePrendaRequest.php
public function rules()
{
    return [
        'nombre_prenda' => 'required|max:255',
        'tallas' => 'required|array|min:1',  // Backend valida
        'procesos' => 'required_if:tipo,prenda',
        // Backend es responsable de reglas complejas
    ];
}
```

---

## Frontend - Archivos a Refactorizar

### Priority 1 (Este archivo)
- [ ] `gestion-items-pedido.js` 
  - Eliminar: `obtenerItemsOrdenados`, `agregarPrendaAlOrden`, `agregarEPPAlOrden`
  - Simplificar: `eliminarItem`
  - Fijar: variable `esEdicion` sin definir

### Priority 2
- [ ] `ItemAPIService.js` - Actualizar llamadas a API
- [ ] `ItemFormCollector.js` - Remover lógica de negocio

### Priority 3
- [ ] Crear: `EventBus.js` (si no existe)
- [ ] Actualizar: todos los gestores para usar EventBus

---

## Testing - Casos a Cubrir

### Backend (PHP Tests)
```php
// Tests que deben pasar DESPUÉS de refactorizar

public function test_eliminar_prenda_actualiza_lista_ordenada()
{
    $prenda = Prenda::factory()->create();
    // Crear otros items
    
    $response = $this->delete("/api/items/{$prenda->id}");
    
    $this->assertTrue($response['success']);
    // Items deben estar en el orden correcto
    $this->assertCount(2, $response['items']);
    $this->assertEquals(1, $response['items'][0]['orden']);
    $this->assertEquals(2, $response['items'][1]['orden']);
}

public function test_crear_prenda_sin_tallas_falla()
{
    $data = ['nombre' => 'Test', 'tallas' => []];
    $response = $this->post('/api/prendas', $data);
    
    $this->assertFalse($response['success']);
    $this->assertContains('tallas', $response['validationErrors']);
}
```

### Frontend (JavaScript Tests)
```javascript
// Tests que deben pasar DESPUÉS

describe('GestionItemsUI', () => {
    it('should display items returned by backend', async () => {
        const response = {
            success: true,
            items: [{id: 1, nombre: 'Prenda1'}, {id: 2, nombre: 'Prenda2'}]
        };
        
        gestionItemsUI.items = response.items;
        await gestionItemsUI.renderer.actualizar(gestionItemsUI.items);
        
        expect(document.querySelectorAll('.item').length).toBe(2);
    });
    
    it('should not validate tallas - backend responsibility', () => {
        // Este test debe FALLAR en el código actual
        // y PASAR después de refactorizar
    });
});
```

---

## Checklist de Migración

### Fase 1: Backend (1-2 días)
- [ ] Actualizar PrendaController para retornar items ordenados
- [ ] Agregar validaciones de reglas de negocio
- [ ] Crear/actualizar FormRequests
- [ ] Actualizar método eliminarItem() para manejar cascada
- [ ] Documentar estructura de respuestas API

### Fase 2: API (Frontend consumidor del API)
- [ ] Actualizar ItemAPIService con nuevas respuestas
- [ ] Agregar manejo de errores de validación
- [ ] Crear estructura simple de items en frontend

### Fase 3: Componentes Frontend
- [ ] Simplificar GestionItemsUI
- [ ] Remover gestión de arrays complejos
- [ ] Implementar EventBus si no existe
- [ ] Actualizar otros gestores para usar EventBus

### Fase 4: Testing
- [ ] Tests de backend con nueva validación
- [ ] Tests de frontend con nueva estructura simple
- [ ] Tests de integración

### Fase 5: Limpieza
- [ ] Remover código deprecado
- [ ] Documentar cambios en arquitectura
- [ ] Capacitar equipo

---

## Métricas de Éxito

- ✅ Frontend .js < 300 líneas (ahora ~900)
- ✅ Cada método tiene responsabilidad única
- ✅ No hay acceso a `window.` gestores externos
- ✅ Backend valida 100% de reglas de negocio
- ✅ Tests pasen completamente
- ✅ Actualizar página no pierde estado (si debería persistir)

---

## Documentación que falta

Crear después de refactorizar:
- [ ] `API_ITEMS_CONTRACT.md` - Estructura exacta de respuestas
- [ ] `FRONTEND_COMPONENT_RESPONSIBILITIES.md` - Qué hace cada componente
- [ ] `VALIDATION_RULES.md` - Donde debe validarse cada regla
- [ ] `EVENT_BUS_SPEC.md` - Eventos que emite el sistema
