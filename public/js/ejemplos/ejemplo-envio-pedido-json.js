/**
 * EJEMPLO PRÁCTICO: Enviar Pedido desde JSON al Backend
 * 
 * Este archivo muestra cómo el frontend debe construir el JSON
 * y enviarlo al backend para guardarlo correctamente.
 */

class ClientePedidosJSON {
    constructor(csrfToken) {
        this.csrfToken = csrfToken;
        this.urlGuardar = '/api/pedidos/guardar-desde-json';
        this.urlValidar = '/api/pedidos/validar-json';
    }

    /**
     * Ejemplo 1: Enviar pedido con una prenda simple
     * - 1 prenda (Polo)
     * - 2 variantes (S y M)
     * - 1 proceso (Bordado)
     */
    async ejemplo1_PrendaSimple() {
        const formData = new FormData();

        // DATOS JSON
        const datosJSON = {
            pedido_produccion_id: 1,
            prendas: [
                {
                    nombre_prenda: 'Polo',
                    descripcion: 'Polo manga corta con bordado frontal',
                    genero: 'dama',
                    de_bodega: true,
                    
                    // FOTOS DE PRENDA (opcionales)
                    fotos_prenda: [], // Serán agregadas vía FormData
                    
                    // FOTOS DE TELAS (opcionales)
                    fotos_tela: [],
                    
                    // VARIANTES (REQUERIDO: al menos 1)
                    variantes: [
                        {
                            talla: 'S',
                            cantidad: 30,
                            color_id: 1,      // ID del catálogo de colores
                            tela_id: 5,       // ID del catálogo de telas
                            tipo_manga_id: 2, // ID del catálogo de mangas
                            manga_obs: 'Manga corta',
                            tipo_broche_boton_id: 3,
                            broche_boton_obs: 'Botón blanco',
                            tiene_bolsillos: true,
                            bolsillos_obs: 'Bolsillos laterales'
                        },
                        {
                            talla: 'M',
                            cantidad: 50,
                            color_id: 1,
                            tela_id: 5,
                            tipo_manga_id: 2,
                            manga_obs: 'Manga corta',
                            tipo_broche_boton_id: 3,
                            broche_boton_obs: 'Botón blanco',
                            tiene_bolsillos: true,
                            bolsillos_obs: 'Bolsillos laterales'
                        }
                    ],
                    
                    // PROCESOS (opcional)
                    procesos: [
                        {
                            tipo_proceso_id: 3,  // Bordado
                            ubicaciones: ['Frente', 'Espalda'],
                            observaciones: 'Bordado en punto de cruz con hilo negro',
                            // Estructura relacional: { DAMA: {S: 1, M: 1, L: 1}, CABALLERO: {...} }
                            tallas: {
                                DAMA: { S: 1, M: 1, L: 1 },
                                CABALLERO: {},
                                UNISEX: {}
                            },
                            imagenes: [] // Serán agregadas vía FormData
                        }
                    ]
                }
            ]
        };

        // AGREGAR JSON A FORMDATA
        formData.append('pedido_produccion_id', datosJSON.pedido_produccion_id);
        formData.append('prendas', JSON.stringify(datosJSON.prendas));

        // ENVIAR
        return await this.enviar(formData);
    }

    /**
     * Ejemplo 2: Pedido con múltiples prendas y procesos
     */
    async ejemplo2_MultiplePrendasYProcesos() {
        const formData = new FormData();

        const datosJSON = {
            pedido_produccion_id: 1,
            prendas: [
                // PRENDA 1: Polo
                {
                    nombre_prenda: 'Polo',
                    descripcion: 'Polo manga corta',
                    genero: 'dama',
                    de_bodega: true,
                    fotos_prenda: [],
                    fotos_tela: [],
                    variantes: [
                        { talla: 'S', cantidad: 20, color_id: 1, tela_id: 5, tiene_bolsillos: false },
                        { talla: 'M', cantidad: 30, color_id: 1, tela_id: 5, tiene_bolsillos: false },
                        { talla: 'L', cantidad: 25, color_id: 1, tela_id: 5, tiene_bolsillos: false }
                    ],
                    procesos: [
                        {
                            tipo_proceso_id: 3,
                            ubicaciones: ['Frente'],
                            observaciones: 'Bordado logo empresa',
                            // Estructura relacional
                            tallas: {
                                DAMA: { S: 1, M: 1, L: 1 },
                                CABALLERO: {},
                                UNISEX: {}
                            },
                            imagenes: []
                        }
                    ]
                },
                // PRENDA 2: Camiseta
                {
                    nombre_prenda: 'Camiseta',
                    descripcion: 'Camiseta cuello V',
                    genero: 'caballero',
                    de_bodega: false, // Nueva, no de bodega
                    fotos_prenda: [],
                    fotos_tela: [],
                    variantes: [
                        { talla: 'M', cantidad: 40, color_id: 2, tela_id: 6, tiene_bolsillos: false },
                        { talla: 'L', cantidad: 60, color_id: 2, tela_id: 6, tiene_bolsillos: false }
                    ],
                    procesos: [
                        {
                            tipo_proceso_id: 4,  // Estampado
                            ubicaciones: ['Pecho'],
                            observaciones: 'Estampado digital full color',
                            // Estructura relacional
                            tallas: {
                                DAMA: {},
                                CABALLERO: { M: 1, L: 1 },
                                UNISEX: {}
                            },
                            imagenes: []
                        }
                    ]
                }
            ]
        };

        formData.append('pedido_produccion_id', datosJSON.pedido_produccion_id);
        formData.append('prendas', JSON.stringify(datosJSON.prendas));

        return await this.enviar(formData);
    }

    /**
     * Ejemplo 3: Pedido con archivos (fotos)
     * 
     * En este ejemplo se muestra cómo agregar archivos reales.
     */
    async ejemplo3_ConArchivos(
        fotoPrendaFile,
        fotoTelaFile,
        fotoProcesoBordadoFile,
        fotoProcesEstampFile
    ) {
        const formData = new FormData();

        const datosJSON = {
            pedido_produccion_id: 1,
            prendas: [
                {
                    nombre_prenda: 'Polo Premium',
                    descripcion: 'Polo con bordado y estampado',
                    genero: 'mixto',
                    de_bodega: true,
                    fotos_prenda: [fotoPrendaFile], // Se enviará vía FormData
                    fotos_tela: [
                        {
                            tela_id: 5,
                            color_id: 1,
                            archivo: fotoTelaFile,
                            ancho: 150,
                            alto: 200,
                            tamaño: 2048576,
                            observaciones: 'Tela algodon 100%'
                        }
                    ],
                    variantes: [
                        {
                            talla: 'S',
                            cantidad: 25,
                            color_id: 1,
                            tela_id: 5,
                            tipo_manga_id: 2,
                            manga_obs: 'Manga corta',
                            tiene_bolsillos: true,
                            bolsillos_obs: 'Bolsillos laterales'
                        }
                    ],
                    procesos: [
                        {
                            tipo_proceso_id: 3, // Bordado
                            ubicaciones: ['Frente', 'Espalda'],
                            observaciones: 'Bordado punto de cruz',
                            imagenes: [fotoProcesoBordadoFile] // Se enviará vía FormData
                        },
                        {
                            tipo_proceso_id: 4, // Estampado
                            ubicaciones: ['Manga'],
                            observaciones: 'Estampado sublimado',
                            imagenes: [fotoProcesEstampFile] // Se enviará vía FormData
                        }
                    ]
                }
            ]
        };

        // 1. Agregar JSON
        formData.append('pedido_produccion_id', datosJSON.pedido_produccion_id);
        formData.append('prendas', JSON.stringify(datosJSON.prendas));

        // 2. Agregar archivos de fotos de prenda
        if (fotoPrendaFile) {
            formData.append('prendas[0][fotos_prenda][]', fotoPrendaFile);
        }

        // 3. Agregar archivos de fotos de telas
        if (fotoTelaFile) {
            formData.append('prendas[0][fotos_tela][0][archivo]', fotoTelaFile);
        }

        // 4. Agregar archivos de procesos
        if (fotoProcesoBordadoFile) {
            formData.append('prendas[0][procesos][0][imagenes][]', fotoProcesoBordadoFile);
        }
        if (fotoProcesEstampFile) {
            formData.append('prendas[0][procesos][1][imagenes][]', fotoProcesEstampFile);
        }

        return await this.enviar(formData);
    }

    /**
     * VALIDAR sin guardar
     */
    async validar(datosJSON) {
        try {
            const response = await fetch(this.urlValidar, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(datosJSON),
            });

            const data = await response.json();

            if (!response.ok) {

                return {
                    valid: false,
                    errors: data.errors
                };
            }


            return {
                valid: true,
                message: data.message
            };
        } catch (error) {

            return {
                valid: false,
                error: error.message
            };
        }
    }

    /**
     * ENVIAR para guardar
     */
    async enviar(formData) {
        try {


            const response = await fetch(this.urlGuardar, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {

                return {
                    success: false,
                    message: data.message,
                    errors: data.errors
                };
            }


            return data;
        } catch (error) {

            return {
                success: false,
                error: error.message
            };
        }
    }
}

// ============================================================
// USO EN EL FRONTEND
// ============================================================

// Obtener token CSRF
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// Crear instancia
const clientePedidos = new ClientePedidosJSON(csrfToken);

// Ejemplo 1: Enviar pedido simple
async function enviarPedidoSimple() {
    const resultado = await clientePedidos.ejemplo1_PrendaSimple();
    
    if (resultado.success) {



    } else {

        if (resultado.errors) {

        }
    }
}

// Ejemplo 2: Validar antes de guardar
async function validarYGuardar() {
    const datosJSON = {
        pedido_produccion_id: 1,
        prendas: [
            {
                nombre_prenda: 'Polo',
                genero: 'dama',
                de_bodega: true,
                variantes: [
                    { talla: 'S', cantidad: 30 }
                ],
                procesos: []
            }
        ]
    };

    // 1. Validar
    const validacion = await clientePedidos.validar(datosJSON);
    
    if (!validacion.valid) {

        return;
    }

    // 2. Guardar
    const resultado = await clientePedidos.ejemplo1_PrendaSimple();

}

// Ejemplo 3: Con archivos desde input
async function enviarConArchivos() {
    const inputPrenda = document.getElementById('foto-prenda');
    const inputTela = document.getElementById('foto-tela');
    const inputBordado = document.getElementById('foto-bordado');

    const fotoPrenda = inputPrenda?.files[0];
    const fotoTela = inputTela?.files[0];
    const fotoBordado = inputBordado?.files[0];

    const resultado = await clientePedidos.ejemplo3_ConArchivos(
        fotoPrenda,
        fotoTela,
        fotoBordado,
        null
    );


}

// Invocar cuando el usuario presione "Guardar"
document.getElementById('btn-guardar-pedido')?.addEventListener('click', async () => {
    await enviarPedidoSimple();
});

document.getElementById('btn-validar-pedido')?.addEventListener('click', async () => {
    await validarYGuardar();
});
