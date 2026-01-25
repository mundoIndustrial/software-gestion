# 🚀 SCRIPT AUTO-DIAGNÓSTICO - COPIA Y EJECUTA EN LA CONSOLE

## INSTRUCCIONES

1. Abre Developer Tools (F12)
2. Vete a la pestaña **Console**
3. Haz clic en "Ver Recibos" del pedido (en la página)
4. Copia TODO el código de abajo en la console y presiona Enter
5. Espera 3 segundos
6. Copia TODO el output que aparezca en la console
7. Comparte el output conmigo

---

## CÓDIGO PARA COPIAR Y PEGAR EN LA CONSOLE

```javascript
// ============================================
// AUTO-DIAGNÓSTICO DE PROCESOS - V2
// Ejecuta esto 3 segundos después de hacer clic en "Ver Recibos"
// ============================================

console.clear();
console.log('%c🔍 INICIANDO AUTO-DIAGNÓSTICO DE PROCESOS', 'background: #1e40af; color: white; font-size: 14px; padding: 8px;');
console.log('⏱️ Timestamp:', new Date().toLocaleTimeString());

// ============================================
// PASO 1: Verificar ReceiptManager
// ============================================
console.group('%c1️⃣ VERIFICACIÓN DE ReceiptManager', 'background: #0ea5e9; color: white; padding: 4px;');

if (typeof window.receiptManager !== 'undefined') {
    console.log('✅ ReceiptManager cargado:', window.receiptManager !== undefined);
    
    if (window.receiptManager && window.receiptManager.datosFactura) {
        const datos = window.receiptManager.datosFactura;
        console.log('📊 Estructura de datos:');
        console.log('  • Prendas count:', datos.prendas ? datos.prendas.length : 'UNDEFINED');
        console.log('  • EPPs count:', datos.epps ? datos.epps.length : 'UNDEFINED');
        
        // ============================================
        // PASO 2: Analizar Primera Prenda
        // ============================================
        if (datos.prendas && datos.prendas.length > 0) {
            const prenda = datos.prendas[0];
            
            console.group('%c2️⃣ ANÁLISIS PRIMERA PRENDA', 'background: #10b981; color: white; padding: 4px;');
            console.log('Nombre:', prenda.nombre);
            console.log('Número:', prenda.numero);
            
            // Procesos
            console.group('%c🔹 PROCESOS', 'color: #f59e0b; font-weight: bold;');
            console.log('¿Tiene clave "procesos"?', 'procesos' in prenda ? '✅ SÍ' : '❌ NO');
            console.log('Valor de procesos:', prenda.procesos);
            console.log('¿Es array?', Array.isArray(prenda.procesos) ? '✅ SÍ' : '❌ NO');
            console.log('Tipo de datos:', typeof prenda.procesos);
            
            if (prenda.procesos === null) {
                console.log('%c⚠️ PROCESOS ES NULL', 'color: red; font-weight: bold;');
            } else if (prenda.procesos === undefined) {
                console.log('%c⚠️ PROCESOS ES UNDEFINED', 'color: red; font-weight: bold;');
            } else if (Array.isArray(prenda.procesos)) {
                console.log(`%c✅ PROCESOS ES ARRAY CON ${prenda.procesos.length} ITEMS`, 'color: green; font-weight: bold;');
                
                if (prenda.procesos.length > 0) {
                    console.log('%cPrimer proceso:', 'font-weight: bold;');
                    console.table(prenda.procesos[0]);
                    
                    console.log(`%cTodos los procesos (${prenda.procesos.length}):`, 'font-weight: bold;');
                    console.table(prenda.procesos);
                }
            } else if (typeof prenda.procesos === 'object') {
                console.log('%c⚠️ PROCESOS ES OBJETO (NO ARRAY)', 'color: orange; font-weight: bold;');
                console.log('Claves del objeto:', Object.keys(prenda.procesos));
                console.table(prenda.procesos);
            }
            console.groupEnd();
            
            // Todos los campos
            console.group('%c🔹 TODOS LOS CAMPOS DE LA PRENDA', 'color: #8b5cf6; font-weight: bold;');
            const campos = Object.keys(prenda);
            console.log(`Total de campos: ${campos.length}`);
            console.log('Campos:', campos);
            console.groupEnd();
            
            console.groupEnd();
        } else {
            console.log('❌ No hay prendas en los datos');
        }
    } else {
        console.log('❌ datosFactura no disponible en ReceiptManager');
    }
} else {
    console.log('❌ ReceiptManager NO está cargado');
    console.log('   Posible causa: El script de ReceiptManager no se ha cargado aún');
    console.log('   Solución: Espera 2-3 segundos más y vuelve a ejecutar');
}

console.groupEnd();

// ============================================
// PASO 3: Verificar recibos generados
// ============================================
console.group('%c3️⃣ RECIBOS GENERADOS', 'background: #ef4444; color: white; padding: 4px;');

if (typeof window.receiptManager !== 'undefined' && window.receiptManager.recibos) {
    console.log(`Total de recibos: ${window.receiptManager.recibos.length}`);
    console.log('Desglose por tipo:');
    
    const costura = window.receiptManager.recibos.filter(r => r.titulo === 'RECIBO DE COSTURA' || r.titulo === 'RECIBO DE COSTURA-BODEGA').length;
    const procesos = window.receiptManager.recibos.filter(r => r.titulo && r.titulo.startsWith('RECIBO DE ')).length - costura;
    
    console.log(`  • Recibos de costura: ${costura}`);
    console.log(`  • Recibos de procesos: ${procesos}`);
    console.log(`  • Total: ${costura + procesos}`);
    
    if (procesos === 0) {
        console.log('%c⚠️ ADVERTENCIA: No hay recibos de procesos', 'color: red; font-weight: bold;');
        console.log('   Esto sugiere que procesos está vacío o undefined');
    }
} else {
    console.log('❌ No hay recibos disponibles');
}

console.groupEnd();

// ============================================
// PASO 4: Resumen
// ============================================
console.group('%c4️⃣ RESUMEN EJECUTIVO', 'background: #1e293b; color: white; padding: 4px;');

if (window.receiptManager && window.receiptManager.datosFactura) {
    const prenda = window.receiptManager.datosFactura.prendas?.[0];
    
    if (prenda) {
        const tieneProc = 'procesos' in prenda;
        const esMal = prenda.procesos === null || prenda.procesos === undefined;
        const esArray = Array.isArray(prenda.procesos);
        const tieneItems = esArray && prenda.procesos.length > 0;
        
        console.log(`
Prenda: ${prenda.nombre}
├─ ¿Procesos existe? ${tieneProc ? '✅' : '❌'}
├─ ¿Es nulo/undefined? ${esMal ? '⚠️ SÍ' : '✅ NO'}
├─ ¿Es array? ${esArray ? '✅' : '❌'}
└─ ¿Tiene items? ${tieneItems ? `✅ (${prenda.procesos.length})` : '❌'}
        `.trim());
        
        if (!tieneProc) {
            console.log('%c❌ PROBLEMA IDENTIFICADO: procesos NO existe en la prenda', 'color: red; font-weight: bold; font-size: 12px;');
        } else if (esMal) {
            console.log('%c⚠️ PROBLEMA IDENTIFICADO: procesos es null o undefined', 'color: orange; font-weight: bold; font-size: 12px;');
        } else if (!esArray) {
            console.log('%c⚠️ PROBLEMA IDENTIFICADO: procesos NO es un array', 'color: orange; font-weight: bold; font-size: 12px;');
        } else if (!tieneItems) {
            console.log('%c⚠️ PROBLEMA IDENTIFICADO: procesos es array vacío', 'color: orange; font-weight: bold; font-size: 12px;');
        } else {
            console.log('%c✅ TODO CORRECTO: procesos está cargado correctamente', 'color: green; font-weight: bold; font-size: 12px;');
        }
    }
}

console.groupEnd();

console.log('%c✅ DIAGNÓSTICO COMPLETADO', 'background: green; color: white; font-size: 14px; padding: 8px;');
console.log('📋 Copia TODO el output de arriba y comparte conmigo');

```

---

## ¿QUÉ ESPERAR?

### Si procesos está bien:
```
✅ TODO CORRECTO: procesos está cargado correctamente
Prenda: CAMISETA XYZ
├─ ¿Procesos existe? ✅
├─ ¿Es nulo/undefined? ✅ NO
├─ ¿Es array? ✅
└─ ¿Tiene items? ✅ (3)
```

### Si procesos no existe:
```
❌ PROBLEMA IDENTIFICADO: procesos NO existe en la prenda
```

### Si procesos es null:
```
⚠️ PROBLEMA IDENTIFICADO: procesos es null o undefined
```

---

## CÓMO COPIAR EL OUTPUT

1. En la console, selecciona TODO el output
2. Click derecho → "Copy" o Ctrl+C
3. Pégalo en un archivo de texto o directamente en el chat

Así sabré exactamente qué está pasando.
