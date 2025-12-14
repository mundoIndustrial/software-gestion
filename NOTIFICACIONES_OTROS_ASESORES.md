# Implementación: Campana de Notificaciones para Asesores - Pedidos de Otros Asesores

## Objetivo
Mostrar en la campana de notificaciones de los asesores, los pedidos/cotizaciones que han sido creados por **otros asesores** en las últimas 24 horas, permitiendo que cada asesor sepa en qué número de cotización y número de pedido van los otros asesores.

## Cambios Realizados

### 1. Backend - `app/Http/Controllers/AsesoresController.php`

**Método modificado:** `getNotifications()`

**Cambios:**
- Se agregó una nueva sección que consulta los **pedidos de otros asesores** creados en las últimas 24 horas
- Se mapea la información para mostrar: `numero_pedido`, `numero_cotizacion`, `cliente`, `asesor_nombre`, `estado` y fecha de creación
- Se retorna junto con los pedidos propios del usuario

**Nueva respuesta JSON:**
```json
{
  "pedidos_otros_asesores": [
    {
      "numero_pedido": "12345",
      "numero_cotizacion": "54321",
      "cliente": "Cliente XYZ",
      "asesor_nombre": "Juan Pérez",
      "estado": "En Ejecución",
      "created_at": "2025-12-14T10:30:00"
    }
  ],
  "pedidos_proximos_entregar": [...],
  "pedidos_en_ejecucion": 3,
  "total_notificaciones": 5
}
```

### 2. Frontend - `public/js/asesores/notifications.js`

**Función modificada:** `renderNotifications(data)`

**Cambios:**
- Se procesan los pedidos de otros asesores que vienen en `data.pedidos_otros_asesores`
- Se calcula el tiempo transcurrido desde que se creó el pedido (minutos, horas, días)
- Se muestra el nombre del asesor + número de cotización en el título
- Se muestra el número de pedido + cliente en el mensaje
- El formato es: 
  - **Título**: `{ASESOR} - COT-{NUMERO_COT}`
  - **Mensaje**: `PED-{NUMERO_PED} - {CLIENTE}`
  - **Tiempo**: `Hace {X} minutos/horas/días`

**Estructura de notificación para otros asesores:**
```javascript
{
  icon: 'fa-shopping-cart',
  color: '#10b981',  // Verde
  title: 'Juan Pérez - COT-54321',
  message: 'PED-12345 - Cliente XYZ',
  time: 'Hace 2 horas'
}
```

## Datos Mostrados

### Para Pedidos de Otros Asesores:
- ✅ **Nombre del Asesor** (en el título)
- ✅ **Número de Cotización** (COT-XXXXX)
- ✅ **Número de Pedido** (PED-XXXXX)
- ✅ **Nombre del Cliente**
- ✅ **Tiempo Transcurrido** (Hace X minutos/horas/días)

### Rango de Tiempo:
- Se muestran los pedidos creados en las **últimas 24 horas**
- Se limita a los **últimos 10 pedidos** más recientes

## Apariencia Visual

**Notificación de Pedido de Otro Asesor:**
```
[ICONO CARRITO VERDE]  Juan Pérez - COT-54321
                       PED-12345 - Cliente XYZ
                       Hace 2 horas
```

**Notificación de Orden Propia Próxima a Vencer:**
```
[ICONO RELOJ AZUL]     Tu orden próxima a vencer
                       PED-67890 - Cliente ABC
                       Vence en 3 días
```

**Notificación de Órdenes en Ejecución (Propias):**
```
[ICONO ALERTA ROJO]    Órdenes en ejecución
                       Tienes 3 órdenes en ejecución
                       Requiere atención
```

## Logística de Notificaciones

### Orden de Visualización:
1. **Primero**: Pedidos de otros asesores (últimas 24 horas)
2. **Luego**: Órdenes propias próximas a vencer (próximos 7 días)
3. **Finalmente**: Órdenes propias en ejecución

### Actualización Automática:
- Las notificaciones se actualizan automáticamente cada **30 segundos**
- Se mantiene el historial de visualización (sesión)

## Rutas Utilizadas

**Endpoint de Notificaciones:**
```
GET /asesores/notifications
```

**Respuesta del servidor incluye:**
- `pedidos_otros_asesores` (array) - Nuevos pedidos de otros asesores
- `pedidos_proximos_entregar` (array) - Órdenes propias próximas a vencer
- `pedidos_en_ejecucion` (integer) - Cantidad de órdenes propias en ejecución
- `total_notificaciones` (integer) - Suma total de todas las notificaciones

## Características Técnicas

### Backend:
- Usa `whereNotNull('asesor_id')` para asegurar que el pedido tiene asesor
- Filtra `where('asesor_id', '!=', $userId)` para excluir al usuario actual
- Usa `orderBy('created_at', 'desc')` para mostrar más recientes primero
- Carga la relación `with('asesora')` para obtener el nombre del asesor

### Frontend:
- Cálculo automático de tiempo transcurrido con lógica de minutos/horas/días
- Format de números de cotización/pedido con padding a 5 dígitos (COT-00123)
- Manejo de casos donde el asesor es null (muestra "Desconocido")
- Ícono verde (carrito de compra) para distinguir de otras notificaciones

## Consideraciones de UX

✅ **Pro-activa**: Cada asesor ve lo que otros están haciendo (colaboración)
✅ **Contextual**: Muestra número de cotización para rastrear el trabajo
✅ **Temporal**: Solo muestra últimas 24 horas para no saturar
✅ **Limitada**: Máximo 10 notificaciones de otros asesores
✅ **Diferenciada**: Color verde y ícono diferente para no confundir con alertas propias

## Próximas Mejoras Sugeridas

- Agregar filtro por asesor en la campana
- Permitir expandir notificaciones para ver más detalles
- Agregar sonido de notificación cuando llega pedido de otro asesor
- Mostrar progreso de estado de los pedidos de otros asesores
- Agregar estadísticas: "3 asesores activos en las últimas 2 horas"
