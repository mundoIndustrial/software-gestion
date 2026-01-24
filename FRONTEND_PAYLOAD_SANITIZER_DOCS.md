# 🧹 PAYLOAD SANITIZER - DOCUMENTACIÓN TÉCNICA

## 📋 RESUMEN

**PayloadSanitizer** es una utilidad JavaScript profesional para limpiar datos reactivos antes de enviarlos a Laravel, eliminando propiedades internas de Vue/React que causan errores "Over 9 levels deep" y normalizando tipos de datos.

---

## 🎯 PROBLEMAS QUE RESUELVE

### ❌ **Problema 1: Propiedades reactivas de frameworks**

**Vue 3 Composition API** inyecta:
```javascript
{
  nombre: "Camisa",
  __v_isRef: true,        // ❌ Propiedad de reactividad
  __v_isReactive: true,   // ❌ Propiedad de reactividad
  _value: Proxy(...)      // ❌ Proxy interno
}
```

**Vue 2 Options API** inyecta:
```javascript
{
  nombre: "Camisa",
  __ob__: Observer { ... } // ❌ Observer reactivo
}
```

**Resultado en Laravel:**
```
"Over 9 levels deep, aborting normalization"
```

---

### ❌ **Problema 2: Tipos incorrectos**

```javascript
// Frontend envía:
{
  tiene_bolsillos: "true",          // ❌ String
  tipo_broche_boton_id: "2",        // ❌ String
  cantidad: "10"                    // ❌ String
}

// Laravel espera:
{
  tiene_bolsillos: true,            // ✅ Boolean
  tipo_broche_boton_id: 2,          // ✅ Number
  cantidad: 10                      // ✅ Number
}
```

---

### ❌ **Problema 3: Referencias circulares**

```javascript
const obj = { nombre: "Test" };
obj.self = obj;  // ❌ Referencia circular

JSON.stringify(obj);  // Error: Converting circular structure to JSON
```

---

### ❌ **Problema 4: Arrays anidados innecesarios**

```javascript
// Frontend envía:
{
  imagenes: [[[]]]  // ❌ Array con 3 niveles vacíos
}

// Laravel espera:
{
  imagenes: []      // ✅ Array simple
}
```

---

## ✅ SOLUCIÓN: PAYLOAD SANITIZER

### **Archivo:** `payload-sanitizer.js`

Ubicación:
```
public/js/modulos/crear-pedido/utils/payload-sanitizer.js
```

---

## 📚 API COMPLETA

### **1. `sanitizarVariaciones(variaciones)`**

Limpia el objeto `variaciones` de una prenda.

**Input:**
```javascript
{
  tipo_manga: "LARGA",
  obs_manga: "  observación  ",
  tiene_bolsillos: "true",        // ❌ String
  obs_bolsillos: "",              // ❌ String vacío
  tipo_broche: "boton",
  tipo_broche_boton_id: "2",      // ❌ String
  __v_isRef: true,                // ❌ Vue reactivity
  _reactive: Proxy(...)           // ❌ Proxy
}
```

**Output:**
```javascript
{
  tipo_manga: "LARGA",
  obs_manga: "observación",
  tiene_bolsillos: true,          // ✅ Boolean
  obs_bolsillos: null,            // ✅ null (eliminado vacío)
  tipo_broche: "boton",
  tipo_broche_boton_id: 2,        // ✅ Number
  obs_broche: null,
  tiene_reflectivo: false,
  obs_reflectivo: null
}
```

**Uso:**
```javascript
const limpio = PayloadSanitizer.sanitizarVariaciones(variacionesFormulario);
```

---

### **2. `sanitizarItem(item)`**

Limpia un item completo (prenda).

**Input:**
```javascript
{
  tipo: "prenda_nueva",
  nombre_prenda: "Camisa",
  variaciones: { /* ... */ },
  cantidad_talla: {
    DAMA: {
      S: "10",  // ❌ String
      M: "20"   // ❌ String
    }
  },
  __v_isReactive: true  // ❌ Vue reactivity
}
```

**Output:**
```javascript
{
  tipo: "prenda_nueva",
  nombre_prenda: "Camisa",
  variaciones: { /* limpio */ },
  cantidad_talla: {
    DAMA: {
      S: 10,  // ✅ Number
      M: 20   // ✅ Number
    }
  }
  // Sin propiedades reactivas
}
```

**Uso:**
```javascript
const itemLimpio = PayloadSanitizer.sanitizarItem(itemFormulario);
```

---

### **3. `sanitizarPedido(pedido)`**

Limpia el pedido completo con todos sus items.

**Input:**
```javascript
{
  cliente: "EMPRESA XYZ",
  items: [
    { nombre_prenda: "Camisa", variaciones: {...}, __v_isReactive: true },
    { nombre_prenda: "Pantalón", variaciones: {...} }
  ],
  __v_isReactive: true,  // ❌ Vue reactivity
  _meta: { /* ... */ }    // ❌ Metadata
}
```

**Output:**
```javascript
{
  cliente: "EMPRESA XYZ",
  items: [
    { nombre_prenda: "Camisa", variaciones: {...} },  // ✅ Limpio
    { nombre_prenda: "Pantalón", variaciones: {...} }  // ✅ Limpio
  ]
  // Sin propiedades reactivas ni metadata
}
```

**Uso:**
```javascript
const pedidoLimpio = PayloadSanitizer.sanitizarPedido(pedidoFormulario);
```

---

### **4. `validarPayload(payload)`**

Valida que el payload esté listo para Laravel.

**Returns:**
```javascript
{
  valido: true | false,
  errores: string[]
}
```

**Ejemplo:**
```javascript
const { valido, errores } = PayloadSanitizer.validarPayload(payload);

if (!valido) {
  console.error('Errores:', errores);
  // ['El cliente es requerido', 'Item 1: nombre_prenda es requerido']
}
```

---

### **5. `debug(antes, despues)`**

Compara el objeto antes y después de sanitizar (solo para desarrollo).

**Ejemplo:**
```javascript
if (process.env.NODE_ENV === 'development') {
  PayloadSanitizer.debug(pedidoFormulario, pedidoLimpio);
}
```

**Output en consola:**
```
🧪 PayloadSanitizer - Debug
  📦 ANTES (con propiedades reactivas):
    { cliente: "...", __v_isReactive: true, ... }
  
  ✅ DESPUÉS (limpio para Laravel):
    { cliente: "...", items: [...] }
  
  📊 Tamaño:
    Antes: 2340 bytes
    Después: 1850 bytes
```

---

## 🔧 FUNCIONES UTILITARIAS

### **`clonarProfundo(obj, cache)`**

Clona objetos sin referencias circulares ni propiedades reactivas.

**Características:**
- ✅ Detecta referencias circulares con `WeakMap`
- ✅ Elimina propiedades que empiezan con `__`, `_`, `$`, `@@`
- ✅ Maneja `Date`, `RegExp`, `ArrayBuffer`
- ✅ Clona arrays profundamente

---

### **`convertirBoolean(valor)`**

Convierte cualquier valor a boolean real.

**Casos soportados:**
```javascript
PayloadSanitizer.convertirBoolean("true")   // => true
PayloadSanitizer.convertirBoolean("false")  // => false
PayloadSanitizer.convertirBoolean("1")      // => true
PayloadSanitizer.convertirBoolean("0")      // => false
PayloadSanitizer.convertirBoolean("yes")    // => true
PayloadSanitizer.convertirBoolean("si")     // => true
PayloadSanitizer.convertirBoolean(1)        // => true
PayloadSanitizer.convertirBoolean(0)        // => false
PayloadSanitizer.convertirBoolean(null)     // => false
```

---

### **`convertirNumero(valor)`**

Convierte strings a números, retorna `null` si no es válido.

**Ejemplos:**
```javascript
PayloadSanitizer.convertirNumero("123")    // => 123
PayloadSanitizer.convertirNumero("45.67")  // => 45.67
PayloadSanitizer.convertirNumero("")       // => null
PayloadSanitizer.convertirNumero(null)     // => null
PayloadSanitizer.convertirNumero("abc")    // => null
```

---

### **`limpiarString(valor)`**

Limpia strings (trim) y convierte vacíos a `null`.

**Ejemplos:**
```javascript
PayloadSanitizer.limpiarString("  test  ")  // => "test"
PayloadSanitizer.limpiarString("")          // => null
PayloadSanitizer.limpiarString("   ")       // => null
PayloadSanitizer.limpiarString(null)        // => null
```

---

## 💻 EJEMPLOS DE USO

### **Ejemplo 1: Con Fetch API**

```javascript
async function crearPedido(pedidoFormulario) {
  try {
    // 1. Sanitizar
    const payload = PayloadSanitizer.sanitizarPedido(pedidoFormulario);
    
    // 2. Validar
    const { valido, errores } = PayloadSanitizer.validarPayload(payload);
    if (!valido) {
      alert(`Errores: ${errores.join(', ')}`);
      return;
    }
    
    // 3. Enviar
    const response = await fetch('/api/pedidos-editable/crear', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)  // ✅ Limpio
    });
    
    if (!response.ok) throw new Error('Error al crear pedido');
    
    const resultado = await response.json();
    console.log('✅ Pedido creado:', resultado);
    return resultado;
    
  } catch (error) {
    console.error('❌ Error:', error);
    throw error;
  }
}
```

---

### **Ejemplo 2: Con Axios**

```javascript
async function crearPedidoAxios(pedidoFormulario) {
  const payload = PayloadSanitizer.sanitizarPedido(pedidoFormulario);
  
  const { valido, errores } = PayloadSanitizer.validarPayload(payload);
  if (!valido) {
    throw new Error(`Validación: ${errores.join(', ')}`);
  }
  
  const response = await axios.post('/api/pedidos-editable/crear', payload);
  return response.data;
}
```

---

### **Ejemplo 3: Vue 3 Composition API**

```vue
<script setup>
import { reactive, ref } from 'vue';
import PayloadSanitizer from '@/utils/payload-sanitizer';

const pedido = reactive({
  cliente: '',
  items: []
});

const isSubmitting = ref(false);

async function enviar() {
  isSubmitting.value = true;
  
  try {
    // ✅ Sanitizar (elimina Proxy reactivos)
    const payload = PayloadSanitizer.sanitizarPedido(pedido);
    
    const response = await fetch('/api/pedidos-editable/crear', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    
    const resultado = await response.json();
    alert('Pedido creado');
    
  } catch (error) {
    console.error(error);
  } finally {
    isSubmitting.value = false;
  }
}
</script>
```

---

### **Ejemplo 4: React con Hooks**

```javascript
import { useState } from 'react';
import PayloadSanitizer from './payload-sanitizer';

function CrearPedidoForm() {
  const [pedido, setPedido] = useState({
    cliente: '',
    items: []
  });
  
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    
    try {
      // ✅ Sanitizar
      const payload = PayloadSanitizer.sanitizarPedido(pedido);
      
      const response = await fetch('/api/pedidos-editable/crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      
      const resultado = await response.json();
      alert('Pedido creado');
      
    } catch (error) {
      console.error(error);
    } finally {
      setIsSubmitting(false);
    }
  };
  
  return (
    <form onSubmit={handleSubmit}>
      {/* Formulario */}
      <button type="submit" disabled={isSubmitting}>
        {isSubmitting ? 'Creando...' : 'Crear Pedido'}
      </button>
    </form>
  );
}
```

---

## 🧪 TESTING

Ver archivo de ejemplos completo: [payload-sanitizer-ejemplos.js](payload-sanitizer-ejemplos.js)

```javascript
// Ejecutar tests
function testSanitizador() {
  // Test 1: Eliminar reactivos
  const conReactivos = { nombre: "Test", __v_isRef: true };
  const sinReactivos = PayloadSanitizer.clonarProfundo(conReactivos);
  console.assert(!sinReactivos.__v_isRef);
  
  // Test 2: Convertir booleanos
  console.assert(PayloadSanitizer.convertirBoolean("true") === true);
  console.assert(PayloadSanitizer.convertirBoolean("false") === false);
  
  // Test 3: Convertir números
  console.assert(PayloadSanitizer.convertirNumero("123") === 123);
  
  console.log('✅ Todos los tests pasaron');
}

testSanitizador();
```

---

## 📦 INSTALACIÓN

### **1. Incluir el script**

```html
<!-- En tu layout principal -->
<script src="/js/modulos/crear-pedido/utils/payload-sanitizer.js"></script>
```

### **2. Uso global**

```javascript
// Disponible globalmente
const payload = PayloadSanitizer.sanitizarPedido(pedido);
```

### **3. Uso como módulo ES6 (opcional)**

```javascript
import PayloadSanitizer from '@/utils/payload-sanitizer';

const payload = PayloadSanitizer.sanitizarPedido(pedido);
```

---

## ⚠️ CONSIDERACIONES IMPORTANTES

### ✅ **SIEMPRE sanitizar antes de enviar**

```javascript
// ❌ MAL
fetch('/api', { body: JSON.stringify(reactive(pedido)) });

// ✅ BIEN
const payload = PayloadSanitizer.sanitizarPedido(pedido);
fetch('/api', { body: JSON.stringify(payload) });
```

### ✅ **Validar después de sanitizar**

```javascript
const payload = PayloadSanitizer.sanitizarPedido(pedido);
const { valido, errores } = PayloadSanitizer.validarPayload(payload);

if (!valido) {
  console.error('Errores:', errores);
  return;
}
```

### ✅ **Debug en desarrollo**

```javascript
if (process.env.NODE_ENV === 'development') {
  PayloadSanitizer.debug(pedidoOriginal, payloadLimpio);
}
```

---

## 🎯 PROPIEDADES ELIMINADAS AUTOMÁTICAMENTE

El sanitizador elimina:

**Vue 3:**
- `__v_isRef`
- `__v_isReactive`
- `__v_isReadonly`
- `__v_isShallow`
- `__v_skip`
- `_rawValue`
- `_value`

**Vue 2:**
- `__ob__`
- `_isVue`

**React:**
- `__reactInternalInstance`
- `$$typeof`

**Otras:**
- Cualquier propiedad que empiece con `__`, `_`, `$`, `@@`

---

## 📚 REFERENCIAS

- **Laravel Validation:** https://laravel.com/docs/10.x/validation
- **Vue 3 Reactivity:** https://vuejs.org/guide/essentials/reactivity-fundamentals.html
- **React Hooks:** https://react.dev/reference/react
- **JSON Circular References:** https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Errors/Cyclic_object_value

---

## ✅ CHECKLIST

- [x] Crear `payload-sanitizer.js`
- [x] Documentar API completa
- [x] Crear ejemplos de uso
- [x] Agregar tests
- [ ] Incluir en layout principal (`<script src="...">`)
- [ ] Actualizar código de envío de pedidos
- [ ] Probar en navegador
- [ ] Verificar logs de Laravel (no más "Over 9 levels deep")

---

**Autor:** GitHub Copilot  
**Versión:** 1.0.0  
**Fecha:** 24 de enero de 2026  
**Licencia:** MIT
