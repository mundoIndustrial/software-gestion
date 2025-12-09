# Guía: Exportar Datos de Corte a Google Sheets

## Descripción
Esta funcionalidad permite generar un reporte de todos los pedidos que pasaron al área de corte en un mes específico, listo para copiar y pegar en Google Sheets.

## Cómo Usar

### 1. Acceder a la Vista
- URL: `http://localhost/exportar-corte`
- Requiere autenticación y permisos de supervisor

### 2. Seleccionar Mes y Año
- Selecciona el **mes** del dropdown (Enero - Diciembre)
- Ingresa el **año** (ej: 2024)
- Por defecto muestra el mes y año actual

### 3. Generar Reporte
- Haz clic en el botón **"Generar Reporte"**
- El sistema procesará los datos y mostrará el resultado

### 4. Copiar al Portapapeles
- Haz clic en **"Copiar al Portapapeles"**
- El reporte se copia automáticamente en formato TSV (Tab-Separated Values)
- Verás un mensaje de confirmación

### 5. Pegar en Google Sheets
- Abre Google Sheets
- Crea una nueva hoja o abre una existente
- Haz clic en la celda A1
- Pega el contenido (Ctrl+V)
- Google Sheets distribuirá automáticamente los datos en columnas

## Columnas del Reporte

| Columna | Descripción | Fuente |
|---------|-------------|--------|
| **Fecha de Ingreso a Corte** | Fecha en que el pedido pasó a corte | `tabla_original.corte` |
| **Número Pedido** | ID del pedido | `tabla_original.pedido` |
| **Cliente** | Nombre del cliente | `tabla_original.cliente` |
| **Prendas** | Nombres de prendas (separados por comas) | `registros_por_orden.prenda` |
| **Descripción** | Descripciones de prendas (separadas por \|) | `registros_por_orden.descripcion` |
| **Tallas** | Todas las tallas del pedido (separadas por comas) | `registros_por_orden.talla` |
| **Total** | Suma total de cantidades | `registros_por_orden.cantidad` |
| **Cortador** | Encargado del corte | `tabla_original.encargados_de_corte` |
| **Fecha Terminación** | Fecha cuando salió de corte (siguiente área) | Calculada automáticamente |
| **Género** | Dama, Caballero, Ambos o No aplica | Detectado de prendas/descripciones |

## Lógica de Detección

### Género
Se analiza el texto de prendas y descripciones buscando:
- **Dama**: "dama", "mujer", "femenino"
- **Caballero**: "caballero", "hombre", "masculino"
- Si encuentra ambos: "Dama, Caballero"
- Si no encuentra ninguno: "No aplica"

### Fecha de Terminación
Se busca la siguiente fecha de cambio de área después de corte:
1. Costura
2. Bordado
3. Estampado
4. Lavandería
5. Arreglos
6. Control de Calidad
7. Entrega

Se retorna la fecha más cercana (la primera después de corte).

## Archivos Involucrados

- **Controlador**: `/app/Http/Controllers/ExportarCorteController.php`
- **Vista**: `/resources/views/exportar-corte/index.blade.php`
- **Rutas**: `/routes/web.php` (líneas 137-138)

## Rutas Disponibles

```php
GET  /exportar-corte              // Mostrar la vista
POST /exportar-corte/generate     // Generar el reporte (AJAX)
```

## Requisitos

- Autenticación requerida
- Middleware: `supervisor-readonly` (acceso de lectura)
- Permisos: Supervisor o superior

## Formato de Salida

El reporte se genera en formato **TSV** (Tab-Separated Values):
- Encabezados en la primera fila
- Datos separados por tabulaciones
- Compatible con Google Sheets, Excel, etc.

## Ejemplo de Salida

```
Fecha de Ingreso a Corte	Número Pedido	Cliente	Prendas	Descripción	Tallas	Total	Cortador	Fecha Terminación	Género
01/11/2024	12345	CLIENTE A	Polo Roja, Pantalón Jean	Logo bordado | Bolsillos	S:10, M:15, L:5	30	Juan Pérez	05/11/2024	Caballero
02/11/2024	12346	CLIENTE B	Camisa Drill	Reflectivo espalda	XS:5, S:10	15	María García	08/11/2024	Dama
```

## Troubleshooting

### "No hay pedidos en corte para el mes y año especificados"
- Verifica que existan pedidos con fecha en la columna `corte`
- Asegúrate de haber seleccionado el mes y año correcto

### El reporte está vacío
- Algunos pedidos pueden no tener registros en `registros_por_orden`
- Verifica que los pedidos tengan datos en esa tabla

### Las fechas se ven incorrectas
- El sistema intenta parsear fechas en múltiples formatos
- Si una fecha es inválida, se ignora

## Notas Importantes

- El reporte se genera en tiempo real desde la base de datos
- No se guardan copias del reporte en el servidor
- El formato TSV es ideal para Google Sheets
- Las descripciones múltiples se separan con " | " (pipe)
- Las prendas múltiples se separan con ", " (coma-espacio)
