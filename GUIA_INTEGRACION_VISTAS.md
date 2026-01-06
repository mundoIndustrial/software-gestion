# 📱 GUÍA DE INTEGRACIÓN EN VISTAS

## Cómo integrar la refactorización DDD en tu formulario

---

## 1️⃣ PASO 1: Agregar script JavaScript

En `resources/views/cotizaciones/bordado/create.blade.php`, antes del cierre de `</div>`:

```html
<!-- Scripts de logo cotización técnicas -->
<script src="{{ asset('js/logo-cotizacion-tecnicas.js') }}"></script>
```

---

## 2️⃣ PASO 2: Crear el HTML del Modal

Agregar este HTML en la vista (antes de las técnicas actuales):

```html
<!-- Modal para Agregar Técnica -->
<div class="modal fade" id="modalAgregarTecnica" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title">
                    Agregar Técnica:
                    <span id="tecnicaSeleccionada" style="color: #0066cc;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tecnicaSeleccionadaId">
                
                <!-- Prendas dinámicas -->
                <div id="prendasModal"></div>
                
                <!-- Botón para agregar más prendas -->
                <button type="button" class="btn btn-primary btn-sm mb-3" 
                        id="btnAgregarPrenda">
                    <i class="fas fa-plus"></i> Agregar Prenda
                </button>
                
                <!-- Observaciones -->
                <div class="mb-3">
                    <label class="form-label">Observaciones de la técnica</label>
                    <textarea class="form-control" id="observacionesTecnica" 
                              rows="3" placeholder="Detalles especiales..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" 
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" 
                        onclick="LogoCotizacion.guardarTecnica()">
                    <i class="fas fa-save"></i> Guardar Técnica
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sección para mostrar técnicas agregadas -->
<div class="mt-4">
    <h4>✨ Técnicas Agregadas</h4>
    <div id="tecnicasAgregadas">
        <p class="text-muted">No hay técnicas agregadas aún</p>
    </div>
</div>
```

---

## 3️⃣ PASO 3: Reemplazar selector de técnicas antiguo

**ANTES (eliminar):**
```html
<!-- TÉCNICAS -->
<div class="form-section">
    <div class="tecnicas-box">
        <div class="tecnicas-header">
            <label>Técnicas disponibles</label>
            <button type="button" class="btn-add" onclick="agregarTecnica()">+</button>
        </div>
        <select id="selector_tecnicas" class="input-large">
            <option value="">-- SELECCIONA UNA TÉCNICA --</option>
            <option value="BORDADO">BORDADO</option>
            <option value="DTF">DTF</option>
            <option value="ESTAMPADO">ESTAMPADO</option>
            <option value="SUBLIMADO">SUBLIMADO</option>
        </select>
        <div class="tecnicas-seleccionadas" id="tecnicas_seleccionadas"></div>
        <label>Observaciones</label>
        <textarea id="observaciones_tecnicas"></textarea>
    </div>
</div>
```

**DESPUÉS (nuevo):**
```html
<!-- TÉCNICAS (NUEVA ARQUITECTURA DDD) -->
<div class="form-section">
    <div class="form-group">
        <label style="font-weight: 700; color: #1e40af;">Técnicas de Logo/Bordado</label>
        <p class="text-muted" style="font-size: 0.85rem;">
            Selecciona una técnica y agrega las prendas que llevarán ese tipo de aplicación
        </p>
        
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <select id="selector_tecnicas" class="form-control" style="flex: 1;">
                <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                <!-- Se cargan dinámicamente desde API -->
            </select>
            
            <button type="button" class="btn btn-primary" 
                    onclick="LogoCotizacion.abrirModalAgregarTecnica()">
                <i class="fas fa-plus"></i> Agregar Técnica
            </button>
        </div>
    </div>
</div>
```

---

## 4️⃣ PASO 4: Agregar input oculto para ID

En el formulario principal, agregar:

```html
<!-- IMPORTANTE: ID de la cotización para API -->
@if($cotizacion)
    <input type="hidden" id="logoCotizacionId" value="{{ $cotizacion->logoCotizacion->id ?? '' }}">
@endif
```

---

## 5️⃣ PASO 5: Actualizar JavaScript existente

Modificar la función `form.addEventListener('submit', ...)` para incluir datos de técnicas:

```javascript
// En el submit del formulario, ANTES de enviar:
async function prepararDatosFormulario() {
    // Las técnicas ya están guardadas en BD via API
    // Solo necesitas recopilar datos del formulario principal
    
    const datos = {
        // ... otros datos del formulario
        logo_cotizacion_id: document.getElementById('logoCotizacionId')?.value,
        // Las técnicas se guardan automáticamente via API
    };
    
    return datos;
}
```

---

## 6️⃣ PASO 6: Estilos CSS (Opcional)

Agregar en `<style>` si necesitas personalizar:

```css
/* Tarjetas de técnicas */
.tecnica-card {
    border-left: 5px solid #0066cc;
    transition: all 0.3s ease;
}

.tecnica-card:hover {
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.2);
}

/* Ubicaciones checkboxes */
.ubicaciones-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

/* Filas de prendas en modal */
.prenda-row {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.prenda-row:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}
```

---

## 7️⃣ PASO 7: Estructura final de la vista

```
create.blade.php
├── Encabezado del formulario
├── Selección de cliente
├── NUEVA SECCIÓN: Técnicas (con modal)
│   ├── Select de técnicas
│   ├── Botón "Agregar Técnica"
│   ├── Modal para prendas
│   └── Sección de técnicas agregadas
├── Ubicación (ANTIGUO - eliminar si lo reemplazas)
├── Observaciones (ANTIGUO - eliminar si lo reemplazas)
└── Imágenes + botones Guardar/Enviar
```

---

## 🧪 TESTING EN NAVEGADOR

### 1. Abrir consola (F12)

```javascript
// Ver tipos cargados
console.log(tiposDisponibles);

// Ver técnicas agregadas
console.log(tecnicasAgregadas);

// Cargar técnicas manualmente
LogoCotizacion.cargarTecnicasAgregadas();
```

### 2. Probar flujo completo

1. Seleccionar técnica (Bordado)
2. Clic en "Agregar Técnica"
3. Completar datos de prendas
4. Guardar
5. Verificar que aparece en "Técnicas Agregadas"
6. Seleccionar otra técnica (Estampado)
7. Agregar
8. Ver ambas técnicas en la sección

---

## 🔗 FLUJO DE DATOS

```
Vista (HTML)
  ↓
JavaScript (logo-cotizacion-tecnicas.js)
  ↓
API Endpoint: POST /api/logo-cotizacion-tecnicas/agregar
  ↓
Form Request: AgregarTecnicaRequest
  ↓
Controller: LogoCotizacionTecnicaController
  ↓
Application Service: AgregarTecnicaLogoCotizacionService
  ↓
Domain Entities: TecnicaLogoCotizacion, PrendaTecnica
  ↓
Repository: LogoCotizacionTecnicaRepository
  ↓
Database (3 tablas)
```

---

## ✅ CHECKLIST DE INTEGRACIÓN

- [ ] Agregar script `logo-cotizacion-tecnicas.js`
- [ ] Crear HTML del modal
- [ ] Reemplazar selector de técnicas antiguo
- [ ] Agregar input oculto con ID de cotización
- [ ] Probar en navegador (consola)
- [ ] Verificar que se crean registros en BD
- [ ] Probar eliminar técnica
- [ ] Probar agregar múltiples técnicas
- [ ] Probar actualizar observaciones
- [ ] Validar que imágenes sigan funcionando
- [ ] Validar que guardado de borrador funcione

---

## 🐛 TROUBLESHOOTING

### Problema: "logoCotizacionId is null"
**Solución:** Asegúrate que está correctamente en el HTML:
```html
<input type="hidden" id="logoCotizacionId" value="{{ $cotizacion->logoCotizacion->id }}">
```

### Problema: "CSRF token mismatch"
**Solución:** Verifica que tengas el meta tag en <head>:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### Problema: "Technique Not Found"
**Solución:** Verifica que el seeder se ejecutó:
```bash
php artisan db:seed --class=TipoLogoCotizacionSeeder
```

### Problema: Modal no abre
**Solución:** Asegúrate de tener Bootstrap 5 en la vista:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
```

---

## 📚 REFERENCIAS

- Documentación API: `GUIA_USO_LOGO_COTIZACIONES_DDD.md`
- Arquitectura DDD: `REFACTORIZACION_LOGO_COTIZACIONES_DDD.md`
- Resumen ejecutivo: `RESUMEN_EJECUTIVO_LOGO_DDD.md`

---

## 🎯 PRÓXIMO PASO

Una vez integrado en la vista, puedes:

1. **Tests automatizados** en `tests/Feature/LogoCotizacionTecnicaTest.php`
2. **Reporting** de técnicas por tipo
3. **Cálculo de precios** por técnica
4. **Exportar PDF** con técnicas separadas

¡Listo para implementar! 🚀
