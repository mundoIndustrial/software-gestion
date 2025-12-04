# 🎯 ROL SUPERVISOR_PEDIDOS - GUÍA DE IMPLEMENTACIÓN

## 📋 Resumen Ejecutivo

Se ha implementado un nuevo rol **`supervisor_pedidos`** con una interfaz completa para supervisar órdenes de producción. El sistema incluye:

- ✅ Tabla de órdenes con filtros avanzados
- ✅ Modal para ver detalles de órdenes
- ✅ Descarga de PDF de órdenes
- ✅ Modal de anulación con observaciones obligatorias
- ✅ Sidebar personalizado
- ✅ Diseño heredado del layout de asesores

---

## 🗂️ Archivos Creados

### 1. **Controller**
```
app/Http/Controllers/SupervisorPedidosController.php
```

**Métodos principales:**
- `index()` - Lista órdenes con filtros
- `show()` - Ver detalle de orden
- `descargarPDF()` - Descargar PDF
- `anular()` - Anular orden con observación
- `cambiarEstado()` - Cambiar estado
- `obtenerDatos()` - Obtener datos en JSON

### 2. **Vistas**
```
resources/views/supervisor-pedidos/index.blade.php
resources/views/supervisor-pedidos/pdf.blade.php
```

### 3. **Sidebar**
```
resources/views/components/sidebars/sidebar-supervisor-pedidos.blade.php
```

### 4. **Rutas**
Agregadas en `routes/web.php` (líneas 372-393)

---

## 🚀 Características Implementadas

### 1. **Tabla de Órdenes**
- Columnas: ID, Cliente, Fecha, Total, Estado, Asesora, Forma Pago, Acciones
- Paginación: 15 órdenes por página
- Diseño responsive
- Badges de estado con colores diferenciados

### 2. **Filtros Avanzados**
- Por estado (No iniciado, En Ejecución, Entregado, Anulada)
- Por cliente (búsqueda)
- Por asesora (búsqueda)
- Por rango de fechas
- Botones: Filtrar y Limpiar

### 3. **Acciones en Tabla**
Cada orden tiene 3 botones:

**👁️ Ver Orden**
- Abre modal con detalles completos
- Muestra información general
- Muestra tabla de prendas
- Carga datos dinámicamente

**📄 Descargar PDF**
- Genera PDF profesional
- Incluye logo, información general
- Tabla de prendas con estados
- Motivo de anulación (si aplica)
- Firmas para supervisor y responsable

**❌ Anular Orden** (solo si no está anulada)
- Abre modal de confirmación
- Campo de observación obligatorio (10-500 caracteres)
- Contador de caracteres en tiempo real
- Botones: Cancelar y Confirmar Anulación

### 4. **Modal de Anulación**
```
┌─────────────────────────────────────┐
│ ⚠️  ¿Anular Orden #ORD-001?         │
├─────────────────────────────────────┤
│ Esta acción cancelará la orden...   │
│                                     │
│ Motivo de anulación *               │
│ ┌─────────────────────────────────┐ │
│ │ Ej: El cliente solicitó...      │ │
│ └─────────────────────────────────┘ │
│ 0/500 caracteres                    │
│                                     │
│ [Cancelar] [Confirmar Anulación]   │
└─────────────────────────────────────┘
```

### 5. **PDF Profesional**
- Header con logo y título
- Información general en boxes
- Tabla de prendas con estados
- Sección de observaciones (si está anulada)
- Footer con espacios para firmas
- Optimizado para impresión

### 6. **Sidebar Personalizado**
- Logo con enlace al dashboard
- Menú principal: Órdenes de Producción
- Filtros rápidos por estado
- Información del usuario
- Botón de logout
- Diseño colapsable

---

## 🔧 Configuración Requerida

### 1. **Crear el Rol en la BD**

```sql
INSERT INTO roles (name, description, requires_credentials, created_at, updated_at) 
VALUES ('supervisor_pedidos', 'Supervisor de Pedidos de Producción', 0, NOW(), NOW());
```

### 2. **Asignar Rol a Usuario**

```php
// En tinker o en un seeder
$user = User::find(1);
$user->role_id = 5; // ID del rol supervisor_pedidos
$user->save();
```

O usando múltiples roles:

```php
$user->addRole(5); // Agregar rol supervisor_pedidos
```

### 3. **Migración de BD (Opcional)**

Si la tabla `pedidos_produccion` no existe, crear migración:

```bash
php artisan make:migration create_pedidos_produccion_table
```

Estructura mínima:
```php
Schema::create('pedidos_produccion', function (Blueprint $table) {
    $table->id();
    $table->integer('numero_pedido')->unique();
    $table->string('cliente');
    $table->string('asesora')->nullable();
    $table->string('forma_de_pago')->nullable();
    $table->string('estado')->default('No iniciado');
    $table->date('fecha_de_creacion_de_orden');
    $table->date('fecha_estimada_entrega')->nullable();
    $table->decimal('total', 10, 2)->nullable();
    $table->text('motivo_anulacion')->nullable();
    $table->string('usuario_anulacion')->nullable();
    $table->timestamp('fecha_anulacion')->nullable();
    $table->timestamps();
});
```

---

## 📍 Rutas Disponibles

```php
// Listar órdenes
GET /supervisor-pedidos/

// Ver detalle
GET /supervisor-pedidos/{id}

// Descargar PDF
GET /supervisor-pedidos/{id}/pdf

// Anular orden
POST /supervisor-pedidos/{id}/anular

// Cambiar estado
PATCH /supervisor-pedidos/{id}/estado

// Obtener datos JSON
GET /supervisor-pedidos/{id}/datos
```

---

## 🎨 Diseño y Estilos

### Colores Utilizados
- **Primario**: #3498db (Azul)
- **Secundario**: #2c3e50 (Gris oscuro)
- **Éxito**: #27ae60 (Verde)
- **Advertencia**: #f39c12 (Naranja)
- **Peligro**: #e74c3c (Rojo)
- **Fondo**: #f5f7fa (Gris claro)

### Componentes
- Tabla responsive con hover effects
- Modales con animaciones
- Botones con iconos Material Symbols
- Badges de estado
- Filtros con validación
- Contador de caracteres en tiempo real

---

## 🔐 Seguridad

### Middleware Aplicado
```php
Route::middleware(['auth', 'role:supervisor_pedidos,admin'])
```

### Validaciones
- Autenticación requerida
- Rol específico requerido
- Validación de motivo de anulación (10-500 caracteres)
- CSRF token en formularios
- Logs de auditoría para anulaciones

### Logs Generados
```
Orden #{numero_pedido} anulada por {usuario}
- Motivo: {motivo}
- Fecha: {timestamp}
```

---

## 📱 Responsividad

### Breakpoints
- **Desktop**: 1200px+
- **Tablet**: 768px - 1199px
- **Mobile**: < 768px

### Adaptaciones
- Tabla se vuelve scrollable en móvil
- Filtros se apilan verticalmente
- Modales se ajustan al ancho de pantalla
- Sidebar se colapsa automáticamente

---

## 🧪 Testing

### Casos de Prueba

**1. Listar órdenes**
```
✓ Acceder a /supervisor-pedidos/
✓ Ver tabla con órdenes
✓ Paginación funciona
✓ Filtros funcionan
```

**2. Ver detalle**
```
✓ Hacer clic en botón "Ver"
✓ Modal se abre con datos
✓ Información completa se muestra
✓ Modal se cierra al hacer clic fuera
```

**3. Descargar PDF**
```
✓ Hacer clic en botón "PDF"
✓ PDF se descarga correctamente
✓ PDF contiene toda la información
✓ PDF es imprimible
```

**4. Anular orden**
```
✓ Hacer clic en botón "Anular"
✓ Modal de confirmación se abre
✓ Validación: motivo vacío no permite anular
✓ Validación: motivo < 10 caracteres no permite
✓ Contador de caracteres funciona
✓ Al confirmar, orden se anula
✓ Página se recarga
✓ Orden aparece con estado "Anulada"
```

---

## 📊 Estructura de Datos

### Tabla: pedidos_produccion
```
id                          INT (PK)
numero_pedido               INT (UNIQUE)
cliente                     VARCHAR
asesora                     VARCHAR
forma_de_pago               VARCHAR
estado                      VARCHAR (No iniciado, En Ejecución, Entregado, Anulada)
fecha_de_creacion_de_orden  DATE
fecha_estimada_entrega      DATE
total                       DECIMAL
motivo_anulacion            TEXT
usuario_anulacion           VARCHAR
fecha_anulacion             TIMESTAMP
created_at                  TIMESTAMP
updated_at                  TIMESTAMP
```

---

## 🔄 Flujo de Uso

1. **Acceso**
   - Usuario con rol `supervisor_pedidos` accede a `/supervisor-pedidos/`

2. **Visualización**
   - Ve tabla de órdenes con información general
   - Puede filtrar por estado, cliente, asesora, fechas

3. **Supervisión**
   - Hace clic en "Ver" para ver detalles completos
   - Descarga PDF si necesita impresión

4. **Anulación** (si es necesario)
   - Hace clic en "Anular"
   - Ingresa motivo de anulación
   - Confirma acción
   - Orden se marca como "Anulada"

5. **Auditoría**
   - Sistema registra quién anuló y cuándo
   - Se guarda el motivo de anulación

---

## 🚨 Troubleshooting

### Problema: "No tienes permiso para acceder"
**Solución**: Verificar que el usuario tenga el rol `supervisor_pedidos`

### Problema: Modal no se abre
**Solución**: Verificar que JavaScript esté habilitado y sin errores en consola

### Problema: PDF no se descarga
**Solución**: Verificar que `barryvdh/laravel-dompdf` esté instalado

### Problema: Anulación no funciona
**Solución**: Verificar que el motivo tenga al menos 10 caracteres

---

## 📝 Notas Importantes

1. **Herencia de Layout**: La vista hereda del layout de asesores (`asesores.layout`)
2. **Sidebar Personalizado**: Usa `sidebar-supervisor-pedidos.blade.php`
3. **Relaciones**: Requiere que `PedidoProduccion` tenga relación con `prendas`
4. **PDF**: Usa `barryvdh/laravel-dompdf` para generar PDFs
5. **Logs**: Se registran en `storage/logs/laravel.log`

---

## 🎓 Próximos Pasos (Opcionales)

1. Agregar exportación a Excel
2. Agregar gráficos de estadísticas
3. Agregar notificaciones por email
4. Agregar historial de cambios
5. Agregar búsqueda avanzada
6. Agregar reportes personalizados

---

## 📞 Soporte

Para reportar problemas o sugerencias, contactar al equipo de desarrollo.

---

**Fecha**: Diciembre 2025
**Versión**: 1.0
**Estado**: ✅ Completado y Funcional
