# QUICK START - FASE 2 (Frontend Migration)

**Para:** Desarrolladores frontend
**Duración:** 4-6 horas de trabajo
**Complejidad:** Media

---

## ⚡ 30 SEGUNDO SUMMARY

El backend está listo. Ahora necesitas actualizar el frontend para llamar a `/api/pedidos` en lugar de `/asesores/pedidos`.

**Cambios típicos:**
```javascript
// ANTES
fetch('/asesores/pedidos', ...)

// DESPUÉS
fetch('/api/pedidos', ...)
```

**Documentación:** GUIA_MIGRACION_FRONTEND.md

---

## INICIO RÁPIDO

### PASO 1: Leer Guía (30 min)
```bash
# Lee esta guía completamente
GUIA_MIGRACION_FRONTEND.md

# Aprenderás:
 Cómo cambiar cada endpoint
 Manejo de errores
 Ejemplos de código
```

### PASO 2: Buscar Archivos (15 min)
```bash
# Usa PowerShell en Windows
Get-ChildItem -Path ".\resources" -Recurse -Include "*.js", "*.blade.php" | 
  Select-String "asesores/pedidos" | 
  Format-Table Path, LineNumber, Line

# O usa grep en WSL/Mac
grep -r "asesores/pedidos" resources/ --include="*.js" --include="*.blade.php"
```

### PASO 3: Actualizar Archivos (3-4 horas)
```bash
# Para cada archivo encontrado:
1. Abre en VS Code
2. Reemplaza "/asesores/pedidos" → "/api/pedidos"
3. Valida que funcione
4. Commit
```

### PASO 4: Testing (1-2 horas)
```bash
# Ejecuta suite de tests
php artisan test

# Testing manual:
1. Crear pedido
2. Confirmar pedido
3. Cancelar pedido
4. Obtener detalle
5. Listar pedidos

# Valida:
 No hay errores 410 Gone
 Respuestas JSON correctas
 Flujos completos funcionan
```

### PASO 5: Commit
```bash
git add .
git commit -m "Fase 2: Migración frontend a DDD endpoints"
git push
```

---

##  CHECKLIST RÁPIDO

### Antes de empezar:
- [ ] Leído GUIA_MIGRACION_FRONTEND.md
- [ ] Entiendo diferencia entre ANTES/DESPUÉS
- [ ] Tengo ambiente de desarrollo funcionando
- [ ] Tests pasando (php artisan test)

### Mientras actualizo:
- [ ] Busqué todos los archivos con /asesores/pedidos
- [ ] Para cada archivo: actualicé fetch/AJAX calls
- [ ] Validé que no hay referencias a CrearPedidoService
- [ ] Agregué manejo de errores (incluyendo 410)
- [ ] Testeé localmente antes de commit

### Antes de finalizar Fase 2:
- [ ] Ejecuté php artisan test (16/16 pasando)
- [ ] Hice testing manual de flujos completos
- [ ] Validé que no hay errores 410 Gone
- [ ] Revisé que respuestas JSON están correctas
- [ ] Commiteé cambios con mensaje claro

---

## 🔄 CAMBIOS TÍPICOS

### 1. Crear Pedido
```javascript
// ANTES
fetch('/asesores/pedidos', { method: 'POST', ... })

// DESPUÉS
fetch('/api/pedidos', { method: 'POST', ... })
```

### 2. Confirmar Pedido
```javascript
// ANTES
fetch(`/asesores/pedidos/confirm`, { 
  method: 'POST',
  body: JSON.stringify({ borrador_id, numero_pedido })
})

// DESPUÉS
fetch(`/api/pedidos/${pedidoId}/confirmar`, {
  method: 'PATCH',
  body: JSON.stringify({})
})
```

### 3. Cancelar Pedido
```javascript
// ANTES
fetch(`/asesores/pedidos/${id}/anular`, {
  method: 'POST',
  body: JSON.stringify({ novedad })
})

// DESPUÉS
fetch(`/api/pedidos/${id}/cancelar`, {
  method: 'DELETE',
  body: JSON.stringify({ razon })
})
```

### 4. Obtener Detalle
```javascript
// ANTES
fetch(`/asesores/pedidos/${id}/recibos-datos`)

// DESPUÉS
fetch(`/api/pedidos/${id}`)
```

**Más cambios en:** GUIA_MIGRACION_FRONTEND.md (8 operaciones documentadas)

---

## 🔍 ARCHIVOS A BUSCAR

```bash
# Típicamente encontrarás en:
 resources/views/asesores/pedidos/*.blade.php
 resources/js/pedidos/*.js
 resources/js/asesores/*.js
 public/js/pedidos.js (si existe)

# Usa búsqueda para encontrar:
grep -r "asesores/pedidos" resources/ --include="*.js" --include="*.blade.php"
grep -r "fetch.*asesores" resources/ --include="*.js"
grep -r "\.post.*asesores" resources/ --include="*.js"
```

---

##  VALIDACIÓN

### Código está correcto si:
```javascript
//  CORRECTO
fetch('/api/pedidos', { ... })
fetch(`/api/pedidos/${id}`, { ... })
fetch(`/api/pedidos/${id}/confirmar`, { ... })
await response.json() // Valida estructura DTO

// ❌ INCORRECTO
fetch('/asesores/pedidos', { ... }) // ← AÚN USA RUTA VIEJA
fetch('/api/asesores/pedidos', { ... }) // ← PATH INCORRECTO
response.data // ← DEBERÍA SER response.data, NO response
```

---

## ⚠️ ERRORES COMUNES

### Error 410 Gone
```
Response: { message: "Esta ruta está deprecada. Usa POST /api/pedidos" }
Status: 410

Significa: Aún estás usando /asesores/pedidos
Solución: Actualiza a /api/pedidos
```

### Error 401 Unauthorized
```
Significa: Falta token de autenticación
Solución: Agrega header Authorization: Bearer TOKEN
```

### Error 422 Unprocessable Entity
```
Significa: Estado inválido (ej: cancelar pedido completado)
Solución: Validar estado del pedido antes de operación
```

### Response structure incorrecto
```
ANTES: { borrador_id: 1, ...data }
DESPUÉS: { success: true, data: { id: 1, ...data } }

Asegúrate de acceder a response.data.id, no response.id
```

---

## 🧪 TESTING LOCAL

```bash
# 1. Asegúrate que backend está en localhost
# Típicamente: http://localhost:8000 o http://localhost:3000

# 2. Abre Developer Tools (F12) en navegador

# 3. Haz clic en operación (crear, confirmar, etc.)

# 4. Verifica en Network tab:
    Request va a /api/pedidos (no /asesores/pedidos)
    Status code es 200/201/204 (no 410)
    Response JSON tiene estructura correcta

# 5. Verifica en Console tab:
    No hay errores JavaScript
    Respuesta se procesa correctamente
```

---

## 📞 SI TIENES DUDAS

### "¿Cómo cambio esta línea?"
→ Busca la operación en GUIA_MIGRACION_FRONTEND.md
→ Encuentra ANTES/DESPUÉS
→ Copia el DESPUÉS
→ Adapta a tu código

### "¿Qué endpoint uso?"
→ Ver GUIA_API_PEDIDOS_DDD.md
→ O GUIA_CUAL_ENDPOINT_USAR.md

### "¿Cómo manejo errores?"
→ Ver sección "Manejo de Errores" en GUIA_MIGRACION_FRONTEND.md

### "¿Los tests siguen pasando?"
→ Ejecuta `php artisan test`
→ Deberían pasar 16/16 tests
→ Si no, hay un error en tu código

---

## 📊 PROGRESO TRACKING

Crea un archivo llamado `FASE2_PROGRESO.md`:

```markdown
# FASE 2 PROGRESO

## Archivos encontrados:
- [ ] resources/views/asesores/pedidos/index.blade.php
- [ ] resources/js/pedidos.js
- [ ] resources/views/asesores/create.blade.php
... (lista completa)

## Archivos actualizados:
- [ ] index.blade.php - 5 cambios (HECHO)
- [ ] pedidos.js - 8 cambios (EN PROGRESO)
- [ ] create.blade.php - 3 cambios (PENDIENTE)

## Testing:
- [ ] Tests unitarios (16/16 pasando)
- [ ] Testing manual crear pedido
- [ ] Testing manual confirmar
- [ ] Testing manual cancelar
- [ ] Testing manual obtener detalle

## Estado: 60% completado (actualizar mientras avanzas)
```

---

## ESTIMADOS POR SECCIÓN

| Tarea | Tiempo | Notas |
|-------|--------|-------|
| Leer guía | 30 min | Obligatorio |
| Buscar archivos | 15 min | Usa comandos grep |
| Actualizar 1-2 archivos pequeños | 30 min | Templates simples |
| Actualizar archivos grandes | 1-2 horas | AJAX complejos |
| Testing manual | 1-2 horas | Flujos completos |
| **TOTAL** | **4-6 horas** | Depende cantidad archivos |

---

##  CUÁNDO FASE 2 ESTÁ LISTA

- [x] Todos los archivos actualizados
- [x] No hay referencias a /asesores/pedidos
- [x] Todos los tests pasan (16/16)
- [x] Testing manual completado
- [x] No hay errores 410 Gone
- [x] Cambios commiteados

**Entonces:** FASE 2 COMPLETADA 

---

## PRÓXIMA FASE

Cuando Fase 2 esté lista:
- Notificar al team
- Ejecutar Fase 3 (Consolidación BD)
- Luego Fase 4 (Cleanup)

**Total timeline:** ~1 semana para 100% completado

---

## 📚 DOCUMENTACIÓN DE REFERENCIA

| Necesito | Ver | Link |
|----------|-----|------|
| Ver cambios específicos | GUIA_MIGRACION_FRONTEND.md | Sección "Migración por Operación" |
| Listar endpoints | GUIA_API_PEDIDOS_DDD.md | Sección "Endpoints de Referencia" |
| Entender decisiones | GUIA_CUAL_ENDPOINT_USAR.md | Todas las secciones |
| Ver estado general | ESTADO_REFACTOR_RESUMEN.md | Sección "Próximas tareas" |
| Ejecutar búsquedas | FASE2_BUSQUEDA_ARCHIVOS.md | Sección "Comandos" |

---

## 🎓 TIPS & TRICKS

### Buscar y reemplazar eficiente:
```bash
# VS Code: Usa Ctrl+H para Find & Replace
# Busca: asesores/pedidos
# Reemplaza: api/pedidos
# Cuidado: Valida CADA cambio antes de confirmar
```

### Testing rápido:
```bash
# Acceso directo:
# 1. Haz cambio en código
# 2. Presiona F5 (refresh navegador)
# 3. Haz clic en operación
# 4. Verifica en Console que no hay errores
```

### Mantén historial:
```bash
# Commit después de CADA archivo actualizado:
git add resources/views/asesores/pedidos/index.blade.php
git commit -m "Actualizar index.blade.php para usar /api/pedidos"

# Así si algo falla, sabes exactamente qué cambió
```

---

## ⏰ DEADLINE

Fase 2 estimada: **4-6 horas de trabajo**

Si empiezas ahora:
- Hoy: Leer + Buscar archivos (45 min)
- Mañana: Actualizar archivos (3-4 horas)
- Mañana: Testing (1-2 horas)
- Resultado:  FASE 2 COMPLETADA

---

**¡COMENZAMOS AHORA?  SI / ⏸️ ESPERAR**

**Primer paso:** Abre GUIA_MIGRACION_FRONTEND.md y empieza a leer

---

*Documento: QUICK START FASE 2*
*Última actualización: 2024*
*Responsable: Team Frontend*
