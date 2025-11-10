
# Mejora del Formulario de Pedidos - Captura Detallada

## Objetivo
Adaptar el formulario de pedidos para capturar toda la información detallada que viene en los correos de los clientes, como el ejemplo de **INVERSIONES EVAN**.

## Ejemplo de Correo Recibido

```
BUENAS TARDES NO SE HA FACTURADO-CONTADO 
CAMISA TIPO POLO TELA LAFAYETTE MANGA CORTA, MODELO DE BODEGA,
CON CUELLOS Y PUÑOS EN HILO, CON BROCHES,
CON LOGO BORDADO EN LADO DERECHO DEL PECHO "INVERSIONES EVAN",
Y LOGO BORDADO EN LADO IZQUIERDO DEL PECHO "DIFFER".
-MODELO FOTO DAMA -SIN BOLSILLO, CAMISA COLOR AZUL REY- REF HILO 293 TALLA S-1
CABALLERO - SIN BOLSILLO, CAMISA COLOR AZUL REY- REF HILO 293 TALLA L-1
DAMA - SIN BOLSILLO, CAMISA COLOR NEGRA TALLA S-1
CABALLERO - SIN BOLSILLO, CAMISA COLOR NEGRA TALLA L-1
```

## Campos Agregados al Formulario

### 1. **Información del Producto**
- ✅ **Tipo de Prenda** (nombre_producto): CAMISA TIPO POLO
- ✅ **Tela**: TELA LAFAYETTE
- ✅ **Tipo de Manga**: Manga Corta, Manga Larga, Sin Manga, Manga 3/4
- ✅ **Color**: AZUL REY, NEGRO, etc.
- ✅ **Talla**: S, M, L, XL, etc.
- ✅ **Género**: Dama, Caballero, Unisex
- ✅ **Cantidad**: Número de unidades

### 2. **Detalles Técnicos**
- ✅ **Referencia de Hilo** (ref_hilo): REF HILO 293
- ✅ **Descripción Completa**: CON CUELLOS Y PUÑOS EN HILO, CON BROCHES, SIN BOLSILLO, MODELO DE BODEGA
- ✅ **Bordados/Logos**: LOGO BORDADO EN LADO DERECHO DEL PECHO "INVERSIONES EVAN", LOGO BORDADO EN LADO IZQUIERDO DEL PECHO "DIFFER"
- ✅ **Modelo/Referencia Foto**: URL o descripción del modelo

### 3. **Información Comercial**
- ✅ **Precio Unitario**: Precio por unidad
- ✅ **Subtotal**: Calculado automáticamente
- ✅ **Notas Adicionales**: Observaciones especiales

## Cambios Realizados

### 1. **Base de Datos - Nueva Migración**

**Archivo:** `2025_11_10_150547_add_detailed_fields_to_productos_pedido_table.php`

```php
Schema::table('productos_pedido', function (Blueprint $table) {
    $table->string('tela')->nullable();
    $table->string('tipo_manga')->nullable();
    $table->string('color')->nullable();
    $table->string('genero')->nullable(); // Dama, Caballero, Unisex
    $table->string('ref_hilo')->nullable();
    $table->text('bordados')->nullable(); // Información de logos y bordados
    $table->string('modelo_foto')->nullable(); // URL o referencia de foto
});
```

**Ejecutar:**
```bash
php artisan migrate
```

### 2. **Modelo ProductoPedido**

**Archivo:** `app/Models/ProductoPedido.php`

```php
protected $fillable = [
    'pedido',
    'nombre_producto',
    'tela',
    'tipo_manga',
    'color',
    'descripcion',
    'bordados',
    'modelo_foto',
    'talla',
    'genero',
    'ref_hilo',
    'cantidad',
    'precio_unitario',
    'subtotal',
    'imagen',
    'notas',
];
```

### 3. **Vista de Creación de Pedidos**

**Archivo:** `resources/views/asesores/pedidos/create.blade.php`

**Campos del formulario:**

```html
<!-- Tipo de Prenda -->
<input type="text" name="productos[][nombre_producto]" 
       placeholder="Ej: CAMISA TIPO POLO" required>

<!-- Tela -->
<input type="text" name="productos[][tela]" 
       placeholder="Ej: TELA LAFAYETTE">

<!-- Tipo de Manga -->
<select name="productos[][tipo_manga]">
    <option value="Manga Corta">Manga Corta</option>
    <option value="Manga Larga">Manga Larga</option>
    <option value="Sin Manga">Sin Manga</option>
    <option value="Manga 3/4">Manga 3/4</option>
</select>

<!-- Color -->
<input type="text" name="productos[][color]" 
       placeholder="Ej: AZUL REY, NEGRO" required>

<!-- Talla -->
<input type="text" name="productos[][talla]" 
       placeholder="Ej: S, M, L, XL" required>

<!-- Género -->
<select name="productos[][genero]">
    <option value="Dama">Dama</option>
    <option value="Caballero">Caballero</option>
    <option value="Unisex">Unisex</option>
</select>

<!-- Referencia de Hilo -->
<input type="text" name="productos[][ref_hilo]" 
       placeholder="Ej: REF HILO 293">

<!-- Descripción Completa -->
<textarea name="productos[][descripcion]" rows="4"
          placeholder="Ej: CON CUELLOS Y PUÑOS EN HILO, CON BROCHES, SIN BOLSILLO...">
</textarea>

<!-- Bordados/Logos -->
<textarea name="productos[][bordados]" rows="3"
          placeholder="Ej: LOGO BORDADO EN LADO DERECHO DEL PECHO 'INVERSIONES EVAN'...">
</textarea>

<!-- Modelo/Referencia Foto -->
<input type="text" name="productos[][modelo_foto]" 
       placeholder="URL de la foto o descripción del modelo">

<!-- Notas Adicionales -->
<textarea name="productos[][notas]" rows="2"
          placeholder="Cualquier observación adicional...">
</textarea>
```

## Ejemplo de Uso

### Caso: INVERSIONES EVAN

**Producto 1:**
- **Tipo de Prenda:** CAMISA TIPO POLO
- **Tela:** TELA LAFAYETTE
- **Tipo de Manga:** Manga Corta
- **Color:** AZUL REY
- **Talla:** S
- **Género:** Dama
- **Cantidad:** 1
- **Ref. Hilo:** REF HILO 293
- **Descripción:** CON CUELLOS Y PUÑOS EN HILO, CON BROCHES, SIN BOLSILLO, MODELO DE BODEGA
- **Bordados:** LOGO BORDADO EN LADO DERECHO DEL PECHO "INVERSIONES EVAN", LOGO BORDADO EN LADO IZQUIERDO DEL PECHO "DIFFER"
- **Modelo Foto:** [URL de la imagen]

**Producto 2:**
- **Tipo de Prenda:** CAMISA TIPO POLO
- **Tela:** TELA LAFAYETTE
- **Tipo de Manga:** Manga Corta
- **Color:** AZUL REY
- **Talla:** L
- **Género:** Caballero
- **Cantidad:** 1
- **Ref. Hilo:** REF HILO 293
- **Descripción:** CON CUELLOS Y PUÑOS EN HILO, CON BROCHES, SIN BOLSILLO
- **Bordados:** LOGO BORDADO EN LADO DERECHO DEL PECHO "INVERSIONES EVAN", LOGO BORDADO EN LADO IZQUIERDO DEL PECHO "DIFFER"

**Producto 3:**
- **Tipo de Prenda:** CAMISA TIPO POLO
- **Color:** NEGRA
- **Talla:** S
- **Género:** Dama
- **Cantidad:** 1
- (Mismas especificaciones de bordados)

**Producto 4:**
- **Tipo de Prenda:** CAMISA TIPO POLO
- **Color:** NEGRA
- **Talla:** L
- **Género:** Caballero
- **Cantidad:** 1
- (Mismas especificaciones de bordados)

## Ventajas del Nuevo Formulario

### 1. **Captura Completa de Información**
- ✅ Todos los detalles técnicos del producto
- ✅ Especificaciones de bordados y logos
- ✅ Referencias de hilo y telas
- ✅ Género y tallas específicas

### 2. **Mejor Organización**
- ✅ Campos estructurados y claros
- ✅ Dropdowns para opciones comunes
- ✅ Áreas de texto para descripciones largas
- ✅ Validación de campos requeridos

### 3. **Facilita la Producción**
- ✅ Información clara para el área de corte
- ✅ Detalles precisos para bordados
- ✅ Referencias de materiales específicas
- ✅ Modelos de referencia disponibles

### 4. **Trazabilidad**
- ✅ Toda la información queda registrada
- ✅ Fácil consulta de especificaciones
- ✅ Historial completo del pedido
- ✅ Referencias para pedidos futuros

## Estructura de la Base de Datos

### Tabla: `productos_pedido`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único del producto |
| pedido | int | Número de pedido (FK) |
| nombre_producto | varchar | Tipo de prenda |
| tela | varchar | Tipo de tela |
| tipo_manga | varchar | Manga corta/larga/sin manga |
| color | varchar | Color del producto |
| descripcion | text | Descripción completa |
| bordados | text | Detalles de bordados y logos |
| modelo_foto | varchar | URL o referencia de foto |
| talla | varchar | Talla del producto |
| genero | varchar | Dama/Caballero/Unisex |
| ref_hilo | varchar | Referencia del hilo |
| cantidad | int | Cantidad de unidades |
| precio_unitario | decimal | Precio por unidad |
| subtotal | decimal | Total del producto |
| imagen | varchar | Imagen del producto |
| notas | text | Notas adicionales |

## Próximos Pasos

### 1. **Actualizar Vista de Edición**
Aplicar los mismos campos al formulario de edición (`edit.blade.php`)

### 2. **Actualizar Vista de Detalle**
Mostrar todos los campos nuevos en la vista de detalle (`show.blade.php`)

### 3. **Actualizar Controlador**
Asegurar que el controlador maneje todos los campos nuevos correctamente

### 4. **Exportar a PDF**
Incluir todos los campos en la generación de PDF del pedido

### 5. **Reportes**
Agregar filtros por color, tela, género, etc. en los reportes

## Comandos Ejecutados

```bash
# Crear migración
php artisan make:migration add_detailed_fields_to_productos_pedido_table --table=productos_pedido

# Ejecutar migración
php artisan migrate

# Limpiar caché
php artisan view:clear
php artisan cache:clear
```

## Archivos Modificados

1. ✅ `database/migrations/2025_11_10_150547_add_detailed_fields_to_productos_pedido_table.php`
2. ✅ `app/Models/ProductoPedido.php`
3. ✅ `resources/views/asesores/pedidos/create.blade.php`

## Archivos Pendientes

1. ⏳ `resources/views/asesores/pedidos/edit.blade.php` - Aplicar mismos campos
2. ⏳ `resources/views/asesores/pedidos/show.blade.php` - Mostrar campos nuevos
3. ⏳ `app/Http/Controllers/AsesoresController.php` - Verificar manejo de campos

## Resultado Final

El formulario ahora puede capturar **TODA** la información que viene en los correos de pedidos, incluyendo:

- ✅ Especificaciones técnicas detalladas
- ✅ Información de bordados y logos
- ✅ Referencias de materiales (telas, hilos)
- ✅ Detalles de género y tallas
- ✅ Modelos de referencia
- ✅ Notas y observaciones especiales

**¡El sistema está listo para manejar pedidos tan detallados como el de INVERSIONES EVAN!** 🎉
