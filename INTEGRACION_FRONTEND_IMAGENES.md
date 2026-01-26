# 📱 Guía de Integración Frontend - Sistema de Imágenes

## 🎯 Cambios Necesarios en Frontend

El flujo es **casi idéntico** al anterior, con **un pequeño cambio**: almacenar el `temp_uuid` devuelto por el servidor.

---

## 1️⃣ Endpoint de Upload (SIN CAMBIOS de comportamiento)

### Antes (igual funcionaba):
```javascript
POST /asesores/pedidos-editable/subir-imagenes-prenda
```

### Ahora (igual pero con respuesta mejorada):
```json
{
    "success": true,
    "message": "3 imagen(es) subida(s) temporalmente",
    "imagenes": [
        {
            "ruta_webp": "prendas/temp/uuid-123/webp/prenda_0_20260125_xyz.webp",
            "ruta_original": "prendas/temp/uuid-123/original/prenda_0_20260125_xyz.jpg",
            "url": "/storage/prendas/temp/uuid-123/webp/prenda_0_20260125_xyz.webp",
            "thumbnail": "/storage/prendas/temp/uuid-123/thumbnails/prenda_0_20260125_xyz.webp"
        }
    ],
    "temp_uuid": "uuid-123"  // ← NUEVO
}
```

---

## 2️⃣ Almacenar temp_uuid en SessionStorage

```javascript
// Cuando el usuario sube imágenes de prendas
async function subirImagenesPrenda(archivos) {
    const formData = new FormData();
    archivos.forEach(archivo => {
        formData.append('imagenes', archivo);
    });

    const response = await fetch('/asesores/pedidos-editable/subir-imagenes-prenda', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();

    if (data.success) {
        // ✅ GUARDAR EL TEMP_UUID
        sessionStorage.setItem('temp_uuid_prendas', data.temp_uuid);
        
        // Mostrar URLs para preview
        mostrarPreviewImagenes(data.imagenes);
        
        // Guardar las rutas en el formulario
        formulario.imagenes_prendas = data.imagenes.map(img => img.ruta_webp);
    }
}
```

---

## 3️⃣ Incluir temp_uuid en el Formulario de Creación

### Estructura del JSON a enviar:

```json
{
    "numero_pedido": "PED-2026-001",
    "cliente": "Acme Corp",
    "items": [
        {
            "nombre_prenda": "Camisa Polo",
            "cantidad_talla": { "DAMA": { "S": 10, "M": 20 } },
            "imagenes": [
                "prendas/temp/uuid-123/webp/prenda_0_....webp",
                "prendas/temp/uuid-123/webp/prenda_1_....webp"
            ],
            "telas": [
                {
                    "tela_id": 5,
                    "color_id": 12,
                    "imagenes": [
                        "telas/temp/uuid-456/webp/tela_0_....webp"
                    ]
                }
            ]
        }
    ]
}
```

### En JavaScript:

```javascript
async function crearPedido() {
    const datosFormulario = construirDatosFormulario();
    
    // ✅ Las imágenes ya están en el array del item
    // ✅ Si vinieron de upload temporal, están como:
    //    "prendas/temp/{uuid}/webp/..."
    // ✅ El backend se encargará de relocalizarlas

    const response = await fetch('/asesores/pedidos-editable/crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datosFormulario)
    });

    const resultado = response.json();
    
    if (resultado.success) {
        console.log('✅ Pedido creado con ID:', resultado.pedido_id);
        // Limpiar sessionStorage
        sessionStorage.removeItem('temp_uuid_prendas');
    }
}
```

---

## 4️⃣ Ejemplo Completo de Formulario

```html
<!-- Formulario de Crear Pedido -->
<form id="formCrearPedido" @submit.prevent="crearPedido">
    
    <!-- Datos básicos -->
    <input v-model="pedido.numero_pedido" placeholder="Número de pedido" />
    <input v-model="pedido.cliente" placeholder="Cliente" />
    
    <!-- Items (Prendas) -->
    <div v-for="(item, index) in pedido.items" :key="index">
        <h3>Prenda {{ index + 1 }}</h3>
        
        <input v-model="item.nombre_prenda" placeholder="Nombre de prenda" />
        
        <!-- ✅ IMÁGENES DE PRENDA -->
        <div>
            <label>Imágenes de Prenda</label>
            <input 
                type="file" 
                multiple 
                @change="(e) => subirImagenesPrenda(e.target.files, index)"
                accept="image/*"
            />
            
            <!-- Preview de imágenes -->
            <div v-if="item.imagenes && item.imagenes.length" class="preview-container">
                <div v-for="(img, imgIdx) in item.imagenes" :key="imgIdx" class="preview">
                    <!-- ✅ USAR ruta_webp para mostrar -->
                    <img 
                        :src="`/storage/${img}`"
                        alt="Preview prenda"
                        style="max-width: 100px; max-height: 100px"
                    />
                    <p>{{ img.substring(img.lastIndexOf('/') + 1) }}</p>
                </div>
            </div>
        </div>

        <!-- Telas -->
        <div v-for="(tela, telaIdx) in item.telas" :key="telaIdx">
            <h4>Tela {{ telaIdx + 1 }}</h4>
            
            <select v-model="tela.tela_id">
                <option value="">Seleccionar tela</option>
                <option v-for="t in telas" :key="t.id" :value="t.id">{{ t.nombre }}</option>
            </select>

            <!-- ✅ IMÁGENES DE TELA -->
            <div>
                <label>Imágenes de Tela</label>
                <input 
                    type="file" 
                    multiple 
                    @change="(e) => subirImagenesTela(e.target.files, index, telaIdx)"
                    accept="image/*"
                />
                
                <!-- Preview -->
                <div v-if="tela.imagenes && tela.imagenes.length" class="preview-container">
                    <div v-for="(img, imgIdx) in tela.imagenes" :key="imgIdx" class="preview">
                        <img 
                            :src="`/storage/${img}`"
                            alt="Preview tela"
                            style="max-width: 100px; max-height: 100px"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Botón para crear -->
    <button type="submit" :disabled="cargando">
        {{ cargando ? 'Creando...' : 'Crear Pedido' }}
    </button>
</form>
```

### Script Vue.js:

```javascript
export default {
    data() {
        return {
            pedido: {
                numero_pedido: '',
                cliente: '',
                items: [
                    {
                        nombre_prenda: '',
                        imagenes: [],
                        telas: [
                            {
                                tela_id: null,
                                color_id: null,
                                imagenes: []
                            }
                        ]
                    }
                ]
            },
            telas: [],
            cargando: false
        }
    },
    
    methods: {
        // Upload de imágenes de prendas
        async subirImagenesPrenda(archivos, itemIdx) {
            const formData = new FormData();
            
            for (let archivo of archivos) {
                formData.append('imagenes', archivo);
            }

            try {
                const response = await fetch('/asesores/pedidos-editable/subir-imagenes-prenda', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // ✅ Guardar UUID para este lote
                    sessionStorage.setItem(`temp_uuid_prendas_${itemIdx}`, data.temp_uuid);
                    
                    // ✅ Guardar rutas en el formulario
                    // El backend espera: ['prendas/temp/{uuid}/webp/...', ...]
                    this.pedido.items[itemIdx].imagenes = data.imagenes.map(img => img.ruta_webp);
                    
                    this.$toast.success(data.message);
                }
            } catch (error) {
                this.$toast.error('Error al subir imágenes: ' + error.message);
            }
        },

        // Upload de imágenes de telas
        async subirImagenesTela(archivos, itemIdx, telaIdx) {
            const formData = new FormData();
            
            for (let archivo of archivos) {
                formData.append('imagenes', archivo);
            }

            try {
                const response = await fetch('/asesores/pedidos-editable/subir-imagenes-prenda', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // ✅ Guardar UUID
                    sessionStorage.setItem(`temp_uuid_telas_${itemIdx}_${telaIdx}`, data.temp_uuid);
                    
                    // ✅ Guardar rutas
                    this.pedido.items[itemIdx].telas[telaIdx].imagenes = 
                        data.imagenes.map(img => img.ruta_webp);
                    
                    this.$toast.success(data.message);
                }
            } catch (error) {
                this.$toast.error('Error al subir imágenes: ' + error.message);
            }
        },

        // Crear pedido
        async crearPedido() {
            this.cargando = true;

            try {
                const response = await fetch('/asesores/pedidos-editable/crear', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.pedido)
                });

                const resultado = await response.json();

                if (resultado.success) {
                    this.$toast.success('✅ Pedido creado exitosamente');
                    
                    // ✅ Limpiar sessionStorage
                    sessionStorage.clear();
                    
                    // Redirigir
                    window.location.href = `/pedidos/${resultado.pedido_id}`;
                } else {
                    this.$toast.error(resultado.message);
                }
            } catch (error) {
                this.$toast.error('Error: ' + error.message);
            } finally {
                this.cargando = false;
            }
        }
    }
}
```

---

## 5️⃣ Cambios Mínimos (CHECKLIST)

### ✅ Frontend NO Necesita Cambios Si:
- [ ] Ya usa el endpoint `/asesores/pedidos-editable/subir-imagenes-prenda`
- [ ] Ya incluye las rutas de imágenes en `item.imagenes = [...]`
- [ ] Ya envía el JSON correctamente formado

### ⚠️ Frontend Necesita Cambios Si:
- [ ] Construye manualmente rutas como `'prendas/UUID/prenda.webp'` → Ahora viene del servidor
- [ ] Usa un UUID distinto que el servidor genera → Ahora frontend recibe el correcto en respuesta
- [ ] No almacena las rutas en el item → Debe hacerlo para enviar al crear pedido

---

## 6️⃣ Flujo Visual Resumido

```
┌─────────────────────────────────────────────────────────────┐
│ USUARIO SELECCIONA IMÁGENES                                 │
└─────────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│ POST /subir-imagenes-prenda                                 │
│ {                                                            │
│   imagenes: [File, File, File],                             │
│   temp_uuid: "uuid-123" (OPCIONAL - si lo tiene)            │
│ }                                                            │
└─────────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│ BACKEND RESPONDE                                            │
│ {                                                            │
│   temp_uuid: "uuid-123",    ← GUARDAR ESTO                  │
│   imagenes: [                                                │
│     {                                                        │
│       ruta_webp: "prendas/temp/uuid-123/webp/..."           │
│     }                                                        │
│   ]                                                          │
│ }                                                            │
└─────────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│ FRONTEND GUARDA EN FORMULARIO                               │
│ item.imagenes = [                                            │
│   "prendas/temp/uuid-123/webp/prenda_0_....webp",           │
│   "prendas/temp/uuid-123/webp/prenda_1_....webp"            │
│ ]                                                            │
└─────────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│ USUARIO HACE CLIC EN "CREAR PEDIDO"                         │
│ POST /crear                                                 │
│ {                                                            │
│   items: [{                                                 │
│     imagenes: ["prendas/temp/uuid-123/webp/..."]            │
│   }]                                                         │
│ }                                                            │
└─────────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│ BACKEND                                                      │
│ 1. Crea pedido → id = 42                                    │
│ 2. ImagenRelocalizadorService relocaliza:                   │
│    prendas/temp/uuid-123/ → pedidos/42/prendas/            │
│ 3. Guarda rutas finales en BD                               │
│ 4. Retorna {pedido_id: 42}                                  │
└─────────────────────────────────────────────────────────────┘
                         ↓
✅ LISTO - Imágenes en: storage/app/public/pedidos/42/prendas/
```

---

## 7️⃣ Preguntas Frecuentes

### P: ¿Qué pasa si el usuario sube imágenes pero no crea el pedido?
**R:** Las imágenes quedan en `/temp/{uuid}/` indefinidamente. Se pueden limpiar manualmente o crear un cron job.

### P: ¿Necesito cambiar el HTML?
**R:** NO. Solo asegúrate que almacenes las rutas que el servidor devuelve.

### P: ¿Funciona con Vue/React/Vanilla?
**R:** SÍ. Es solo HTTP requests. Funciona con cualquier framework.

### P: ¿Se puede usar el temp_uuid?
**R:** SÍ, es devuelto por si lo necesitas para debugging. No es obligatorio usarlo.

### P: ¿Las imágenes antiguas se pueden perder?
**R:** NO. El sistema es **100% backward compatible**. Las imágenes se reloca lizan automáticamente.

---

## 🔗 Referencia de APIs

### Endpoint 1: Upload Temporal
```
POST /asesores/pedidos-editable/subir-imagenes-prenda
Content-Type: multipart/form-data

Parámetros:
- imagenes: File[] (requerido)
- temp_uuid: string (opcional, para agrupar uploads)

Response:
{
  success: bool,
  message: string,
  imagenes: [{ ruta_webp, ruta_original, url, thumbnail }],
  temp_uuid: string
}
```

### Endpoint 2: Crear Pedido
```
POST /asesores/pedidos-editable/crear
Content-Type: application/json

Body:
{
  numero_pedido: "PED-2026-001",
  items: [{
    imagenes: ["prendas/temp/{uuid}/webp/...", ...]
  }]
}

Response:
{
  success: bool,
  pedido_id: int,
  message: string
}
```

---

## ✅ Validación de Implementación

Para verificar que todo funciona:

1. **Abrir formulario de crear pedido**
2. **Seleccionar imagen** → Verificar que response contiene `temp_uuid`
3. **Crear pedido** → Verificar que se crea exitosamente
4. **Verificar carpeta:**
   ```bash
   ls storage/app/public/pedidos/1/prendas/
   # Debe mostrar archivos WebP
   ```
5. **Abrir "Ver Pedido"** → Imágenes deben verse normalmente

---

## 🚀 ¡Listo para implementar!

No hay cambios complejos. Solo asegúrate de:
- ✅ Guardar `temp_uuid` si lo necesitas
- ✅ Incluir las rutas de imágenes en el JSON
- ✅ Enviar el JSON al crear pedido

**El backend hace todo lo demás automáticamente.** 🎉

