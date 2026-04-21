# Módulo Recepción Despacho - Documentación Técnica

## ✅ Lo que se ha creado

### 1. **Componente React Principal**
- **Archivo**: `resources/js/recepcion-despacho/RecepcionPrendas.jsx`
- **Características**:
  - Interfaz mobile-first (390×844px)
  - Buscador en tiempo real (cliente, prenda, pedido, recibo)
  - Filtros: Todos / Pendientes / Recibidos
  - Vista Lista: Cards con detalles de prendas
  - Vista Resumen: Progreso turno + desglose por cliente
  - Confirmación de recepción con animación y toast
  - Registro de fecha/hora exacta en formato AM/PM
  - Grilla de tallas con cantidades y totales

### 2. **Estructura de Carpetas**
```
resources/js/recepcion-despacho/
├── RecepcionPrendas.jsx      (Componente React)
└── entry.js                  (Punto de entrada + Vite)

resources/views/recepcion-despacho/
└── index.blade.php           (Vista Blade)

app/Http/Controllers/
└── RecepcionDespachoController.php  (Controlador)
```

### 3. **Rutas Configuradas**

**Web Routes** (`routes/recepcion-despacho.php`):
```
GET /recepcion-despacho               → RecepcionDespachoController@index
```

**API Routes** (`routes/api-recepcion-despacho.php`):
```
GET  /api/recepcion-despacho/items        → Obtener lista de prendas
POST /api/recepcion-despacho/{id}/confirmar → Confirmar recepción
```

### 4. **Configuración Vite**
- Agregado `resources/js/recepcion-despacho/entry.js` a `vite.config.js`
- Compilación automática en dev y production

## 🔧 Próximos Pasos - Backend (TODO)

### 1. **Crear el rol en base de datos**
```sql
INSERT INTO roles (name, description) 
VALUES ('recepcion_despacho', 'Personal de recepción en despacho');
```

### 2. **Configurar permisos**
Asignar el rol `recepcion_despacho` a los usuarios correspondientes:
```php
// En seeder o comando artisan
$user->assignRole('recepcion_despacho');
```

### 3. **Implementar lógica en RecepcionDespachoController**

**Método `getItems()`**:
- Consultar prendas en estado "pendiente" en el área de despacho
- Retornar estructura:
```php
[
    'id' => 1,
    'cliente' => 'CLIENTE NAME',
    'prenda' => 'Nombre Prenda',
    'descripcion' => 'Descripción...',
    'tallas' => [
        ['talla' => 'S', 'cantidad' => 10],
        ['talla' => 'M', 'cantidad' => 24],
        // ...
    ],
    'status' => 'pendiente',      // o 'recibido'
    'pedido' => 'PED-0841',
    'recibo' => 'REC-2201',
    'fechaLlegada' => ISO8601,    // Cuando llegó al área
    'fechaHora' => null,          // Se llena al confirmar
]
```

**Método `confirmarRecepcion($id)`**:
- Validar ID de prenda
- Actualizar status a 'recibido'
- Guardar fecha/hora exacta
- Registrar usuario que confirmó
- Retornar confirmación

### 4. **Validar Middleware de Rol**
Asegurar que el middleware `role:recepcion_despacho` esté configurado en `app/Http/Middleware/` o `bootstrap/app.php`

## 📱 Estructura de Datos Esperada

### Request GET `/api/recepcion-despacho/items`
**Response (200)**:
```json
[
  {
    "id": 1,
    "cliente": "ALMACENES ÉXITO",
    "prenda": "Camiseta Oxfort",
    "descripcion": "Cambio azul azul marino rebajos qty en m",
    "tallas": [
      {"talla": "S", "cantidad": 10},
      {"talla": "M", "cantidad": 24},
      {"talla": "L", "cantidad": 18},
      {"talla": "XL", "cantidad": 6}
    ],
    "status": "pendiente",
    "pedido": "PED-0841",
    "recibo": "REC-2201",
    "fechaLlegada": "2026-04-21T14:30:00.000Z",
    "fechaHora": null
  }
]
```

### Request POST `/api/recepcion-despacho/{id}/confirmar`
**Body**:
```json
{
  "status": "recibido",
  "fechaHora": "2026-04-21T14:35:22.000Z"
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Prenda recibida correctamente",
  "data": {
    "id": 1,
    "status": "recibido",
    "fechaHora": "2026-04-21T14:35:22.000Z"
  }
}
```

## 🎨 Estilos & Personalizaciones

El componente usa **Tailwind CSS**:
- Color primario: `#2563eb` (azul)
- Fondo: `gray-50`
- Cards: `white` con `border-gray-200`
- Colores de estado:
  - Pendiente: `yellow-100`
  - Recibido: `green-100`

### Tweaks disponibles en estado:
```javascript
const [tweaks] = useState({
  theme: 'light',        // 'light' | 'dark'
  accent: '#2563eb',     // Color principal
  density: 'normal',     // 'compact' | 'normal' | 'comfortable'
});
```

## 🚀 Cómo Ejecutar

### Desarrollo:
```bash
npm run dev
# Acceder a: http://localhost:8000/recepcion-despacho
```

### Producción:
```bash
npm run build
php artisan serve
```

## 🧪 Mock Data Incluido

Para demostración, el controlador retorna datos mock. Reemplazar con lógica real:
- 3 prendas de ejemplo
- Estados: pendiente y recibido
- Tallas y cantidades realistas

## 📝 Notas Técnicas

- **Componente React 18+** con Hooks
- **Sin librerías de UI externas** (solo Tailwind)
- **Iconos SVG inline** (sin dependencias adicionales)
- **Cache invalidación automática** en confirmación
- **CSRF Protection** incluido
- **Responsivo** para mobile (390-844px) y desktop

## ❓ Troubleshooting

**Error: Cannot find module 'react'**
- Verificar que `npm install react react-dom` se ejecutó correctamente

**Ruta `/recepcion-despacho` no funciona**
- Confirmar que el middleware de autenticación está activo
- Verificar que el usuario tiene el rol `recepcion_despacho`

**Componente no renderiza**
- Revisar console del navegador para errores
- Verificar que `#recepcion-despacho-app` existe en la vista Blade

---

**Creado**: 2026-04-21  
**Versión**: 1.0.0
