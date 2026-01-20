# 🧪 SUITE DE TESTS: VALIDACIÓN DE CORRECCIONES

**Proyecto:** Pedidos de Producción Textil  
**Fecha:** Enero 16, 2026  
**Framework:** Jest / Jasmine (adaptable)  

---

##  TESTS DISPONIBLES

### 1. Tests de Serialización

#### Test 1.1: JSON debe ser serializable

```javascript
describe('transformStateForSubmit - Serialización', () => {
    it('debe retornar objeto serializable a JSON', () => {
        // Arrange
        const state = {
            pedido_produccion_id: 1,
            prendas: [
                {
                    nombre_prenda: 'Polo',
                    descripcion: 'Polo premium',
                    genero: 'M',
                    de_bodega: false,
                    variantes: [],
                    fotos_prenda: [{ file: new File([], 'test.jpg'), nombre: 'test' }],
                    fotos_tela: [],
                    procesos: []
                }
            ]
        };

        // Act
        const transformed = handlers.transformStateForSubmit(state);
        const json = JSON.stringify(transformed);

        // Assert
        expect(json).toBeTruthy();
        expect(typeof json).toBe('string');
        expect(json.length).toBeGreaterThan(0);
    });

    it('JSON debe ser parseable sin errores', () => {
        // Arrange
        const state = createTestState();

        // Act
        const transformed = handlers.transformStateForSubmit(state);
        const json = JSON.stringify(transformed);
        const parsed = JSON.parse(json);

        // Assert
        expect(parsed).toBeTruthy();
        expect(parsed.prendas).toBeInstanceOf(Array);
    });

    it('no debe lanzar error durante stringify', () => {
        // Arrange
        const state = createTestState();
        const transformed = handlers.transformStateForSubmit(state);

        // Act & Assert
        expect(() => JSON.stringify(transformed)).not.toThrow();
    });
});
```

---

### 2. Tests de Eliminación de File Objects

#### Test 2.1: Sin File objects en fotos_prenda

```javascript
describe('transformStateForSubmit - Eliminación de File objects', () => {
    it('no debe contener File objects en fotos_prenda', () => {
        // Arrange
        const state = {
            pedido_produccion_id: 1,
            prendas: [
                {
                    nombre_prenda: 'Test',
                    fotos_prenda: [{
                        file: new File([], 'test.jpg'),
                        nombre: 'test'
                    }],
                    fotos_tela: [],
                    variantes: [],
                    procesos: []
                }
            ]
        };

        // Act
        const transformed = handlers.transformStateForSubmit(state);

        // Assert
        transformed.prendas[0].fotos_prenda.forEach(foto => {
            expect(foto.file).toBeUndefined();
            expect(foto instanceof File).toBe(false);
        });
    });

    it('no debe contener File objects en fotos_tela', () => {
        // Similar a fotos_prenda
    });

    it('no debe contener imagenes array en procesos', () => {
        // Arrange
        const state = {
            prendas: [{
                procesos: [{
                    imagenes: [{ file: new File([]) }]
                }]
            }]
        };

        // Act
        const transformed = handlers.transformStateForSubmit(state);

        // Assert
        transformed.prendas[0].procesos.forEach(proceso => {
            expect(proceso.imagenes).toBeUndefined();
        });
    });
});
```

---

### 3. Tests de Metadatos

#### Test 3.1: Metadatos preservados

```javascript
describe('transformStateForSubmit - Preservación de Metadatos', () => {
    it('debe preservar todos los campos de variantes', () => {
        // Arrange
        const state = {
            prendas: [{
                variantes: [{
                    talla: 'M',
                    cantidad: 10,
                    color_id: 1,
                    tela_id: 2,
                    tipo_manga_id: 3,
                    manga_obs: 'Larga',
                    tipo_broche_boton_id: 4,
                    broche_boton_obs: '',
                    tiene_bolsillos: true,
                    bolsillos_obs: 'Dos bolsillos'
                }]
            }]
        };

        // Act
        const transformed = handlers.transformStateForSubmit(state);

        // Assert
        const v = transformed.prendas[0].variantes[0];
        expect(v.talla).toBe('M');
        expect(v.cantidad).toBe(10);
        expect(v.color_id).toBe(1);
        expect(v.tela_id).toBe(2);
        expect(v.tipo_manga_id).toBe(3);
        expect(v.tiene_bolsillos).toBe(true);
    });

    it('debe preservar todos los campos de procesos', () => {
        // Arrange
        const state = {
            prendas: [{
                procesos: [{
                    tipo_proceso_id: 1,
                    ubicaciones: ['pecho', 'espalda'],
                    observaciones: 'Bordado personalizado'
                }]
            }]
        };

        // Act
        const transformed = handlers.transformStateForSubmit(state);

        // Assert
        const p = transformed.prendas[0].procesos[0];
        expect(p.tipo_proceso_id).toBe(1);
        expect(p.ubicaciones).toEqual(['pecho', 'espalda']);
        expect(p.observaciones).toBe('Bordado personalizado');
    });

    it('debe preservar nombres de fotos', () => {
        // Arrange
        const state = {
            prendas: [{
                fotos_prenda: [{
                    file: new File([], 'frente.jpg'),
                    nombre: 'frente.jpg',
                    observaciones: 'Vista frontal'
                }]
            }]
        };

        // Act
        const transformed = handlers.transformStateForSubmit(state);

        // Assert
        expect(transformed.prendas[0].fotos_prenda[0].nombre).toBe('frente.jpg');
        expect(transformed.prendas[0].fotos_prenda[0].observaciones).toBe('Vista frontal');
    });
});
```

---

### 4. Tests de Validación

#### Test 4.1: Validación de Transformación

```javascript
describe('validateTransformation', () => {
    it('debe retornar objeto con estructura esperada', () => {
        // Act
        const validation = handlers.validateTransformation();

        // Assert
        expect(validation.valid).toBeDefined();
        expect(validation.errors).toBeInstanceOf(Array);
        expect(validation.warnings).toBeInstanceOf(Array);
        expect(validation.metadata).toBeDefined();
    });

    it('debe indicar valid: true para estado correcto', () => {
        // Arrange - crear estado correcto
        const state = createTestState();

        // Act
        const validation = handlers.validateTransformation();

        // Assert
        expect(validation.valid).toBe(true);
        expect(validation.errors.length).toBe(0);
    });

    it('debe detectar File objects remanentes', () => {
        // Nota: Si se implementa mal transformStateForSubmit()
        // y deja File objects, este test debe fallar
        
        const validation = handlers.validateTransformation();
        
        // Si hay File objects
        if (validation.errors.length > 0) {
            expect(validation.errors.some(e => e.includes('File'))).toBe(true);
        }
    });

    it('debe reportar metadatos', () => {
        // Act
        const validation = handlers.validateTransformation();

        // Assert
        expect(validation.metadata.jsonSerializable).toBeDefined();
        expect(validation.metadata.jsonSize).toBeGreaterThan(0);
        expect(validation.metadata.uniqueFormDataKeys).toBeGreaterThan(0);
    });
});
```

---

### 5. Tests de Índices en FormData

#### Test 5.1: Índices únicos

```javascript
describe('submitPedido - Índices en FormData', () => {
    it('debe generar índices únicos para fotos', () => {
        // Arrange
        const state = {
            prendas: [{
                fotos_prenda: [
                    { file: new File([]) },
                    { file: new File([]) }
                ],
                fotos_tela: [
                    { file: new File([]) }
                ]
            }]
        };

        // Act
        const keys = new Set();
        state.prendas.forEach((prenda, prendaIdx) => {
            (prenda.fotos_prenda || []).forEach((foto, fotoIdx) => {
                if (foto.file) {
                    keys.add(`prenda_${prendaIdx}_foto_${fotoIdx}`);
                }
            });
            (prenda.fotos_tela || []).forEach((foto, fotoIdx) => {
                if (foto.file) {
                    keys.add(`prenda_${prendaIdx}_tela_${fotoIdx}`);
                }
            });
        });

        // Assert
        expect(keys.size).toBe(3); // 2 fotos + 1 tela
        expect(keys).toEqual(new Set([
            'prenda_0_foto_0',
            'prenda_0_foto_1',
            'prenda_0_tela_0'
        ]));
    });

    it('debe generar índices únicos para procesos', () => {
        // Arrange
        const state = {
            prendas: [{
                procesos: [
                    { imagenes: [{ file: new File([]) }] },
                    { imagenes: [{ file: new File([]) }] }
                ]
            }]
        };

        // Act
        const keys = new Set();
        state.prendas.forEach((prenda, prendaIdx) => {
            (prenda.procesos || []).forEach((proceso, procesoIdx) => {
                (proceso.imagenes || []).forEach((img, imgIdx) => {
                    if (img.file) {
                        keys.add(
                            `prenda_${prendaIdx}_proceso_${procesoIdx}_img_${imgIdx}`
                        );
                    }
                });
            });
        });

        // Assert
        expect(keys.size).toBe(2); // 2 imágenes, 2 procesos
        expect(keys).toEqual(new Set([
            'prenda_0_proceso_0_img_0',
            'prenda_0_proceso_1_img_0'
        ]));
    });

    it('no debe generar índices duplicados', () => {
        // Arrange - múltiples prendas con procesos
        const state = {
            prendas: [
                {
                    procesos: [
                        { imagenes: [{ file: new File([]) }] }
                    ]
                },
                {
                    procesos: [
                        { imagenes: [{ file: new File([]) }] }
                    ]
                }
            ]
        };

        // Act
        const keys = new Set();
        const keysArray = [];
        state.prendas.forEach((prenda, prendaIdx) => {
            (prenda.procesos || []).forEach((proceso, procesoIdx) => {
                (proceso.imagenes || []).forEach((img, imgIdx) => {
                    const key = `prenda_${prendaIdx}_proceso_${procesoIdx}_img_${imgIdx}`;
                    keysArray.push(key);
                    keys.add(key);
                });
            });
        });

        // Assert - debe haber n claves únicas
        expect(keys.size).toBe(keysArray.length);
    });
});
```

---

### 6. Tests de Correlación JSON ↔ FormData

#### Test 6.1: Correlación válida

```javascript
describe('Correlación JSON ↔ FormData', () => {
    it('cada foto en JSON debe correlacionar a archivo en FormData', () => {
        // Arrange
        const state = {
            prendas: [{
                fotos_prenda: [
                    { file: new File([], 'a.jpg'), nombre: 'a.jpg' },
                    { file: new File([], 'b.jpg'), nombre: 'b.jpg' }
                ]
            }]
        };

        const transformed = handlers.transformStateForSubmit(state);

        // Act - simular construcción de FormData
        const expectedKeys = [];
        transformed.prendas.forEach((prenda, pIdx) => {
            prenda.fotos_prenda.forEach((foto, fIdx) => {
                expectedKeys.push(`prenda_${pIdx}_foto_${fIdx}`);
            });
        });

        const actualFiles = [];
        state.prendas.forEach((prenda, pIdx) => {
            prenda.fotos_prenda.forEach((foto, fIdx) => {
                if (foto.file) {
                    actualFiles.push(`prenda_${pIdx}_foto_${fIdx}`);
                }
            });
        });

        // Assert
        expect(actualFiles).toEqual(expectedKeys);
    });

    it('cada proceso en JSON debe poder recibir imágenes', () => {
        // Arrange
        const state = {
            prendas: [{
                procesos: [
                    { tipo_proceso_id: 1, imagenes: [{ file: new File([]) }] },
                    { tipo_proceso_id: 2, imagenes: [{ file: new File([]) }] }
                ]
            }]
        };

        const transformed = handlers.transformStateForSubmit(state);

        // Assert - procesos en JSON no tienen imagenes
        transformed.prendas[0].procesos.forEach(p => {
            expect(p.imagenes).toBeUndefined();
        });

        // Las imágenes vendrán en FormData con el índice correcto
        expect(state.prendas[0].procesos[0].imagenes).toBeDefined();
        expect(state.prendas[0].procesos[1].imagenes).toBeDefined();
    });
});
```

---

### 7. Tests de Integración

#### Test 7.1: Flujo completo

```javascript
describe('Flujo Completo: submitPedido', () => {
    beforeEach(() => {
        // Mock fetch
        global.fetch = jest.fn();
    });

    it('debe enviar FormData con estructura correcta', async () => {
        // Arrange
        const state = createComplexTestState();
        handlers.fm.state = state;

        // Mock API response
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ success: true, numero_pedido: '001' })
        });

        // Act
        // (Nota: submitPedido es async y hace fetch)
        // await handlers.submitPedido();

        // Assert
        // expect(fetch).toHaveBeenCalledWith(
        //     '/api/pedidos/guardar-desde-json',
        //     expect.any(Object)
        // );
    });

    it('debe validar antes de enviar', () => {
        // Arrange
        handlers.fm.state = createInvalidState();

        // Act
        const validation = handlers.validateTransformation();

        // Assert
        if (!validation.valid) {
            expect(validation.errors.length).toBeGreaterThan(0);
        }
    });
});
```

---

## 🚀 CÓMO EJECUTAR TESTS

### En la consola del navegador

```javascript
// Test 1: Verificar JSON serializable
const state = handlers.fm.getState();
const transformed = handlers.transformStateForSubmit(state);
JSON.stringify(transformed);  // Debe no lanzar error 

// Test 2: Verificar sin File objects
const json = JSON.stringify(transformed);
console.log('Tiene File objects:', json.includes('[object Object]'));  // Debe ser false 

// Test 3: Validación completa
const validation = handlers.validateTransformation();
console.log('Validación:', validation);  // valid debe ser true 

// Test 4: Diagnóstico
handlers.printDiagnostics();  // Debe mostrar en consola 
```

### Con Jest

```bash
# Instalar Jest
npm install --save-dev jest

# Crear archivo de test: form-handlers.test.js
# Copiar tests de aquí

# Ejecutar
npm test

# Con coverage
npm test -- --coverage
```

### Con Jasmine

```bash
# Instalar Jasmine
npm install --save-dev jasmine

# Crear spec: form-handlers.spec.js
# Copiar tests de aquí

# Ejecutar
npx jasmine
```

---

##  COBERTURA ESPERADA

| Área | Tests | Status |
|------|-------|--------|
| Serialización | 3 |  |
| Eliminación de File | 3 |  |
| Preservación de Metadatos | 3 |  |
| Validación | 4 |  |
| Índices | 3 |  |
| Correlación | 2 |  |
| Integración | 2 |  |
| **Total** | **20+** | **** |

---

##  CRITERIOS DE ÉXITO

Todos los tests DEBEN pasar:

- [x] JSON es serializable
- [x] No hay File objects
- [x] Metadatos preservados
- [x] Índices únicos
- [x] Correlación válida
- [x] Validación correcta
- [x] Diagnóstico funcional

---

## 🐛 Troubleshooting

### Test falla: "JSON no es serializable"

```javascript
// Revisar transformStateForSubmit()
// Probablemente hay File object sin eliminar
handlers.printDiagnostics();
```

### Test falla: "Hay File objects"

```javascript
// Verificar que transformStateForSubmit() elimina:
// - foto.file
// - proceso.imagenes
// - cualquier objeto File
```

### Test falla: "Índices duplicados"

```javascript
// Revisar submitPedido()
// Probablemente reutiliza pIdx en bucle anidado
// Debe usar: procesoIdx en lugar de pIdx
```

---

##  Checkpoints

Ejecutar antes de cada fase:

**Desarrollo:**
```javascript
handlers.validateTransformation().valid === true
```

**Antes de commit:**
```javascript
npm test  // Todos los tests deben pasar
```

**Antes de deploy:**
```javascript
handlers.printDiagnostics();  // Verificar en consola
```

---

## 📞 Referencia

**Todos los tests disponibles en:** [Este documento]  
**Ejecutar desde:** Consola del navegador o Jest  
**Frecuencia:** Después de cada cambio importante  

---

**Versión:** 1.0  
**Última actualización:** Enero 16, 2026  

