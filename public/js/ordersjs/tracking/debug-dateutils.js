'use strict';

// Script de depuración específico para DateUtils
console.log('🔍 Iniciando depuración de DateUtils...');

// Probar cada función individualmente
try {
    console.log('1. Probando formatDate...');
    const testDate = new Date();
    if (typeof formatDate === 'function') {
        const result = formatDate(testDate);
        console.log('✅ formatDate result:', result);
    } else {
        console.log('❌ formatDate no existe');
    }
} catch (e) {
    console.error('❌ Error en formatDate:', e);
}

try {
    console.log('2. Probando formatDateTime...');
    if (typeof formatDateTime === 'function') {
        const testDate2 = new Date();
        const result = formatDateTime(testDate2);
        console.log('✅ formatDateTime result:', result);
    } else {
        console.log('❌ formatDateTime no existe');
    }
} catch (e) {
    console.error('❌ Error en formatDateTime:', e);
}

try {
    console.log('3. Probando toDateObject...');
    if (typeof toDateObject === 'function') {
        const result = toDateObject('2024-01-01');
        console.log('✅ toDateObject result:', result);
    } else {
        console.log('❌ toDateObject no existe');
    }
} catch (e) {
    console.error('❌ Error en toDateObject:', e);
}

try {
    console.log('4. Probando calcularDiasHabilesSync...');
    if (typeof calcularDiasHabilesSync === 'function') {
        const result = calcularDiasHabilesSync(new Date(2024, 0, 1), new Date(2024, 0, 10));
        console.log('✅ calcularDiasHabilesSync result:', result);
    } else {
        console.log('❌ calcularDiasHabilesSync no existe');
    }
} catch (e) {
    console.error('❌ Error en calcularDiasHabilesSync:', e);
    console.error('Stack:', e.stack);
}

try {
    console.log('5. Probando formatDurationHuman...');
    if (typeof formatDurationHuman === 'function') {
        const result = formatDurationHuman(86400000);
        console.log('✅ formatDurationHuman result:', result);
    } else {
        console.log('❌ formatDurationHuman no existe');
    }
} catch (e) {
    console.error('❌ Error en formatDurationHuman:', e);
}

try {
    console.log('6. Verificando DateUtils instance...');
    if (window.dateUtils) {
        console.log('✅ dateUtils instance exists');
        console.log('dateUtils methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(window.dateUtils)));
    } else {
        console.log('❌ dateUtils instance no existe');
    }
} catch (e) {
    console.error('❌ Error verificando dateUtils:', e);
}

console.log('🏁 Depuración de DateUtils completada');
