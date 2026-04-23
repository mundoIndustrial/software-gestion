#!/usr/bin/env node

/**
 * Script para actualizar BUILD_VERSION basado en timestamp de los archivos
 * Se ejecuta después de npm run build
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Obtener el timestamp más reciente de los archivos en public/js/insumos
const insumosDir = path.join(__dirname, '../public/js/insumos');
let latestTimestamp = 0;

try {
    const files = fs.readdirSync(insumosDir);
    files.forEach(file => {
        const filePath = path.join(insumosDir, file);
        if (fs.statSync(filePath).isFile()) {
            const timestamp = Math.floor(fs.statSync(filePath).mtimeMs / 1000);
            if (timestamp > latestTimestamp) {
                latestTimestamp = timestamp;
            }
        }
    });
} catch (error) {
    console.error('Error leyendo archivos:', error);
    process.exit(1);
}

// Leer el archivo materiales.entry.js
const entryFile = path.join(__dirname, '../resources/js/insumos/materiales.entry.js');
let content = fs.readFileSync(entryFile, 'utf-8');

// Actualizar BUILD_VERSION
const oldVersion = content.match(/const BUILD_VERSION = '[^']+'/)[0];
const newVersion = `const BUILD_VERSION = '${latestTimestamp}'`;
content = content.replace(oldVersion, newVersion);

// Escribir el archivo actualizado
fs.writeFileSync(entryFile, content, 'utf-8');

console.log(`✓ BUILD_VERSION actualizado a: ${latestTimestamp}`);
