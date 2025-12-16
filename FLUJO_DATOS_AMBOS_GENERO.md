# Flujo de Datos - Género "AMBOS" y Variaciones

## DIAGRAMA DEL FLUJO COMPLETO

```
┌─────────────────────────────────────────────────────────────────┐
│                    USUARIO EN NAVEGADOR                         │
│                  (create-cotizacion.blade.php)                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
                    ┌─────────────────────┐
                    │  Selecciona Género  │
                    │   "Ambos"           │
                    └─────────────────────┘
                              ↓
    ┌─────────────────────────────────────────────────────────┐
    │ tallas.js → actualizarGeneroSeleccionado(select)        │
    │ • Busca .genero-id-hidden input                         │
    │ • Mapea "ambos" → genero_id = "4"                       │
    │ • Asigna valor al input: genero_id.value = "4"          │
    │ Resultado: <input class="genero-id-hidden" value="4">   │
    └─────────────────────────────────────────────────────────┘
                              ↓
    ┌─────────────────────────────────────────────────────────┐
    │ tallas.js → actualizarBotonesPorGenero(select)          │
    │ • Detecta género = "ambos"                              │
    │ • Crea 2 tabs: 👩 DAMA | 👨 CABALLERO                  │
    │ • Tab DAMA: botones con tallas [6,8,10,12,14,...]       │
    │ • Tab CABALLERO: botones [28,30,32,34,36,...]           │
    │ • Usuario selecciona: 10, 14, 18 (DAMA)                 │
    │              y: 32, 38, 44 (CABALLERO)                  │
    └─────────────────────────────────────────────────────────┘
                              ↓
    ┌─────────────────────────────────────────────────────────┐
    │ tallas.js → agregarTallasSeleccionadas()                │
    │ • Recoge tallas de botones .activo                      │
    │ • Array: ["10", "14", "18", "32", "38", "44"]          │
    │ • Guarda en input hidden: tallas = "10, 14, 18, ..."    │
    └─────────────────────────────────────────────────────────┘
                              ↓
                  ┌─────────────────────┐
                  │ CLIC GUARDAR ✓      │
                  └─────────────────────┘
                              ↓
    ┌─────────────────────────────────────────────────────────┐
    │ guardado.js → guardarCotizacion()                       │
    │ • Llama a recopilarDatos()                              │
    └─────────────────────────────────────────────────────────┘
                              ↓
    ┌─────────────────────────────────────────────────────────┐
    │ cotizaciones.js → recopilarDatos()                      │
    │                                                          │
    │ Para cada .producto-card:                              │
    │  1. Busca .genero-id-hidden → valor "4"               │
    │  2. Captura variantes.genero_id = "4" ✅               │
    │  3. Captura tallas = ["10","14","18","32",...]        │
    │  4. Captura fotos, telas, color, etc.                 │
    │                                                          │
    │ Resultado objeto PRODUCTO:                            │
    │ {                                                       │
    │   nombre_producto: "Camiseta Deportiva",              │
    │   tallas: ["10", "14", "18", "32", "38", "44"],       │
    │   fotos: [File, File, File],                          │
    │   variantes: {                                        │
    │     genero_id: "4",          ← ⭐ CLAVE               │
    │     color: "Rojo",                                    │
    │     tela: "Algodón",                                  │
    │     tipo_manga_id: null,                              │
    │     ...                                               │
    │   }                                                    │
    │ }                                                       │
    └─────────────────────────────────────────────────────────┘
                              ↓
    ┌─────────────────────────────────────────────────────────┐
    │ guardado.js → Construye FormData                        │
    │                                                          │
    │ formData.append('prendas[0][variantes][genero_id]', '4')│
    │ formData.append('prendas[0][tallas]', JSON.stringify([...]))
    │ formData.append('prendas[0][fotos][]', File)           │
    │ ...                                                      │
    │                                                          │
    │ → POST /api/cotizaciones (multipart/form-data)        │
    └─────────────────────────────────────────────────────────┘
                              ↓
              ┌──────────────────────────────┐
              │   SERVIDOR LARAVEL           │
              │   (Backend Processing)       │
              └──────────────────────────────┘
                              ↓
    ┌─────────────────────────────────────────────────────────┐
    │ CotizacionPrendaService                                │
    │                                                          │
    │ 1. Recibe genero_id = "4" (string, se convierte a int)  │
    │ 2. Busca género en tabla `generos`:                    │
    │    SELECT * FROM generos WHERE id = 4                  │
    │    → Resultado: id=4, nombre="Ambos"                   │
    │                                                          │
    │ 3. Crea registro en prenda_variantes_cot:              │
    │    INSERT INTO prenda_variantes_cot (                  │
    │      prenda_id, genero_id, color, tela, ...           │
    │    ) VALUES (1, 4, 'Rojo', 'Algodón', ...)             │
    │    → Genera VARIANTE ID = 123                         │
    │                                                          │
    │ 4. Crea registros en prenda_tallas_cot:               │
    │    INSERT INTO prenda_tallas_cot (                     │
    │      prenda_variante_cot_id, talla                     │
    │    ) VALUES                                             │
    │    (123, '10'),                                        │
    │    (123, '14'),                                        │
    │    (123, '18'),                                        │
    │    (123, '32'),                                        │
    │    (123, '38'),                                        │
    │    (123, '44')                                         │
    │                                                          │
    │ 5. Crea registros en prenda_fotos:                    │
    │    INSERT INTO prenda_fotos (...) ...                  │
    │                                                          │
    │ Result: ✅ Cotización guardada correctamente            │
    └─────────────────────────────────────────────────────────┘
                              ↓
              ┌──────────────────────────────┐
              │   BASE DE DATOS              │
              │   (3 tablas afectadas)       │
              └──────────────────────────────┘

RESULTADO EN BD:
───────────────────────────────────────────────────────────────
Tabla: prenda_variantes_cot
┌────┬───────────┬───────┬─────────┬───────────┐
│ id │ genero_id │ color │  tela   │ prenda_id │
├────┼───────────┼───────┼─────────┼───────────┤
│123 │     4     │ Rojo  │ Algodón │     1     │
└────┴───────────┴───────┴─────────┴───────────┘

Tabla: prenda_tallas_cot
┌────┬──────────────────────┬───────┐
│ id │ prenda_variante_cot  │ talla │
├────┼──────────────────────┼───────┤
│ 1  │        123           │  10   │
│ 2  │        123           │  14   │
│ 3  │        123           │  18   │
│ 4  │        123           │  32   │
│ 5  │        123           │  38   │
│ 6  │        123           │  44   │
└────┴──────────────────────┴───────┘

Tabla: prenda_fotos
┌────┬──────────────┬────────────────────┐
│ id │ prenda_id    │  ruta_foto         │
├────┼──────────────┼────────────────────┤
│  1 │      1       │ /cotizaciones/... │
│  2 │      1       │ /cotizaciones/... │
│  3 │      1       │ /cotizaciones/... │
└────┴──────────────┴────────────────────┘

───────────────────────────────────────────────────────────────
```

## VISTA DE COTIZACIÓN (DESPUÉS DE GUARDAR)

```
┌─────────────────────────────────────────────────────────────┐
│          Ver Cotización #59                                 │
│                                                              │
│  CLIENTE: Acme Corp                                          │
│  TIPO: Cotización de Productos                              │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ PRENDA: Camiseta Deportiva                          │   │
│  │ CANTIDAD: 100                                        │   │
│  │ GÉNERO: Ambos (Dama y Caballero) ⭐ [MOSTRADO]     │   │
│  │                                                       │   │
│  │ ┌─────────────────────────────────────────────────┐ │   │
│  │ │ VARIACIONES:                                    │ │   │
│  │ ├─────────────────────────────────────────────────┤ │   │
│  │ │ Color: Rojo                                     │ │   │
│  │ │ Tela: Algodón                                   │ │   │
│  │ │ Género: Ambos (Dama y Caballero) ✅             │ │   │
│  │ │                                                  │ │   │
│  │ │ TALLAS SELECCIONADAS:                           │ │   │
│  │ │   👩 DAMA:       10, 14, 18                      │ │   │
│  │ │   👨 CABALLERO:  32, 38, 44                      │ │   │
│  │ │                                                  │ │   │
│  │ │ FOTOS: 3 archivos                               │ │   │
│  │ │   📸 [Ver imagen]                               │ │   │
│  │ │   📸 [Ver imagen]                               │ │   │
│  │ │   📸 [Ver imagen]                               │ │   │
│  │ └─────────────────────────────────────────────────┘ │   │
│  │                                                       │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  Estado: BORRADOR ✓                                          │
└─────────────────────────────────────────────────────────────┘
```

## VARIABLES GLOBALES EN JAVASCRIPT

```javascript
// En memoria del navegador durante la creación:

window.imagenesEnMemoria = {
  prendaConIndice: [
    { prendaIndex: 0, file: File, nombre: "photo1.jpg" },
    { prendaIndex: 0, file: File, nombre: "photo2.jpg" },
    { prendaIndex: 0, file: File, nombre: "photo3.jpg" }
  ],
  telaConIndice: [
    { prendaIndex: 0, file: File, nombre: "tela1.jpg" }
  ],
  logo: [ File, File ]
}

window.fotosSeleccionadas = {
  "producto_0": [File, File, File]
}

window.telasSeleccionadas = {
  "producto_0": [File]
}

// El objeto DATOS que se envía:
const datos = {
  cliente: "Acme Corp",
  productos: [
    {
      nombre_producto: "Camiseta Deportiva",
      descripcion: "Camiseta de algodón",
      cantidad: 100,
      tallas: ["10", "14", "18", "32", "38", "44"],
      fotos: [File, File, File],
      telas: [File],
      variantes: {
        genero_id: "4",        ← ⭐ AQUÍ ESTÁ EL CAMBIO
        color: "Rojo",
        tela: "Algodón",
        telas_multiples: [...]
      }
    }
  ],
  tecnicas: ["Impresión", "Bordado"],
  observaciones_generales: [],
  ubicaciones: [],
  especificaciones: {}
}
```

## CONSOLA DEL NAVEGADOR - MENSAJES ESPERADOS

Cuando ejecutas el flujo completo, deberías ver en la consola (F12):

```javascript
// 1. Al seleccionar género:
🔵 Género seleccionado: ambos
✅ genero_id actualizado a: 4

// 2. Al actualizar los botones de talla:
Actualizando botones para género: ambos
    - Creando 2 tabs: DAMA vs CABALLERO

// 3. Al hacer clic guardar:
🚀 INICIANDO GUARDADO DE COTIZACIÓN
📦 PROCESANDO PRENDA 1...

✅ genero_id capturado: 4
📝 RESUMEN VARIANTES CAPTURADAS: {
    '✅ Color': 'Rojo',
    '✅ Tela': 'Algodón',
    '👥 Género ID': '4',        ← ⭐ CONFIRMADO
    '🎽 Tipo Manga ID': '(NO CAPTURADO)',
    ...
}

✅ PRODUCTO AGREGADO: {
    nombre: "Camiseta Deportiva",
    tallas: 6,
    fotos: 3,
    telas: 1,
    variantes_keys: 8
}

🔄 Construyendo FormData...
📁 Preparando archivos para envío directo (sin Base64)...

✅ Foto de prenda (File) agregada a FormData [0][0]: photo1.jpg
✅ Foto de prenda (File) agregada a FormData [0][1]: photo2.jpg
✅ Foto de prenda (File) agregada a FormData [0][2]: photo3.jpg

✅ Tela (File) agregada a FormData [0][0]: tela1.jpg

🔵 Enviando FormData a: /api/cotizaciones
⏳ Esperando respuesta del servidor...

✅ Respuesta del servidor: {
    success: true,
    message: "Cotización guardada correctamente",
    cotizacion_id: 59
}
```

## CONCLUSIÓN

El flujo es:
1. ✅ Usuario selecciona "Ambos"
2. ✅ Se captura genero_id = "4" en el input hidden
3. ✅ Se envía al backend en el FormData
4. ✅ Backend crea prenda_variantes_cot con genero_id = 4
5. ✅ Backend crea tallas para todas las tallas seleccionadas
6. ✅ Al ver la cotización, se muestran las tallas (no dice "Sin variaciones")

**Si no ves las variaciones aún, verifica:**
- ¿Aparece "genero_id capturado: 4" en la consola?
- ¿La base de datos tiene genero_id = 4 en prenda_variantes_cot?
- ¿Las tallas están en prenda_tallas_cot?
