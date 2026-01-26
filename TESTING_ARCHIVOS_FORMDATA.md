# 🧪 VERIFICACIÓN RÁPIDA - Flujo de Archivos FormData

## 📋 Script de Testing (Pegar en Browser Console)

Copia y pega esto en la consola del navegador mientras pruebas crear un pedido:

```javascript
// ============================================================================
// TEST 1: Verificar que extraerFilesDelPedido extrae correctamente
// ============================================================================
window.testExtraccion = function() {
    console.log('='.repeat(60));
    console.log('✅ TEST 1: VERIFICANDO extraerFilesDelPedido()');
    console.log('='.repeat(60));
    
    // Simular pedidoData con archivos (modificar según tu estructura)
    // Este es un test manual que debes ejecutar cuando hayas seleccionado archivos
    
    console.log('Para ejecutar este test:');
    console.log('1. Llena el formulario con archivos');
    console.log('2. Abre DevTools → Console');
    console.log('3. Ejecuta: window.testExtraccion()');
    console.log('4. Busca "archivos_en_map" en el log');
    console.log('   → Si archivos_en_map > 0: ✅ CORRECTO');
    console.log('   → Si archivos_en_map === 0: ❌ PROBLEMA EN extraerFiles');
};

// ============================================================================
// TEST 2: Monitorear FormData durante POST
// ============================================================================
window.monitorFormData = function() {
    console.log('='.repeat(60));
    console.log('✅ TEST 2: MONITOREANDO FormData');
    console.log('='.repeat(60));
    
    // Interceptar FormData.prototype.append
    const originalAppend = FormData.prototype.append;
    let archivosCont = 0;
    
    FormData.prototype.append = function(key, value) {
        if (value instanceof File) {
            archivosCont++;
            console.log(`  📎 Archivo ${archivosCont} agregado:`, {
                key: key,
                name: value.name,
                size: value.size,
                type: value.type
            });
        } else if (key === 'pedido') {
            console.log(`  📄 JSON agregado:`, {
                tamaño: value.length,
                preview: value.substring(0, 50) + '...'
            });
        }
        return originalAppend.call(this, key, value);
    };
    
    console.log('✅ Monitoreo activado');
    console.log('Ahora cuando hagas POST, verás los archivos en FormData aquí');
};

// ============================================================================
// TEST 3: Verificar respuesta del backend
// ============================================================================
window.verificarRespuestaBackend = function() {
    console.log('='.repeat(60));
    console.log('✅ TEST 3: VERIFICANDO Respuesta Backend');
    console.log('='.repeat(60));
    
    console.log('1. Crea un pedido');
    console.log('2. Si ves un alert de éxito → backend recibió los datos');
    console.log('3. Abre el servidor → /storage/logs/laravel.log');
    console.log('4. Busca "[CrearPedidoEditableController] 📤 Archivos en FormData"');
    console.log('5. Verifica que "total_archivos" sea > 0');
};

// ============================================================================
// TEST 4: Comparar Frontend vs Backend
// ============================================================================
window.compararFrontendBackend = function() {
    console.log('='.repeat(60));
    console.log('✅ TEST 4: COMPARAR Frontend vs Backend');
    console.log('='.repeat(60));
    
    const checklist = [
        {
            paso: 1,
            frontend: 'console.log muestra archivos_totales > 0',
            backend: 'CrearPedidoEditableController muestra archivos_count > 0',
            esperado: 'IGUALES'
        },
        {
            paso: 2,
            frontend: 'buildFormData muestra "Agregado archivo" N veces',
            backend: 'Debug log muestra total_archivos: N',
            esperado: 'IGUALES'
        },
        {
            paso: 3,
            frontend: 'FormData tiene prendas[0][...], prendas[0][telas][...], etc',
            backend: 'keys_recibidas contiene esas mismas claves',
            esperado: 'IGUALES'
        },
        {
            paso: 4,
            frontend: 'Cada archivo tiene size > 0',
            backend: 'Cada archivo recibido tiene size > 0',
            esperado: 'SIN archivos con size: 0'
        }
    ];
    
    console.table(checklist);
};

// ============================================================================
// TEST 5: Ejecutar todos los tests
// ============================================================================
window.runAllTests = function() {
    window.testExtraccion();
    window.monitorFormData();
    window.verificarRespuestaBackend();
    window.compararFrontendBackend();
};

console.log('%c🧪 TESTS DISPONIBLES', 'background: #4CAF50; color: white; padding: 10px; font-size: 14px; border-radius: 5px;');
console.log('Ejecuta en console:');
console.log('  window.testExtraccion()          - Test de extracción de archivos');
console.log('  window.monitorFormData()         - Monitorear FormData en tiempo real');
console.log('  window.verificarRespuestaBackend() - Guía para verificar backend');
console.log('  window.compararFrontendBackend() - Comparativa Frontend vs Backend');
console.log('  window.runAllTests()             - Ejecutar todos los tests');
```

## 📝 Guía Paso a Paso

### PASO 1: Preparar el ambiente
```bash
1. Abre el navegador en http://localhost:8000/...crear-pedido
2. Abre DevTools (F12)
3. Ve a la pestaña Console
4. Pega el script anterior (completo)
5. Presiona Enter
```

### PASO 2: Simular creación de pedido
```javascript
// En la consola, ejecuta:
window.monitorFormData()

// Luego en la UI:
1. Rellena los datos básicos del pedido
2. Selecciona 3 archivos (prenda, tela, proceso)
3. Haz click en "Crear Pedido"
```

### PASO 3: Verificar Frontend
Busca en la consola:
```
✅ EXTRACCIÓN COMPLETADA: {
    archivos_totales: 3,
    archivos_en_map: 3  ← DEBE SER 3
}

✅ FormData construido: {
    archivos_totales: 3  ← DEBE SER 3
}
```

### PASO 4: Verificar Backend
En otra terminal, ejecuta:
```bash
tail -f storage/logs/laravel.log | grep CrearPedidoEditableController
```

Busca:
```
[CrearPedidoEditableController] 🚀 Iniciando {
    "archivos_count": 3  ← DEBE SER 3
}

[CrearPedidoEditableController] 📤 Archivos en FormData {
    "total_archivos": 3,  ← DEBE SER 3
    "archivos": [...]
}
```

### PASO 5: Comparar Resultados
| Métrica | Frontend | Backend | Esperado |
|---------|----------|---------|----------|
| archivos_totales | 3 | archivos_count: 3 | IGUALES ✅ |
| archivos en map | 3 | total_archivos: 3 | IGUALES ✅ |
| size de archivo | > 0 | size: > 0 | > 0 ✅ |
| nombres de keys | prendas[0][imagenes][0] | key: prendas[0][imagenes][0] | IGUALES ✅ |

---

## ❌ Troubleshooting Rápido

### Problema: "archivos_totales: 0"
```javascript
// En console, ejecuta:
console.log('[DEBUG] ¿PayloadNormalizer existe?', typeof window.PayloadNormalizer);
console.log('[DEBUG] ¿buildFormData existe?', typeof window.PayloadNormalizer?.buildFormData);
console.log('[DEBUG] ¿extraerFilesDelPedido existe?', typeof window.ItemAPIService?.prototype?.extraerFilesDelPedido);

// Si alguno es "undefined" → archivo no se cargó
```

### Problema: "size: 0" en backend
```javascript
// Verifica que los archivos seleccionados son válidos:
// 1. El archivo no está corrupto
// 2. El archivo tiene contenido
// 3. Prueba con otro archivo diferente
```

### Problema: "key: prendas" (clave simple, no anidada)
```javascript
// En console, busca el log de buildFormData:
// Si dice "FormData construido: archivos_totales: 0"
// → Los archivos no se extranjeron o no se encontraron

// Solución: Revisar que extraerFilesDelPedido devuelve estructura correcta
console.log('[DEBUG] Estructura de archivos extraídos');
// Debe tener:
// { prendas: [...], epps: [...], archivosMap: {...} }
```

---

## ✅ Resultado Esperado Final

Cuando todo funciona correctamente, verás:

### Console Frontend:
```
[extraerFilesDelPedido] ✅ EXTRACCIÓN COMPLETADA: {
  prendas: 1,
  epps: 0,
  archivos_totales: 3,
  archivos_en_map: 3
}

[PayloadNormalizer.buildFormData] Agregado archivo prenda: {
  key: "prendas[0][imagenes][0]",
  nombre: "prenda_001.jpg",
  size: 245678
}
...
[PayloadNormalizer.buildFormData] FormData construido: {
  json_size: 4856,
  archivos_totales: 3,
  verificacion: "Si archivos_totales === 0..."
}
```

### Laravel Log:
```
[CrearPedidoEditableController] 🚀 Iniciando creación transaccional {
  "has_pedido_json": true,
  "archivos_count": 3
}

[CrearPedidoEditableController] 📤 Archivos en FormData {
  "total_archivos": 3,
  "archivos": [
    {"key": "prendas[0][imagenes][0]", "name": "prenda_001.jpg", "size": 245678},
    {"key": "prendas[0][telas][0][imagenes][0]", "name": "tela_001.jpg", "size": 182456},
    {"key": "prendas[0][procesos][reflectivo][0]", "name": "ref_001.jpg", "size": 98765}
  ]
}

[ResolutorImagenesService] ✅ Extracción completada {
  "pedido_id": 2729,
  "imagenes_procesadas": 3,
  "imagenes_esperadas": 3,
  "diferencia": 0
}

[CrearPedidoEditableController] TRANSACCIÓN EXITOSA {
  "pedido_id": 2729,
  "numero_pedido": 100010,
  "cantidad_total": 60
}
```

### Base de Datos:
```bash
# En el servidor, verifica:
ls -la storage/app/public/pedidos/2729/prendas/
ls -la storage/app/public/pedidos/2729/telas/
ls -la storage/app/public/pedidos/2729/procesos/

# Deben existir los archivos .webp guardados
```

---

## 📚 Documentación de Referencia

- Documento principal: [SOLUCION_ARCHIVOS_FORMDATA_CORRECTA.md](SOLUCION_ARCHIVOS_FORMDATA_CORRECTA.md)
- Codigo modificado: 
  - [item-api-service.js](public/js/modulos/crear-pedido/procesos/services/item-api-service.js#L514)
  - [payload-normalizer-v3-definitiva.js](public/js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js#L152)

---

**Última actualización**: 26 Enero 2026  
**Status**: ✅ LISTO PARA TESTING
