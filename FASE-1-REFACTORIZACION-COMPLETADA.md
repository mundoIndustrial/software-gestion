# 📋 GUÍA DE REFACTORIZACIÓN FASE 1 - COMPLETADA

**Fecha:** 6 de Enero 2026  
**Estado:** ✅ COMPLETADA  
**Impacto:** Bajo (sin cambios en funcionalidad)

---

## 🎯 Qué se hizo

### Archivos Creados (4 nuevos)

#### 1. **config-pedido-editable.js** (129 líneas)
Contiene todas las **constantes y configuración**:
- `LOGO_OPCIONES_POR_UBICACION` - opciones de ubicación para logos
- `TALLAS_ESTANDAR` - tallas disponibles
- `GENEROS_DISPONIBLES` - géneros de prendas
- `TECNICAS_DISPONIBLES` - técnicas de logo
- `CONFIG` - configuración general (límites, duraciones)
- `MENSAJES` - textos reutilizables
- `TIPOS_COTIZACION` - tipos de cotización
- `DOM_SELECTORS` - selectores de elementos

**¿Cuándo usar?**
```javascript
// Antes (hardcodeado)
if (fotos.length >= 5) { /* ... */ }

// Ahora (centralizado)
if (fotos.length >= CONFIG.MAX_FOTOS_LOGO) { /* ... */ }
```

---

#### 2. **helpers-pedido-editable.js** (378 líneas)
Funciones **reutilizables** para operaciones comunes:

**Modales (reemplaza código repetido de Swal.fire)**
```javascript
confirmarEliminacion(titulo, mensaje, callback)
mostrarExito(titulo, mensaje)
mostrarError(titulo, mensaje)
mostrarAdvertencia(titulo, mensaje)
mostrarInfo(titulo, mensaje)
```

**DOM (manipulación segura)**
```javascript
getElement(selector)
getElements(selector)
toggleVisibility(element, visible)
addClassWithTransition(element, className)
```

**Datos (conversión y parseo)**
```javascript
parseArrayData(data)          // Parsea JSON seguro
fotoToUrl(foto)               // Convierte foto a URL
generarUUID()                 // Genera ID único
```

**Validación**
```javascript
estaVacio(valor)
esEmailValido(email)
esNumero(valor)
```

**Arrays**
```javascript
sinDuplicados(array)
agruparPor(array, propiedad)
```

---

#### 3. **gestor-fotos-pedido.js** (320 líneas)
**Clases** para gestión centralizada de fotos:

```javascript
// Clase base
class GestorFotos {
  puedeAgregarFoto(cantidad)
  agregarFotos(archivos)
  eliminarFoto(index)
  obtenerFotos()
  cantidadFotos()
  espaciosDisponibles()
}

// Especializaciones
class GestorFotosLogo extends GestorFotos
class GestorFotosPrenda extends GestorFotos
class GestorFotosTela extends GestorFotos
```

**Uso:**
```javascript
// Crear instancia
const gestor = new GestorFotosLogo();

// Validar antes de agregar
if (!gestor.puedeAgregarFoto()) {
  mostrarError('Límite alcanzado', 'Máximo 5 fotos');
}

// Agregar fotos
await gestor.agregarFotos(files);

// Renderizar
gestor.renderizar('contenedor-id');
```

---

#### 4. **test-fase-1.js** (300 líneas)
**Tests** para verificar que todo cargó correctamente.

**Cómo usar:**
```javascript
// En la consola del navegador (F12)
testFase1()

// Resultado esperado:
// 🎉 ¡TODOS LOS TESTS PASARON! Fase 1 está lista para usar.
```

---

### Archivos Modificados (2 actualizados)

#### 1. **crear-pedido-editable.js**
**Cambios:**
- Simplificadas 3 funciones que usaban `Swal.fire` repetidamente:
  - `eliminarPrendaDelPedido()` ❌ 14 líneas → ✅ 8 líneas
  - `eliminarVariacionDePrenda()` ❌ 20 líneas → ✅ 12 líneas
  - `quitarTallaDelFormulario()` ❌ 28 líneas → ✅ 14 líneas

**Antes:**
```javascript
window.eliminarPrendaDelPedido = function(index) {
    Swal.fire({
        title: 'Eliminar prenda',
        text: '¿Estás seguro...',
        icon: 'warning',
        // ... 10 líneas más de config
    }).then((result) => {
        if (result.isConfirmed) {
            prendasEliminadas.add(index);
            Swal.fire({  // Segundo modal
                icon: 'success',
                // ... más líneas
            });
        }
    });
};
```

**Después:**
```javascript
window.eliminarPrendaDelPedido = function(index) {
    confirmarEliminacion(
        'Eliminar prenda',
        MENSAJES.PRENDA_ELIMINAR_CONFIRMAR,
        () => {
            prendasEliminadas.add(index);
            mostrarExito('Prenda eliminada', MENSAJES.PRENDA_ELIMINADA);
        }
    );
};
```

**Ventajas:**
- ✅ 45% menos líneas
- ✅ Lógica centralizada en helpers
- ✅ Mensajes fáciles de cambiar

---

#### 2. **crear-desde-cotizacion-editable.blade.php**
**Cambios:**
- Agregados 4 nuevos `<script>` en **orden específico**:
  1. `config-pedido-editable.js` (constantes)
  2. `helpers-pedido-editable.js` (funciones de utilidad)
  3. `gestor-fotos-pedido.js` (clases de fotos)
  4. `crear-pedido-editable.js` (script principal)
  5. `test-fase-1.js` (tests opcionales)

**⚠️ IMPORTANTE:** El orden NO puede cambiar o habrá errores de referencia.

---

## 📊 Impacto de Cambios

### Líneas de Código
| Archivo | Antes | Después | Cambio |
|---------|-------|---------|--------|
| crear-pedido-editable.js | 4,838 | 4,750 | -88 (-1.8%) |
| config-pedido-editable.js | — | 129 | **+129** |
| helpers-pedido-editable.js | — | 378 | **+378** |
| gestor-fotos-pedido.js | — | 320 | **+320** |
| test-fase-1.js | — | 300 | **+300** |
| **TOTAL** | **4,838** | **6,277** | **+1,439 (+29%)** |

### Código Duplicado Reducido
- ❌ Antes: ~50 líneas de `Swal.fire` repetidas
- ✅ Después: 5 funciones helper reutilizables

### Reutilización
- ✅ 378 líneas de helpers reutilizables
- ✅ 3 funciones simplificadas
- ✅ 13 constantes centralizadas

---

## ✅ Cómo Verificar que Funciona

### Paso 1: Abre la página de crear pedido
```
http://tu-url/asesores/pedidos/crear-desde-cotizacion-editable
```

### Paso 2: Abre la consola (F12)
```
Presiona F12 → Pestaña "Console"
```

### Paso 3: Ejecuta el test
```javascript
testFase1()
```

### Resultado Esperado:
```
🧪 Iniciando test Fase 1...

✅ TEST 1 PASADO: Constantes de configuración cargadas correctamente
✅ TEST 2 PASADO: Tallas estándar cargadas correctamente
✅ TEST 3 PASADO: Configuración general cargada correctamente
... (más tests)

=====================================================
📊 RESULTADO: 13/13 tests pasados
=====================================================

🎉 ¡TODOS LOS TESTS PASARON! Fase 1 está lista para usar.
```

---

## 🔧 Cómo Usar los Nuevos Archivos

### Usar Constantes

```javascript
// Opciones de logo por ubicación
const opciones = LOGO_OPCIONES_POR_UBICACION['CAMISA'];
// Resultado: ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO']

// Límites de configuración
console.log(CONFIG.MAX_FOTOS_LOGO); // 5
console.log(CONFIG.MAX_FOTOS_PRENDA); // 10

// Mensajes
alert(MENSAJES.PRENDA_ELIMINADA);
// Resultado: "La prenda ha sido eliminada del pedido"
```

### Usar Helpers

```javascript
// Confirmar eliminación
confirmarEliminacion(
    'Eliminar',
    '¿Estás seguro?',
    () => {
        // Código si confirma
        console.log('Eliminado');
    }
);

// Mostrar notificaciones
mostrarExito('Éxito', 'Operación completada');
mostrarError('Error', 'Algo salió mal');

// Manipular DOM de forma segura
const elemento = getElement('mi-id');
if (elemento) {
    toggleVisibility(elemento, true); // Mostrar
}

// Validar datos
if (estaVacio(valor)) {
    mostrarAdvertencia('Campo vacío', 'Por favor rellena este campo');
}

// Parsear datos JSON
const ubicaciones = parseArrayData(datosJSON);
```

### Usar Gestor de Fotos

```javascript
// Crear instancia
const gestor = new GestorFotosLogo();

// Verificar límite
const validacion = gestor.puedeAgregarFoto(5);
if (!validacion.permitido) {
    mostrarError('Límite', validacion.mensaje);
    return;
}

// Agregar fotos
try {
    const cantidad = await gestor.agregarFotos(files);
    gestor.renderizar('galeria-fotos-logo');
    mostrarExito('Éxito', `${cantidad} fotos agregadas`);
} catch (error) {
    mostrarError('Error', error.message);
}
```

---

## 🚀 Próximos Pasos (FASE 2)

### Paso 1: Crear `gestor-cotizacion.js`
Extraer toda la lógica de búsqueda y selección:
- `mostrarOpciones()`
- `seleccionarCotizacion()`
- `cargarPrendasDesdeCotizacion()`

### Paso 2: Crear `gestor-prendas.js`
Extraer lógica de prendas:
- `renderizarPrendasEditables()`
- `agregarFilaTela()`
- `eliminarFilaTela()`
- Manejo de variaciones

### Paso 3: Crear `gestor-logo.js`
Encapsular toda lógica de logo:
- `renderizarCamposLogo()`
- Modal de ubicaciones
- Guardar secciones

---

## 📝 Checklist de Verificación

- [ ] Página de crear pedido carga sin errores
- [ ] Consola (F12) no muestra errores rojos
- [ ] Test `testFase1()` pasa los 13 tests
- [ ] Botón "Eliminar prenda" funciona igual que antes
- [ ] Modales de confirmación se muestran correctamente
- [ ] Puedes agregar y eliminar tallas
- [ ] Fotos se cargan sin problemas
- [ ] Buscar cotización sigue funcionando

---

## 💡 Tips para el Futuro

### Si necesitas agregar una constante nueva:
1. Edita `config-pedido-editable.js`
2. Agrega en `CONFIG`, `MENSAJES` o la sección apropiada
3. Guarda y recarga la página
4. ¡Listo! Ya está disponible globalmente

### Si necesitas agregar un helper nuevo:
1. Edita `helpers-pedido-editable.js`
2. Agrega la función al final de la clase o como función
3. Dentro de crear-pedido-editable.js, úsala normalmente
4. El test no será necesario

### Si necesitas extender el gestor de fotos:
1. Edita `gestor-fotos-pedido.js`
2. Crea una nueva clase que extienda `GestorFotos`
3. Override los métodos necesarios
4. Usa como instancia global

---

## ⚠️ Advertencias Importantes

### 1. No cambies el orden de scripts en blade.php
```html
<!-- ❌ INCORRECTO - fallará
<script src="crear-pedido-editable.js"></script>
<script src="helpers-pedido-editable.js"></script>

<!-- ✅ CORRECTO
<script src="config-pedido-editable.js"></script>
<script src="helpers-pedido-editable.js"></script>
<script src="gestor-fotos-pedido.js"></script>
<script src="crear-pedido-editable.js"></script>
```

### 2. Las funciones siguen siendo globales
```javascript
// Toda función window.* sigue siendo accesible
window.eliminarPrendaDelPedido(0);
window.agregarPrendaSinCotizacion();
// Esto NO cambió, solo se simplificó internamente
```

### 3. SweetAlert2 debe estar antes
```html
<!-- SweetAlert2 debe ir ANTES de nuestros scripts
<script src="sweetalert2@11"></script>
<script src="config-pedido-editable.js"></script>
```

---

## 📞 Soporte

Si algo no funciona:

1. **Abre la consola** (F12)
2. **Busca errores rojos** - cópia el mensaje
3. **Ejecuta `testFase1()`** - mira qué falla
4. **Verifica el orden de scripts** en blade.php
5. **Limpia cache del navegador** (Ctrl+Shift+Del)

---

## 📚 Referencia Rápida

| Necesito... | Usar... |
|-------------|---------|
| Constantes/Config | `CONFIG`, `MENSAJES`, `TIPOS_COTIZACION` |
| Modal de confirmación | `confirmarEliminacion()` |
| Notificaciones | `mostrarExito()`, `mostrarError()` |
| Obtener elemento DOM | `getElement()` |
| Validar campo vacío | `estaVacio()` |
| Parsear JSON | `parseArrayData()` |
| Generar ID único | `generarUUID()` |
| Gestionar fotos | `GestorFotosLogo`, `GestorFotosPrenda` |
| Pruebas | `testFase1()` en consola |

---

**¡FASE 1 COMPLETADA EXITOSAMENTE! 🎉**

Próximo paso cuando estés listo: **FASE 2** (separar cotizaciones, prendas y logo en módulos)
