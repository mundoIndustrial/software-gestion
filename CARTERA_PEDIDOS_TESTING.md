# TESTING - CARTERA PEDIDOS

## 🧪 Cómo Probar la Interfaz

### 1. Acceder a la Página

```
http://localhost:8000/cartera/pedidos
```

Asegúrate de estar logueado con un usuario que tenga rol `cartera` o `admin`.

---

## 🔍 Testing en Consola del Navegador

Abre la consola con `F12` y prueba estos comandos:

### Verificar que el script está cargado
```javascript
console.log(pedidosData);  // Debe mostrar array de pedidos
console.log(pedidoSeleccionado);  // Debe ser null inicialmente
```

### Simular carga de pedidos con datos de prueba
```javascript
// Dato de prueba
const mockPedidos = [
  {
    id: 1,
    numero_pedido: "PED-2024-001",
    cliente: "Cliente ABC",
    estado: "Pendiente cartera",
    fecha_de_creacion_de_orden: "2024-01-20T10:30:00",
    asesora: { id: 5, name: "María García" },
    forma_de_pago: "Crédito",
    fecha_estimada_de_entrega: "2024-02-01"
  },
  {
    id: 2,
    numero_pedido: "PED-2024-002",
    cliente: "Cliente XYZ",
    estado: "Pendiente cartera",
    fecha_de_creacion_de_orden: "2024-01-21T14:15:00",
    asesora: { id: 6, name: "Laura Martínez" },
    forma_de_pago: "Contado",
    fecha_estimada_de_entrega: "2024-02-05"
  }
];

// Asignar y renderizar
pedidosData = mockPedidos;
renderizarTabla(pedidosData);
```

### Probar formateo de fechas
```javascript
const fecha = "2024-01-20T10:30:00";
console.log(formatearFecha(fecha));  // Debe mostrar: 20/01/2024
```

### Probar notificaciones toast
```javascript
mostrarNotificacion("Esto es un mensaje de éxito", "success");
mostrarNotificacion("Esto es un error", "error");
mostrarNotificacion("Esto es una advertencia", "warning");
mostrarNotificacion("Esto es información", "info");
```

### Simular apertura de modal de aprobación
```javascript
abrirModalAprobacion(1, "PED-2024-001");
// Luego cierra con:
cerrarModalAprobacion();
```

### Simular apertura de modal de rechazo
```javascript
abrirModalRechazo(1, "PED-2024-001");
// Llena el textarea:
document.getElementById("motivoRechazo").value = "Crédito vencido del cliente";
// Luego cierra con:
cerrarModalRechazo();
```

### Probar validación de contadores
```javascript
// Escribir en textarea
document.getElementById("motivoRechazo").value = "Esto es un motivo de prueba";
// Disparar evento input
const event = new Event("input");
document.getElementById("motivoRechazo").dispatchEvent(event);
// Verificar contador
console.log(document.getElementById("contadorRechazo").textContent);
```

---

## 📡 Testing de API Calls

### Simular GET /api/pedidos

```javascript
fetch('/api/pedidos?estado=pendiente_cartera', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
  },
  credentials: 'same-origin'
})
.then(response => response.json())
.then(data => {
  console.log('Respuesta:', data);
  console.log('Número de pedidos:', data.data.length);
})
.catch(error => console.error('Error:', error));
```

### Simular POST /api/pedidos/{id}/aprobar

```javascript
const pedidoId = 1;
fetch(`/api/pedidos/${pedidoId}/aprobar`, {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    'Content-Type': 'application/json',
  },
  credentials: 'same-origin',
  body: JSON.stringify({
    pedido_id: pedidoId,
    accion: 'aprobar'
  })
})
.then(response => response.json())
.then(data => {
  console.log('Respuesta:', data);
  console.log('Éxito:', data.success);
})
.catch(error => console.error('Error:', error));
```

### Simular POST /api/pedidos/{id}/rechazar

```javascript
const pedidoId = 1;
const motivo = "Crédito vencido. El cliente tiene deudas pendientes superiores al límite.";

fetch(`/api/pedidos/${pedidoId}/rechazar`, {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    'Content-Type': 'application/json',
  },
  credentials: 'same-origin',
  body: JSON.stringify({
    pedido_id: pedidoId,
    motivo: motivo,
    accion: 'rechazar'
  })
})
.then(response => response.json())
.then(data => {
  console.log('Respuesta:', data);
  console.log('Éxito:', data.success);
})
.catch(error => console.error('Error:', error));
```

---

##  Puntos de Verificación

### Vista se carga correctamente
- [ ] Página muestra título "Cartera - Pedidos por Aprobar"
- [ ] Hay un botón "Actualizar"
- [ ] Tabla con columnas: # Pedido, Cliente, Estado, Fecha, Acciones
- [ ] Hay spinner de carga inicial

### Tabla carga datos
- [ ] Si hay pedidos: se muestran en la tabla
- [ ] Si no hay pedidos: muestra "No hay pedidos para revisar"
- [ ] Cada fila tiene botones Aprobar y Rechazar

### Modal de Aprobación
- [ ] Hace clic en Aprobar → se abre modal
- [ ] Modal muestra número de pedido
- [ ] Modal muestra datos del pedido (cliente, fecha, etc.)
- [ ] Botón "Aprobar Pedido" está visible
- [ ] Botón "Cancelar" cierra el modal
- [ ] Presionar ESC cierra el modal
- [ ] Clic en overlay cierra el modal

### Modal de Rechazo
- [ ] Hace clic en Rechazar → se abre modal
- [ ] Modal muestra número de pedido
- [ ] Textarea para ingresar motivo
- [ ] Contador de caracteres funciona (0/1000)
- [ ] Botón "Confirmar Rechazo" está visible
- [ ] Validación: textarea vacío → botón deshabilitado
- [ ] Validación: < 10 caracteres → muestra advertencia
- [ ] Botón "Cancelar" cierra el modal

### Notificaciones
- [ ] Aparecen en top-right
- [ ] Desaparecen automáticamente después de 5s
- [ ] Se muestran diferentes colores según tipo
- [ ] Texto es legible

### Responsiveness
- [ ] Desktop (1920px): todo visible y bien espaciado
- [ ] Tablet (768px): tabla con scroll horizontal funciona
- [ ] Mobile (375px): modales ocupan 95% del ancho

---

## 🐛 Debugging Tips

### Ver qué se está enviando a la API
```javascript
// En DevTools → Network tab
// Haz clic en Aprobar o Rechazar
// Verifica el request POST
// Mira Headers y Body
```

### Ver errores de JavaScript
```javascript
// En DevTools → Console tab
// Busca mensajes con ❌ o 🚫
// Los logs incluyen contexto del error
```

### Verificar token CSRF
```javascript
console.log(document.querySelector('meta[name="csrf-token"]').content);
```

### Verificar estado global
```javascript
console.log({
  pedidosData: pedidosData,
  pedidoSeleccionado: pedidoSeleccionado,
  modalRechazoVisible: document.getElementById('modalRechazo').style.display,
  modalAprobacionVisible: document.getElementById('modalAprobacion').style.display
});
```

---

##  Flujo de Testing Completo

### 1. Test básico de carga
```javascript
// Debería ver en consola:
//  Cartera Pedidos - Inicializado
//  Pedidos cargados: [Array]
//  Script de Cartera Pedidos cargado correctamente
```

### 2. Test de interfaz
```javascript
// 1. Abrir página → ver spinner
// 2. Esperar → spinner desaparece, tabla aparece
// 3. Verificar datos en tabla
```

### 3. Test de aprobación
```javascript
// 1. Clic en botón Aprobar
// 2. Modal abre con datos
// 3. Clic en "Confirmar Aprobación"
// 4. Se ve spinner en botón
// 5. Modal cierra
// 6. Toast de éxito aparece
// 7. Tabla se recarga
```

### 4. Test de rechazo
```javascript
// 1. Clic en botón Rechazar
// 2. Modal abre
// 3. Escribir motivo (mínimo 10 caracteres)
// 4. Clic en "Confirmar Rechazo"
// 5. Se ve spinner en botón
// 6. Modal cierra
// 7. Toast de éxito aparece
// 8. Tabla se recarga
```

---

## 📊 Ejemplo de Respuesta Correcta

### GET /api/pedidos?estado=pendiente_cartera

```json
{
  "data": [
    {
      "id": 1,
      "numero_pedido": "PED-2024-001",
      "cliente": "Cliente ABC",
      "estado": "Pendiente cartera",
      "fecha_de_creacion_de_orden": "2024-01-20T10:30:00",
      "asesora": {
        "id": 5,
        "name": "María García"
      },
      "forma_de_pago": "Crédito",
      "fecha_estimada_de_entrega": "2024-02-01"
    }
  ],
  "total": 1,
  "per_page": 50,
  "current_page": 1,
  "last_page": 1,
  "message": "Pedidos obtenidos correctamente"
}
```

### POST /api/pedidos/1/aprobar

```json
{
  "message": "Pedido aprobado correctamente",
  "data": {
    "id": 1,
    "numero_pedido": "PED-2024-001",
    "cliente": "Cliente ABC",
    "estado": "Aprobado por Cartera",
    "fecha_de_creacion_de_orden": "2024-01-20 10:30:00",
    "asesora": {
      "id": 5,
      "name": "María García"
    },
    "forma_de_pago": "Crédito",
    "fecha_estimada_de_entrega": "2024-02-01",
    "aprobado_por_cartera_en": "2024-01-23 10:45:00"
  },
  "success": true
}
```

### POST /api/pedidos/1/rechazar

```json
{
  "message": "Pedido rechazado correctamente",
  "data": {
    "id": 1,
    "numero_pedido": "PED-2024-001",
    "cliente": "Cliente ABC",
    "estado": "Rechazado por Cartera",
    "fecha_de_creacion_de_orden": "2024-01-20 10:30:00",
    "asesora": {
      "id": 5,
      "name": "María García"
    },
    "forma_de_pago": "Crédito",
    "fecha_estimada_de_entrega": "2024-02-01",
    "motivo_rechazo": "Crédito vencido. El cliente tiene deudas pendientes superiores al límite.",
    "rechazado_por_cartera_en": "2024-01-23 10:50:00",
    "notificacion_enviada": true
  },
  "success": true
}
```

---

## ❌ Ejemplos de Errores Esperados

### API no disponible
```json
{
  "message": "Error al cargar los pedidos",
  "error": "Failed to fetch"
}
```

### Usuario sin permisos
```json
{
  "message": "No tienes permiso para acceder a este recurso",
  "error": "Acceso denegado"
}
```

### Pedido no encontrado
```json
{
  "message": "Pedido no encontrado",
  "error": "El pedido con ID 999 no existe"
}
```

### Validación fallida en rechazo
```json
{
  "message": "Validación fallida",
  "errors": {
    "motivo": [
      "El motivo es requerido y debe tener al menos 10 caracteres"
    ]
  }
}
```

---

**Última actualización:** 23 de Enero, 2024
