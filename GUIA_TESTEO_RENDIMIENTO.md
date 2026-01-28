# 🧪 GUÍA DE TESTEO - ANTES vs DESPUÉS

## ⚡ Testear Rendimiento en Navegador

### Paso 1️⃣: Preparación
1. Abrir navegador (Chrome o Firefox)
2. Presionar `F12` para abrir DevTools
3. Ir a pestaña **Network**
4. Asegurarse de que está grabar tráfico (punto rojo debe estar presionado)

---

### Paso 2️⃣: Medir Tiempo de Carga

1. Navegar a: `https://mundoindustrial.local/asesores/pedidos`
2. Esperar a que cargue completamente
3. En DevTools → Network:
   - Buscar la petición de la URL `/asesores/pedidos`
   - Ver columna **Time** (tiempo total)
   - O al pie de la ventana: **Total: XX.XXs**

**Esperado ANTES:** ~17,000ms (17 segundos)  
**Esperado DESPUÉS:** ~3,000ms (3 segundos)

---

### Paso 3️⃣: Verificar en Console

Abrir **Console** (F12 → Console) y ejecutar:

```javascript
// Ver cuántas requests se hicieron
console.log('Total requests:', performance.getEntriesByType('resource').length);

// Ver tiempos de cada recurso
performance.getEntriesByType('resource').forEach(r => {
    console.log(`${r.name}: ${(r.duration).toFixed(2)}ms`);
});
```

**Esperado:** Solo ~3-4 requests importantes (pedidos, prendas, procesos)

---

### Paso 4️⃣: Testear Función Optimizada (editarPedido)

1. En la tabla de pedidos, hacer clic en botón "Editar" de un pedido
2. Abrir **Console** del navegador
3. Buscar mensaje: `✅ Datos extraídos de fila` o `⚠️ Datos incompletos`
4. Medir tiempo hasta que aparece el modal

**Esperado ANTES:** ~2-3 segundos (hace fetch)  
**Esperado DESPUÉS:** <100 milisegundos (extrae de data attributes)

---

## 📊 Comparativa Visual

### ANTES (Sin optimizaciones)
```
Network Tab:
┌─────────────────────────────┐
│ Method │ Status │ Type │ Time│
├─────────────────────────────┤
│ GET    │ 200    │ html │ 17s │ ← LENTO
│ GET    │ 200    │ css  │ 0.5s│
│ GET    │ 200    │ js   │ 0.8s│
│ GET    │ 200    │ js   │ 0.6s│
└─────────────────────────────┘

Console:
Total requests: 120+
Query time: 12s
Render time: 3s
```

### DESPUÉS (Con optimizaciones)
```
Network Tab:
┌─────────────────────────────┐
│ Method │ Status │ Type │ Time│
├─────────────────────────────┤
│ GET    │ 200    │ html │ 3s  │ ← RÁPIDO ⚡
│ GET    │ 200    │ css  │ 0.5s│
│ GET    │ 200    │ js   │ 0.8s│
│ GET    │ 200    │ js   │ 0.6s│
└─────────────────────────────┘

Console:
Total requests: 3-4
Query time: 0.8s
Render time: 1.2s
```

---

## 🎯 Testeos Específicos

### Test 1: Verificar Select Específico

En Console:
```javascript
// Ejecutar una consulta y verificar número de queries
// (Requiere tinker, pero lo podemos ver en Network)

// En Network, filtrar por XHR (AJAX requests)
// Deberían ver solo peticiones a /api/pedidos
// Y LUEGO mucho menos tráfico
```

**Resultado esperado:** Menos de 4 queries principales

---

### Test 2: Verificar Cache de Estados

1. Ir a `/asesores/pedidos`
2. Notar el tiempo de carga: ~3s
3. **Actualizar página** (F5)
4. Notar el tiempo: **Debería ser igual o más rápido**

**Esperado:** Caché está funcionando

---

### Test 3: Verificar Data Attributes

En Console:
```javascript
// Obtener primera fila de tabla
const fila = document.querySelector('[data-pedido-row]');

// Ver data attributes
console.log(fila.dataset);

// Debería mostrar:
// {
//   pedidoId: "123",
//   numeroPedido: "#2760",
//   cliente: "Cliente X",
//   estado: "En Ejecución",
//   formaPago: "Efectivo",
//   asesor: "Juan Pérez"
// }
```

**Esperado:** Todos los atributos presentes

---

### Test 4: Verificar Función Editada

En Console mientras se abre modal de editar:
```javascript
// Ver logs de la función optimizada
console.log('%c[editarPedido] ✅ Datos extraídos de fila:', 'color: green', {
    id: '123',
    numero: '#2760',
    cliente: 'Cliente X'
});

// Si ves este mensaje = extrayendo de data-*
// Si NO lo ves = está haciendo fetch (fallback)
```

**Esperado:** Ver mensaje de "Datos extraídos de fila"

---

## 📱 Testear en Diferentes Dispositivos

### Desktop (Chrome DevTools)
1. F12 → Network
2. Medir tiempo total
3. Filtrar por "XHR" para ver AJAX

### Mobile (Responsive)
1. F12 → Click toggle device toolbar
2. Seleccionar dispositivo (iPhone 12)
3. Medir tiempo en mobile
4. Debería estar más rápido gracias a optimizaciones

---

## 🔍 Verificar Datos en Developer Tools

### Ver estructura de prendas/procesos cargados

En Console:
```javascript
// Ver una fila con todos los datos
const fila = document.querySelector('[data-pedido-row]');

// Parsear datos de prendas
const prendas = JSON.parse(fila.dataset.prendas || '[]');
console.log('Prendas cargadas:', prendas.length);

// Verificar que NO está cargando procesos completos
prendas.forEach(p => {
    console.log(`${p.nombre_prenda}: ${p.procesos?.length || 0} procesos`);
});

// Esperado: máximo 3 procesos por prenda (limit 3)
```

---

## ⏱️ Benchmark Completo

Script para medir todo:

```javascript
// Ejecutar en Console
const start = performance.now();

// Esperar a que cargue
setTimeout(() => {
    const end = performance.now();
    const totalTime = (end - start) / 1000;
    
    console.log('=== BENCHMARK ===');
    console.log(`Tiempo total: ${totalTime.toFixed(2)}s`);
    console.log(`Queries: ${performance.getEntriesByType('resource').length}`);
    console.log(`Esperado: < 3s`);
    console.log(`Estado: ${totalTime < 3 ? '✅ OPTIMIZADO' : '❌ LENTO'}`);
}, 1000);
```

---

## 📋 Checklist de Testeo

- [ ] Tiempo carga < 3 segundos
- [ ] Console muestra "Datos extraídos de fila" al editar
- [ ] Menos de 4 queries principales
- [ ] Data attributes presentes en filas
- [ ] Función editarPedido() abre en <100ms
- [ ] Cache funciona (página más rápida en reload)
- [ ] No hay errores en console (F12)
- [ ] Tabla muestra datos correctamente
- [ ] Paginación funciona
- [ ] Filtros funcionan

---

## 🎓 Esperados Resultados

| Métrica | Valor |
|---------|-------|
| Tiempo carga inicial | < 3s ⚡ |
| Tiempo edición | < 100ms ⚡ |
| Queries SQL | 3-4 |
| Errores console | 0 |
| Performance score | > 80 |

---

## 📸 Capturas para Documentar

1. **Antes**
   - Screenshot de Network con tiempo ~17s
   - Console mostrando 120+ queries

2. **Después**
   - Screenshot de Network con tiempo ~3s
   - Console mostrando 3-4 queries
   - Message "Datos extraídos de fila"

---

**¡Listo para testear!** 🚀

