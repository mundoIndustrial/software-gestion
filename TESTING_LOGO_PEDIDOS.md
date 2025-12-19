# 🎨 LOGO Pedidos - Testing & Validación

## Resumen de Implementación

Se ha completado la implementación completa del sistema de LOGO Pedidos. A continuación se detalla cómo probar y validar cada componente.

---

## 📊 Componentes Implementados

### 1. Base de Datos
```
✅ Migración: 2025_12_19_create_logo_pedidos_table.php
   └─ Tabla: logo_pedidos
      └─ Campos: id, pedido_id, logo_cotizacion_id, numero_pedido, descripcion, 
                 tecnicas (JSON), ubicaciones (JSON), observaciones_tecnicas

✅ Migración: 2025_12_19_create_logo_pedido_imagenes_table.php
   └─ Tabla: logo_pedido_imagenes
      └─ Campos: id, logo_pedido_id, nombre_archivo, url, ruta_original, 
                 ruta_webp, tipo_archivo, tamaño_archivo, orden
```

### 2. Modelos Eloquent
```
✅ app/Models/LogoPedido.php
   ├─ Relaciones: pedidoProduccion(), logoCotizacion(), imagenes()
   ├─ Método: generarNumeroPedido() [LOGO-00001, LOGO-00002...]
   ├─ Casting: tecnicas (json), ubicaciones (json)
   └─ Accesores: getTecnicasAttribute(), getUbicacionesAttribute()

✅ app/Models/LogoPedidoImagen.php
   ├─ Relación: logoPedido()
   ├─ Accesor: getUrlMuestraAttribute()
   └─ Campos: nombre_archivo, url, ruta_original, ruta_webp, orden
```

### 3. Backend - Controlador
```
✅ PedidoProduccionController.php
   └─ Nuevo Método: guardarLogoPedido()
      ├─ Valida datos (pedido_id, tecnicas, ubicaciones, fotos)
      ├─ Genera numero_pedido automático
      ├─ Crea registro LogoPedido
      ├─ Procesa imágenes (base64 → almacenamiento)
      ├─ Crea referencias LogoPedidoImagen
      └─ Retorna JSON response
```

### 4. Rutas API
```
✅ routes/asesores/pedidos.php
   └─ POST /pedidos/guardar-logo-pedido
      └─ Controller: PedidoProduccionController@guardarLogoPedido
```

### 5. Frontend - JavaScript
```
✅ public/js/crear-pedido-editable.js
   ├─ Variables Globales:
   │  ├─ logoTecnicasSeleccionadas []
   │  ├─ logoSeccionesSeleccionadas []
   │  ├─ logoFotosSeleccionadas []
   │  └─ logoOpcionesPorUbicacion {}
   │
   └─ Funciones:
      ├─ renderizarCamposLogo()
      ├─ renderizarFotosLogo()
      ├─ agregarTecnicaLogo()
      ├─ abrirModalUbicacionLogo()
      ├─ eliminarFotoLogo()
      └─ Manejo de envío de formulario (detecta tipo LOGO)
```

---

## 🧪 Testing Manual

### Paso 1: Ejecutar Migraciones
```bash
cd c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial
php artisan migrate
```

**Verificar**:
```sql
SHOW TABLES LIKE 'logo_pedido%';
DESCRIBE logo_pedidos;
DESCRIBE logo_pedido_imagenes;
```

### Paso 2: Probar desde UI

1. **Ir a**: `http://localhost:8000/asesores/pedidos-produccion/crear-desde-cotizacion`
2. **Seleccionar**: Una cotización de tipo LOGO
3. **Verificar**:
   - ✅ Título cambia a "3 Información del Logo"
   - ✅ Alerta cambia a "Completa la información del logo..."
   - ✅ Se muestra formulario LOGO (5 secciones)

### Paso 3: Llenar Formulario LOGO

1. **Descripción**: Escribir algo como "Logo bordado del cliente"
2. **Imágenes**: Agregar 1-5 imágenes
3. **Técnicas**: Seleccionar BORDADO y/o DTF
4. **Ubicaciones**: 
   - Click "Agregar Ubicación"
   - Seleccionar CAMISA
   - Seleccionar PECHO y ESPALDA
   - Agregar observación
   - Click Guardar
5. **Observaciones**: Escribir algo

### Paso 4: Enviar Formulario

1. **Click**: Botón "Crear Pedido"
2. **Verificar Console** (F12):
   - ```
     🎨 Enviando formulario...
     🎨 [LOGO] Preparando datos de LOGO para enviar
     ✅ [LOGO] Pedido creado:
     🎨 [LOGO] Datos del LOGO pedido a guardar:
     ✅ [LOGO] Respuesta del servidor:
     ```

### Paso 5: Verificar en BD

```sql
-- Verificar LOGO Pedido creado
SELECT * FROM logo_pedidos 
WHERE numero_pedido LIKE 'LOGO-%'
ORDER BY created_at DESC LIMIT 1;

-- Verificar imágenes
SELECT * FROM logo_pedido_imagenes 
WHERE logo_pedido_id = (
  SELECT id FROM logo_pedidos 
  WHERE numero_pedido LIKE 'LOGO-%'
  ORDER BY created_at DESC LIMIT 1
)
ORDER BY orden;

-- Verificar estructura JSON
SELECT 
  numero_pedido,
  tecnicas,
  ubicaciones,
  descripcion
FROM logo_pedidos 
WHERE numero_pedido LIKE 'LOGO-%'
ORDER BY created_at DESC LIMIT 1\G
```

---

## 🔍 Validación de Datos

### JSON en Tecnicas
```json
["BORDADO", "DTF"]
```

### JSON en Ubicaciones
```json
[
  {
    "ubicacion": "CAMISA",
    "opciones": ["PECHO", "ESPALDA"],
    "observaciones": "Logo principal del cliente"
  }
]
```

### Fotos
```json
[
  {
    "url": "/storage/logo_pedidos/1/logo_1_xxx.jpg",
    "preview": "data:image/jpeg;base64,...",
    "existing": false,
    "id": null
  }
]
```

---

## 📝 Casos de Testing

### Test 1: LOGO con Solo Descripción
```
Input:
- Descripción: "Test LOGO"
- Tecnicas: []
- Ubicaciones: []
- Fotos: []

Expected: 
- ✅ LogoPedido creado
- ✅ numero_pedido = LOGO-00001
- ✅ Datos guardados correctamente
```

### Test 2: LOGO Completo
```
Input:
- Descripción: "Logo bordado de empresa"
- Tecnicas: ["BORDADO", "DTF"]
- Ubicaciones: [{ubicacion: "CAMISA", opciones: ["PECHO", "ESPALDA"], obs: "..."}]
- Fotos: 3 imágenes

Expected:
- ✅ LogoPedido creado
- ✅ 3 registros en logo_pedido_imagenes
- ✅ Imágenes almacenadas en /storage/logo_pedidos/{id}/
```

### Test 3: LOGO con Imágenes Existentes
```
Input:
- Fotos: [{existing: true, id: 5, url: "..."}]

Expected:
- ✅ Referencia creada en logo_pedido_imagenes
- ✅ No duplica archivos
```

### Test 4: Secuencia de Números
```
Crear 3 LOGO Pedidos

Expected:
- logo_pedidos[1].numero_pedido = LOGO-00001
- logo_pedidos[2].numero_pedido = LOGO-00002
- logo_pedidos[3].numero_pedido = LOGO-00003
```

---

## 🐛 Debugging

### Logs a Revisar
```
tail -f storage/logs/laravel.log
```

Buscar patrones:
- `🎨 [PedidoProduccionController]` - Información de LOGO
- `📸 Imagen nueva guardada` - Procesamiento de imágenes
- `✅ LogoPedido creado exitosamente` - Éxito
- `❌ Error guardando LOGO` - Errores

### Console del Navegador
```javascript
// En consola (F12):
console.log(logoTecnicasSeleccionadas);
console.log(logoSeccionesSeleccionadas);
console.log(logoFotosSeleccionadas);
```

### Network Tab
1. Abrir DevTools (F12)
2. Click pestaña Network
3. Crear LOGO Pedido
4. Verificar:
   - ✅ POST /asesores/pedidos-produccion/crear-desde-cotizacion/{id} → 200
   - ✅ POST /asesores/pedidos/guardar-logo-pedido → 200

---

## 📂 Estructura de Almacenamiento

```
storage/app/
└── logo_pedidos/
    ├── 1/
    │   ├── logo_1_1734656789_1234.jpg
    │   ├── logo_1_1734656790_5678.jpg
    │   └── logo_1_1734656791_9012.jpg
    ├── 2/
    │   └── logo_2_1734656800_3456.jpg
    └── 3/
        └── ...
```

URLs públicas:
```
/storage/logo_pedidos/1/logo_1_1734656789_1234.jpg
/storage/logo_pedidos/2/logo_2_1734656800_3456.jpg
```

---

## ✅ Checklist de Validación

- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Tablas creadas en BD (`SHOW TABLES LIKE 'logo_pedido%'`)
- [ ] Modelos creados (`app/Models/LogoPedido.php`, `LogoPedidoImagen.php`)
- [ ] Controlador con método `guardarLogoPedido()` creado
- [ ] Ruta `/pedidos/guardar-logo-pedido` registrada
- [ ] JavaScript actualizado con lógica LOGO
- [ ] Formulario LOGO se renderiza correctamente
- [ ] Campos editables funcionan
- [ ] Imágenes se pueden agregar/eliminar
- [ ] Técnicas se pueden seleccionar
- [ ] Ubicaciones se pueden editar
- [ ] Formulario se envía correctamente
- [ ] LOGO Pedido se crea en BD
- [ ] Imágenes se guardan en storage
- [ ] Respuesta JSON es exitosa

---

## 🚀 Siguientes Pasos (Opcionales)

1. **Crear Vista de Listado**
   - Mostrar todos los LOGO Pedidos
   - Filtros por numero_pedido, descripción, fecha

2. **Crear Vista de Detalle**
   - Mostrar info completa del LOGO
   - Galería de imágenes
   - Editar información

3. **Exportar a PDF**
   - Incluir descripción, técnicas, ubicaciones
   - Mostrar imágenes en PDF

4. **Dashboard**
   - Estadísticas de LOGO Pedidos
   - Gráficos de técnicas más usadas
   - Ubicaciones más frecuentes

---

## 📞 Soporte

Si encuentras errores:

1. **Verificar logs**: `tail -f storage/logs/laravel.log`
2. **Verificar BD**: Que las tablas existan y tengan datos
3. **Verificar almacenamiento**: Que el directorio `/storage/logo_pedidos/` exista
4. **Limpiar cache**: `php artisan cache:clear`
5. **Ejecutar migraciones**: `php artisan migrate`

---

**Implementado por**: Asistente IA  
**Fecha**: 2025-12-19  
**Versión**: 1.0  
**Estado**: ✅ Completo y Listo para Testing
