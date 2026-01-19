# 📝 IMPLEMENTACIÓN: EDICIÓN DE PEDIDOS

**Fecha:** 19 de Enero de 2026  
**Objetivo:** Permitir editar pedidos existentes usando la interfaz completa de creación

## 🎯 Cambios Realizados

### 1. Backend - Controlador

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php`

#### Cambios:
- ✅ Importado modelo `PedidoProduccion`
- ✅ Actualizado método `edit($pedido)`:
  - Ahora obtiene el modelo de pedido
  - Retorna vista `editar-pedido.blade.php` en lugar de `edit.blade.php`
  - Pasa tanto el modelo como los datos de edición

```php
public function edit($pedido)
{
    try {
        $pedidoModel = PedidoProduccion::findOrFail($pedido);
        $datos = $this->obtenerPedidoDetalleService->obtenerParaEdicion($pedido);
        
        return view('asesores.pedidos.editar-pedido', [
            'pedido' => $pedidoModel,
            'pedidoData' => $datos,
        ]);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }
}
```

### 2. Frontend - Función del Botón Editar

**Archivo:** `resources/views/asesores/pedidos/index.blade.php`

#### Cambios:
- ✅ Actualizada función `editarPedido()` (línea 1808)
- ✅ Cambió ruta de `/asesores/pedidos-produccion/{id}/edit` a `/asesores/pedidos/{id}/edit`

```javascript
function editarPedido(pedidoId) {
    window.location.href = `/asesores/pedidos/${pedidoId}/edit`;
}
```

### 3. Nueva Vista: Editar Pedido

**Archivo:** `resources/views/asesores/pedidos/editar-pedido.blade.php` (NUEVO)

#### Características:
- ✅ Reutiliza la misma interfaz que crear pedido
- ✅ Carga la vista `crear-pedido-desde-cotizacion` en modo edición
- ✅ Pasa datos del pedido a JavaScript
- ✅ Carga todos los scripts necesarios

```php
@php
    $tipo = 'cotizacion';
    $esModoEdicion = true;
    $pedidoEdicion = $pedido ?? null;
@endphp

<script>
    window.modoEdicion = true;
    window.pedidoEdicionId = {{ $pedido->id }};
    window.pedidoEdicionData = @json($pedidoData);
</script>
```

### 4. Script de Carga de Datos

**Archivo:** `public/js/modulos/crear-pedido/edicion/cargar-datos-edicion.js` (NUEVO)

#### Responsabilidades:
- ✅ Espera a que todos los módulos se carguen
- ✅ Carga información general del pedido (cliente, forma de pago, etc.)
- ✅ Renderiza las prendas con sus detalles
- ✅ Crea tarjetas de prenda editables con:
  - Nombre y descripción
  - Tela y color
  - Variantes (talla y cantidad)
  - Procesos asociados
  - Botones para editar o eliminar

#### Funciones principales:
- `cargarDatosEdicion()` - Orquesta la carga
- `cargarInformacionGeneral(datos)` - Rellena campos generales
- `cargarPrendas(prendas)` - Carga las prendas
- `crearTarjetaPrenda(prenda, index)` - Crea UI para cada prenda
- `esperarModulosYCargar()` - Espera dependencias

## 🛣️ Rutas

### Ruta ya existente (verificada):

```
GET /asesores/pedidos/{pedido}/edit
    => AsesoresController@edit
    => Nombre: pedidos.edit
```

## 📊 Flujo de Edición

```
1. Usuario hace clic en "Editar" en la tabla de pedidos
   ↓
2. Función editarPedido() redirige a /asesores/pedidos/{id}/edit
   ↓
3. Controlador edit() obtiene datos del pedido
   ↓
4. Vista editar-pedido.blade.php se renderiza
   ↓
5. JavaScript carga los datos en los formularios
   ↓
6. Usuario ve la interfaz completa de creación PRE-LLENADA
   ↓
7. Usuario puede editar las prendas, procesos, fotos, etc.
   ↓
8. Al hacer submit, método update() procesa los cambios
```

## 🔄 Método Update (Existente)

El método `update()` ya está implementado en el controlador:
- Valida los datos
- Delega a `ActualizarPedidoService`
- Retorna respuesta JSON

```php
public function update(Request $request, $pedido)
{
    $validated = $request->validate([...]);
    try {
        $pedidoActualizado = $this->actualizarPedidoService->actualizar($pedido, $validated);
        return response()->json(['success' => true, ...]);
    } catch (\Exception $e) {
        return response()->json(['error' => ...], 500);
    }
}
```

## ✅ Verificaciones Realizadas

- ✅ Sintaxis PHP correcta
- ✅ Rutas registradas correctamente
- ✅ Imports correctos en controlador
- ✅ Vistas existen o están creadas
- ✅ Scripts de JavaScript creados

## 🚀 Próximos Pasos (Opcional)

Para integración más completa, se podrían:

1. **Mejorar la carga de datos:**
   - Cargar fotos existentes en la galería
   - Pre-llenar procesos
   - Sincronizar con el gestor de prendas existente

2. **Validación mejorada:**
   - Validar cambios antes de enviar
   - Mostrar advertencias de cambios

3. **UX mejorada:**
   - Indicador visual de "modo edición"
   - Historial de cambios
   - Confirmación antes de eliminar prendas

## 📝 Notas

- El sistema mantiene la misma interfaz para crear y editar
- Los datos se cargan dinámicamente en JavaScript
- El formulario Submit reutiliza el endpoint de actualización existente
- Todos los scripts se cargan en orden correcto

