# ✅ CHECKLIST DE IMPLEMENTACIÓN: EPP + Imágenes FormData

## 📋 Resumen Ejecutivo

**Problema:** Las imágenes de EPP llegan como `imagenes: [{}]` porque se envían en JSON  
**Solución:** Usar FormData + separar JSON de archivos  
**Resultado:** Imágenes guardadas en `storage/pedido/{id}/epp/` y persistidas en BD

---

## 🚀 PASO A PASO

### FASE 1: Frontend - Preparar (1-2 horas)

- [ ] **Tarea 1.1:** Incluir `payload-normalizer-epp-correcto.js` en tu HTML
  ```html
  <script src="/js/modulos/crear-pedido/procesos/services/payload-normalizer-epp-correcto.js"></script>
  ```

- [ ] **Tarea 1.2:** En tu formulario de envío, ANTES de hacer fetch:
  ```javascript
  // Recolectar datos
  const pedidoData = {
      cliente: 'Juan',
      epps: [
          {
              epp_id: 5,
              imagenes: [File, File]  // ← Objects reales del navegador
          }
      ]
  };
  
  // Normalizar
  const { pedidoLimpio, archivos, formData } = 
      PayloadNormalizerEpp.normalizar(pedidoData);
  
  // Enviar FormData (NO JSON)
  const response = await fetch('/asesores/pedidos-editable/crear', {
      method: 'POST',
      headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
          // NO incluir Content-Type
      },
      body: formData  // ← FormData, NO JSON.stringify()
  });
  ```

- [ ] **Tarea 1.3:** Verificar con DevTools
  ```javascript
  // En Console:
  for (let [key, value] of formData.entries()) {
      console.log(key, value instanceof File ? '[File]' : value);
  }
  // Debe mostrar:
  // pedido: {...JSON...}
  // epps[0][imagenes][0]: [File]
  // epps[0][imagenes][1]: [File]
  ```

### FASE 2: Backend - Crear Funciones (1-2 horas)

- [ ] **Tarea 2.1:** Copiar todas las funciones de `CrearPedidoEditableControllerEppFunctions.php`
  ```php
  - crearCarpetasPedido()
  - guardarImagenEpp()
  - guardarImagenEppEnBd()
  - procesarImagenesEpp()
  - validarImagenesEpp()
  - contarImagenesEpp()
  ```
  Pegarlas en tu `CrearPedidoEditableController.php`

- [ ] **Tarea 2.2:** Actualizar método `crearPedido()` en controller
  ```php
  public function crearPedido(Request $request): JsonResponse
  {
      try {
          // 1. Validar entrada
          $pedidoJson = $request->input('pedido');
          $pedidoData = json_decode($pedidoJson, true);
          
          // 2. Crear pedido EN BD
          $pedidoCreado = $this->pedidoWebService->crearPedidoCompleto($pedidoData);
          $pedidoId = $pedidoCreado->id;
          
          // 3. ✅ CREAR CARPETAS
          $this->crearCarpetasPedido($pedidoId);
          
          // 4. ✅ PROCESAR IMÁGENES DE EPP
          $resultadoImagenes = $this->procesarImagenesEpp(
              $request,
              $pedidoId,
              $pedidoData['epps'] ?? []
          );
          
          // 5. Retornar resultado
          return response()->json([
              'success' => true,
              'pedido_id' => $pedidoId,
              'numero_pedido' => $pedidoCreado->numero_pedido,
              'imagenes_procesadas' => $resultadoImagenes
          ]);
      } catch (\Exception $e) {
          return response()->json([
              'success' => false,
              'message' => $e->getMessage()
          ], 500);
      }
  }
  ```

- [ ] **Tarea 2.3:** Verificar Models
  ```php
  // Que existan:
  - App\Models\PedidoEpp
  - App\Models\PedidoEppImagen
  ```

### FASE 3: Base de Datos (30 minutos)

- [ ] **Tarea 3.1:** Verificar tablas existen
  ```bash
  # En terminal Laravel:
  php artisan tinker
  > Schema::hasTable('pedido_epp')  // true
  > Schema::hasTable('pedido_epp_imagenes')  // true
  ```

- [ ] **Tarea 3.2:** Si no existen, crear migraciones:
  ```bash
  php artisan make:migration create_pedido_epp_tables
  ```
  
  Contenido:
  ```php
  Schema::create('pedido_epp', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('pedido_produccion_id');
      $table->unsignedBigInteger('epp_id');
      $table->integer('cantidad')->default(0);
      $table->text('observaciones')->nullable();
      $table->string('estado')->default('pendiente');
      $table->timestamps();
      
      $table->foreign('pedido_produccion_id')
            ->references('id')
            ->on('pedido_produccions');
  });
  
  Schema::create('pedido_epp_imagenes', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('pedido_epp_id');
      $table->string('ruta');
      $table->boolean('es_principal')->default(false);
      $table->integer('orden')->default(0);
      $table->timestamps();
      
      $table->foreign('pedido_epp_id')
            ->references('id')
            ->on('pedido_epp');
  });
  ```

  Luego:
  ```bash
  php artisan migrate
  ```

- [ ] **Tarea 3.3:** Crear Models si no existen
  
  `app/Models/PedidoEpp.php`:
  ```php
  <?php
  namespace App\Models;
  use Illuminate\Database\Eloquent\Model;
  class PedidoEpp extends Model {
      protected $table = 'pedido_epp';
      protected $guarded = [];
      public function imagenes() {
          return $this->hasMany(PedidoEppImagen::class);
      }
      public function pedido() {
          return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
      }
  }
  ```
  
  `app/Models/PedidoEppImagen.php`:
  ```php
  <?php
  namespace App\Models;
  use Illuminate\Database\Eloquent\Model;
  class PedidoEppImagen extends Model {
      protected $table = 'pedido_epp_imagenes';
      protected $guarded = [];
      public function pedidoEpp() {
          return $this->belongsTo(PedidoEpp::class);
      }
  }
  ```

### FASE 4: Testing (1-2 horas)

- [ ] **Tarea 4.1:** Abrir ejemplo HTML
  ```
  http://localhost:8000/html/ejemplo-envio-pedido-epp-correcto.html
  ```

- [ ] **Tarea 4.2:** Llenar datos:
  - Cliente: "Test"
  - Asesora: "María"
  - Forma Pago: "Contado"
  - Agregar EPP
  - EPP ID: 5
  - Cantidad: 10
  - Seleccionar 2-3 imágenes

- [ ] **Tarea 4.3:** Click en "Debug: Ver FormData"
  - Verificar que muestre archivos File reales
  - Verificar que pedido sea JSON válido

- [ ] **Tarea 4.4:** Click en "Crear Pedido"
  - Esperar respuesta

- [ ] **Tarea 4.5:** Verificar resultado en backend
  ```bash
  # Carpetas creadas
  ls -la storage/app/public/pedido/
  # Debe mostrar carpeta con número del pedido
  
  # Imágenes guardadas
  ls -la storage/app/public/pedido/2721/epp/
  # Debe mostrar archivos .jpg .png etc
  ```

- [ ] **Tarea 4.6:** Verificar BD
  ```bash
  php artisan tinker
  > PedidoProduccion::find(2721)->epps()->with('imagenes')->get()
  # Debe retornar imágenes con rutas
  ```

### FASE 5: Integración con tu código (2-3 horas)

- [ ] **Tarea 5.1:** Identificar dónde se envía el pedido en tu código
  - Buscar `fetch('/asesores/pedidos-editable/crear'`
  - Buscar `FormData` para ver si ya se usa en otros lados

- [ ] **Tarea 5.2:** Adaptar tu código para usar normalizar correctamente
  ```javascript
  // ❌ ANTES (incorrecto):
  const response = await fetch('/asesores/pedidos-editable/crear', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(pedidoData)  // ← JSON puro
  });
  
  // ✅ DESPUÉS (correcto):
  const { formData } = PayloadNormalizerEpp.normalizar(pedidoData);
  const response = await fetch('/asesores/pedidos-editable/crear', {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': csrfToken},
      body: formData  // ← FormData
  });
  ```

- [ ] **Tarea 5.3:** Actualizar controller para usar las nuevas funciones
  - Si ya existe lógica de procesamiento, reemplazar con llamadas a:
    - `crearCarpetasPedido()`
    - `procesarImagenesEpp()`

- [ ] **Tarea 5.4:** Testing con datos reales
  - Crear pedido con EPPs desde tu formulario
  - Verificar que las imágenes se guarden
  - Verificar que aparezcan en BD

---

## 🔍 DEBUGGING

### Si imagenes aún llegan como `[{}]`

**Problema:** Estás todavía usando `JSON.stringify()` en lugar de FormData

**Solución:**
```bash
# 1. Abrir DevTools → Network
# 2. Filtrar por el request de crear pedido
# 3. Ver "Request Headers" → Content-Type
#    ❌ Si dice: application/json → Error!
#    ✅ Si dice: multipart/form-data → Correcto!
```

### Si las carpetas no se crean

**Problema:** Permisos en storage o no se llama a `crearCarpetasPedido()`

**Solución:**
```bash
# Fijar permisos
chmod -R 755 storage/app/public

# Verificar que la función se ejecuta
# Buscar en laravel.log:
grep "Carpeta creada" storage/logs/laravel.log
```

### Si las imágenes no se guardan

**Problema:** 
1. `$request->file()` retorna null
2. Validación fallando

**Solución:**
```php
// Debug en CrearPedidoEditableController::crearPedido()

$archivos = $request->file("epps.0.imagenes");
dd($archivos);  // Ver qué retorna

$errores = $request->validate([
    'epps.*.imagenes.*' => 'required|image|mimes:jpeg,png,jpg|max:5120'
]);
// Si falla aquí, mensaje en $errores
```

---

## 📊 VERIFICACIÓN FINAL

Marcar TODO como ✅:

- [ ] **Frontend enviando FormData**
  ```javascript
  console.log(formData instanceof FormData);  // true
  ```

- [ ] **Backend recibe UploadedFile**
  ```php
  $archivos = $request->file('epps.0.imagenes');
  dd($archivos[0] instanceof UploadedFile);  // true
  ```

- [ ] **Carpetas existen**
  ```bash
  ls storage/app/public/pedido/{id}/epp/
  # Retorna archivos
  ```

- [ ] **BD tiene registros**
  ```sql
  SELECT * FROM pedido_epp_imagenes WHERE pedido_epp_id = ?;
  # Retorna filas
  ```

- [ ] **URLs públicas funcionan**
  ```
  http://localhost:8000/storage/pedido/2721/epp/imagen.jpg
  # Muestra la imagen
  ```

---

## 📞 SOPORTE RÁPIDO

### Error: "SQLSTATE[42S02]: Base table or view not found"

Causa: Tabla `pedido_epp_imagenes` no existe

Solución:
```bash
php artisan make:migration create_pedido_epp_imagenes_table
# (Ver schema arriba)
php artisan migrate
```

### Error: "File not found" al guardar

Causa: Directorio no existe o sin permisos

Solución:
```bash
mkdir -p storage/app/public/pedido
chmod -R 755 storage/app/public
```

### Error: "Undefined array key 'epps'"

Causa: FormData no tiene key 'epps', o JSON malformado

Solución:
```javascript
// Verificar:
PayloadNormalizerEpp.debugFormData(formData);
// Debe mostrar: epps[0][imagenes][0]: [File]
```

---

## 🎁 ARCHIVOS CREADOS PARA TI

Ya están en tu workspace:

1. **`SOLUCION_COMPLETA_EPP_IMAGENES.md`**
   - Guía teórica completa
   - Explicación del problema
   - Código PHP y JavaScript

2. **`payload-normalizer-epp-correcto.js`**
   - Función para extraer archivos
   - Función para construir FormData
   - Ready to use

3. **`CrearPedidoEditableControllerEppFunctions.php`**
   - Todas las funciones necesarias
   - Copy-paste a tu controller

4. **`ejemplo-envio-pedido-epp-correcto.html`**
   - Ejemplo HTML funcional
   - Accedible en `/html/ejemplo-envio-pedido-epp-correcto.html`

---

## 🎯 PRÓXIMOS PASOS RECOMENDADOS

1. **Semana 1:**
   - [ ] Fases 1-2 (Frontend + Backend prep)
   - [ ] Testing básico

2. **Semana 2:**
   - [ ] Fase 4 (Testing completo)
   - [ ] Integración con código existente

3. **Semana 3:**
   - [ ] Validación en producción
   - [ ] Documentar cambios

---

## ✅ PREGUNTAS FRECUENTES

**P: ¿Por qué no usar Base64?**
- Base64 aumenta tamaño 33%
- Más lento
- FormData es estándar en navegadores modernos
- Laravel lo soporta nativamente

**P: ¿Cómo manejar cancelaciones?**
```php
// Si usuario cancela después de crear pedido
// Las imágenes ya se guardaron en storage
// Opción: Usar soft deletes en PedidoEppImagen
// O: Borrar archivos al eliminar pedido
```

**P: ¿Máximo de imágenes por EPP?**
```php
// Controlar en frontend:
const MAX_IMAGENES = 5;
if (epp.imagenes.length >= MAX_IMAGENES) {
    alert('Máximo 5 imágenes');
    return;
}
```

**P: ¿Soporta otros tipos de archivo?**
```php
// Actualizar validación:
'epps.*.imagenes.*' => 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:5120'
```

---

## 🎉 RESUMEN FINAL

**Antes (❌ Roto):**
```
payload JSON {
  "epps": [{
    "imagenes": [{}]  // ← No se guardan
  }]
}
```

**Después (✅ Correcto):**
```
FormData {
  pedido: "{...JSON sin imágenes...}"
  epps[0][imagenes][0]: File  // ← Se guarda
  epps[0][imagenes][1]: File  // ← Se guarda
}
            ↓
        storage/pedido/2721/epp/imagen.jpg
            ↓
        pedido_epp_imagenes table
```

**Impacto:**
- ✅ Imágenes reales guardadas en filesystem
- ✅ Rutas persistidas en BD
- ✅ URLs públicas accesibles
- ✅ Sin pérdida de datos

---

Estás listo para implementar. ¡Éxito! 🚀
