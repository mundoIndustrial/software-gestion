# 🐛 BUG: de_bodega no se actualiza correctamente

## Problema identificado

Cuando se edita una prenda y se cambia entre "bodega" y "confección", el campo `de_bodega` en la tabla `prendas_pedido` no se actualiza correctamente.

### Causa raíz

1. **Backend devuelve solo `de_bodega` (boolean), NO `origen`**
   - Prenda en DB: `de_bodega = 0` (confección) o `de_bodega = 1` (bodega)
   - Servidor devuelve: `{ de_bodega: false, ... }`
   - Servidor NO devuelve: `{ origen: "confeccion", ... }`

2. **Frontend intenta usar `prenda.origen`**
   - En `prenda-editor.js` línea 117: `let origen = prenda.origen || 'bodega'`
   - `prenda.origen` es `undefined` porque no viene del servidor
   - Siempre usa default `'bodega'`

3. **El SELECT no se carga correctamente**
   - Si `origen = 'bodega'` siempre, el SELECT siempre muestra "Bodega"
   - Cambiar el valor en el SELECT sí se lee correctamente
   - PERO si el usuario no cambia el SELECT, se envía el valor incorrecto que se cargó

### Flujo actual (con BUG)

```
Prenda BD: { de_bodega: 0 }  ← confección
    ↓
Servidor devuelve: { de_bodega: false, origen: undefined }
    ↓
Frontend carga: let origen = undefined || 'bodega' = 'bodega'  ← BUG!
    ↓
SELECT se muestra "Bodega" aunque la prenda es de confección
    ↓
Usuario NO cambia nada
    ↓
Envía: { origen: 'bodega', de_bodega: 1 }  ← INCORRECTO
```

## Solución

### Cambio 1: Convertir `de_bodega` a `origen` en el frontend

Archivo: `prenda-editor.js` línea 117-143

Cambiar:
```javascript
let origen = prenda.origen || 'bodega';
```

Por:
```javascript
// Convertir de_bodega (boolean/integer) a origen (string)
// de_bodega = 1 → 'bodega'
// de_bodega = 0 → 'confeccion'
let origen = prenda.origen;
if (!origen) {
    // Si no viene origen del servidor, convertir de_bodega
    if (prenda.de_bodega === true || prenda.de_bodega === 1 || prenda.de_bodega === '1') {
        origen = 'bodega';
    } else if (prenda.de_bodega === false || prenda.de_bodega === 0 || prenda.de_bodega === '0') {
        origen = 'confeccion';
    } else {
        origen = 'bodega';  // default
    }
}
```

### Cambio 2: Agregar atributo `origen` en respuesta del servidor

Archivo: `ActualizarPrendaCompletaUseCase.php` línea 565-580 (en el método `obtenerRespuestaFormato()`)

Cambiar para incluir `origen`:
```php
'de_bodega' => (bool) $prenda->de_bodega,
'origen' => $prenda->de_bodega ? 'bodega' : 'confeccion',  // AGREGAR ESTA LÍNEA
```

### Cambio 3: Validar que `de_bodega` se envía correctamente

En `modal-novedad-edicion.js` línea 151-156, agregar logging:
```javascript
console.log('[modal-novedad-edicion] DEBUG de_bodega:', {
    origenSelect: origenSelect?.value,
    origenActual: origenActual,
    deBodegaValue: deBodegaValue,
    tipoDeBodega: typeof deBodegaValue,
    esNulo: deBodegaValue === null
});
```

## Checklist de implementación

- [ ] Editar `prenda-editor.js` para convertir correctamente `de_bodega` a `origen`
- [ ] Editar `ActualizarPrendaCompletaUseCase.php` para incluir `origen` en respuesta
- [ ] Agregar logging en `modal-novedad-edicion.js` para debug
- [ ] Probar editando prenda de bodega → confección
- [ ] Probar editando prenda de confección → bodega
- [ ] Verificar que `de_bodega` se guarda correctamente en DB
