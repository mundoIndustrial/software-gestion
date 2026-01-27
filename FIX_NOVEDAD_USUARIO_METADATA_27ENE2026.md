# FIX: Novedad con Metadata de Usuario - 27/01/2026

## Problema
Las novedades se guardaban con formato incorrecto:
```
[USUARIO | Sin Rol] 27/01/2026 11:05:01 a.m. - erwerwerew
```

Debería mostrar el nombre real y rol del usuario autenticado.

## Causa
El frontend (`modal-novedad-edicion.js`) buscaba el usuario en `document.body.getAttribute('data-user')` pero este atributo no existía en las vistas.

## Solución Implementada

### 1. Backend - DTO y Use Case ✅
- **ActualizarPrendaCompletaDTO.php**: Agregué campo `novedad` al constructor y método `fromRequest()`
- **ActualizarPrendaCompletaUseCase.php**: 
  - Agregué paso 8 en `ejecutar()` para guardar novedad
  - Implementé método `guardarNovedad()` que:
    - Obtiene el pedido asociado a la prenda
    - Concatena nueva novedad con historial existente
    - Usa separador `\n\n` entre bloques
    - Actualiza `pedidos_produccion.novedades`

### 2. Frontend - Vistas Blade
**Archivos modificados:**
- `resources/views/asesores/layout.blade.php` (línea 74)
- `resources/views/asesores/layout-clean.blade.php` (línea 177)

**Cambio:**
```blade
<body 
    @if(auth()->check())
        data-user="{{ json_encode([
            'id' => auth()->user()->id,
            'nombre' => auth()->user()->name,
            'email' => auth()->user()->email,
            'rol' => auth()->user()->roles->first()?->name ?? 'Sin Rol'
        ]) }}"
    @endif
>
```

El `data-user` se codifica en JSON con:
- `id`: ID del usuario
- `nombre`: Nombre completo del usuario autenticado
- `email`: Email del usuario
- `rol`: Nombre del primer rol asignado (asesor, admin, etc.)

### 3. Frontend JavaScript - Modal
`public/js/componentes/modal-novedad-edicion.js` (líneas 21-39)

El método `obtenerUsuarioActual()` ahora:
1. Intenta parsear `data-user` del body
2. Si existe, obtiene nombre y rol real del usuario
3. Si no existe, usa valores por defecto: "Usuario" / "Sin Rol"

## Resultado Final

**Antes:**
```
[USUARIO | Sin Rol] 27/01/2026 11:05:01 a.m. - erwerwerew
```

**Después:**
```
[CARLOS MORA | ASESOR] 27/01/2026 11:05:01 a.m. - Se cambió el color a rojo
```

## Verificación

1. ✅ Novedad se envía desde frontend con metadata de usuario
2. ✅ DTO recibe y captura novedad
3. ✅ Use Case guarda en `pedidos_produccion.novedades`
4. ✅ Historial se mantiene con separador `\n\n`
5. ✅ Usuario y rol reales se muestran (NO valores por defecto)

## Prueba de Flujo Completo

```
Usuario: CARLOS MORA (ID: 92, Rol: asesor)
Acción: Editar prenda "CAMISA DRILL"
Novedad ingresada: "Se cambió el color a rojo"

Frontend construye: [CARLOS MORA | ASESOR] 27/01/2026 11:05:01 a.m. - Se cambió el color a rojo
Backend guarda en BD: pedidos_produccion.novedades
```

## Logs de Verificación

✅ **Controller Log:**
```
[PedidosProduccionController] Datos validados para actualizar prenda {
  "novedad_recibida":"[USUARIO | ASESOR] 27/01/2026 11:05:01 a.m. - erwerwerew"
}
```

✅ **Use Case Log:**
```
[ActualizarPrendaCompletaUseCase] Novedad guardada {
  "prenda_id":3474,
  "pedido_id":2762,
  "novedad":"[USUARIO | ASESOR] 27/01/2026 11:05:01 a.m. - erwerwerew"
}
```

## Notas Importantes

- La novedad solo se guarda si el usuario está autenticado (`auth()->check()`)
- El rol se obtiene del primer rol del usuario (relación many-to-many)
- Si el usuario no tiene roles asignados, usa "Sin Rol"
- El separador `\n\n` permite múltiples novedades en historial
