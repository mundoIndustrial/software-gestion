# 🎨 IMPLEMENTACIÓN FRONTEND - ARQUITECTURA DE PRENDAS

## 📋 ÍNDICE

1. Estructura de Carpetas
2. Componentes React
3. Servicios API
4. Formularios
5. Listados
6. Ejemplos Completos

---

## 📁 ESTRUCTURA DE CARPETAS

```
resources/
└── js/
    └── prendas/
        ├── components/
        │   ├── PrendaForm.jsx
        │   ├── PrendaList.jsx
        │   ├── PrendaDetail.jsx
        │   ├── VarianteForm.jsx
        │   ├── TalaForm.jsx
        │   ├── TelaForm.jsx
        │   └── ImagenUpload.jsx
        ├── services/
        │   └── prendaService.js
        ├── hooks/
        │   ├── usePrendas.js
        │   └── useFormPrenda.js
        ├── utils/
        │   ├── validators.js
        │   └── formatters.js
        └── pages/
            ├── PrendasPage.jsx
            ├── CrearPrendaPage.jsx
            └── EditarPrendaPage.jsx
```

---

## 🔌 SERVICIO API

### `resources/js/prendas/services/prendaService.js`

```javascript
import axios from 'axios';

const API_URL = '/api/prendas';

const prendaService = {
    /**
     * Listar prendas
     */
    listar: async (page = 1, perPage = 15) => {
        try {
            const response = await axios.get(API_URL, {
                params: { page, per_page: perPage },
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            });
            return response.data;
        } catch (error) {
            console.error('Error listando prendas:', error);
            throw error;
        }
    },

    /**
     * Buscar prendas
     */
    buscar: async (termino, page = 1) => {
        try {
            const response = await axios.get(`${API_URL}/search`, {
                params: { q: termino, page },
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            });
            return response.data;
        } catch (error) {
            console.error('Error buscando prendas:', error);
            throw error;
        }
    },

    /**
     * Obtener prenda por ID
     */
    obtener: async (id) => {
        try {
            const response = await axios.get(`${API_URL}/${id}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            });
            return response.data;
        } catch (error) {
            console.error(`Error obteniendo prenda ${id}:`, error);
            throw error;
        }
    },

    /**
     * Crear prenda
     */
    crear: async (formData) => {
        try {
            const response = await axios.post(API_URL, formData, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'multipart/form-data',
                    'Accept': 'application/json'
                }
            });
            return response.data;
        } catch (error) {
            console.error('Error creando prenda:', error);
            throw error;
        }
    },

    /**
     * Actualizar prenda
     */
    actualizar: async (id, formData) => {
        try {
            const response = await axios.put(`${API_URL}/${id}`, formData, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'multipart/form-data',
                    'Accept': 'application/json'
                }
            });
            return response.data;
        } catch (error) {
            console.error(`Error actualizando prenda ${id}:`, error);
            throw error;
        }
    },

    /**
     * Eliminar prenda
     */
    eliminar: async (id) => {
        try {
            const response = await axios.delete(`${API_URL}/${id}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            });
            return response.data;
        } catch (error) {
            console.error(`Error eliminando prenda ${id}:`, error);
            throw error;
        }
    },

    /**
     * Obtener estadísticas
     */
    estadisticas: async () => {
        try {
            const response = await axios.get(`${API_URL}/stats`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            });
            return response.data;
        } catch (error) {
            console.error('Error obteniendo estadísticas:', error);
            throw error;
        }
    }
};

export default prendaService;
```

---

## 🎨 COMPONENTE: FORMULARIO DE PRENDA

### `resources/js/prendas/components/PrendaForm.jsx`

```jsx
import React, { useState, useEffect } from 'react';
import { AlertCircle, Plus, Trash2, Upload } from 'lucide-react';
import prendaService from '../services/prendaService';

const PrendaForm = ({ prendaId = null, onSuccess }) => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);

    // Datos básicos
    const [nombreProducto, setNombreProducto] = useState('');
    const [descripcion, setDescripcion] = useState('');
    const [tipoPrenda, setTipoPrenda] = useState('CAMISA');
    const [genero, setGenero] = useState('');

    // Tallas
    const [tallas, setTallas] = useState(['M', 'L']);
    const [tallasDisponibles] = useState(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']);

    // Variantes
    const [variantes, setVariantes] = useState([{
        tipo_manga_id: null,
        tipo_broche_id: null,
        tiene_bolsillos: false,
        tiene_reflectivo: false
    }]);

    // Telas
    const [telas, setTelas] = useState([{
        nombre: '',
        referencia: '',
        color: '',
        foto: null
    }]);

    // Fotos
    const [fotos, setFotos] = useState([]);

    // Tipos
    const [tiposPrenda] = useState([
        'CAMISA', 'PANTALON', 'JEAN', 'FALDA', 'BLUSA',
        'CHAQUETA', 'SUDADERA', 'POLO', 'CAMISETA', 'VESTIDO'
    ]);

    // Cargar prenda si es edición
    useEffect(() => {
        if (prendaId) {
            cargarPrenda();
        }
    }, [prendaId]);

    const cargarPrenda = async () => {
        try {
            setLoading(true);
            const response = await prendaService.obtener(prendaId);
            const prenda = response.data;

            setNombreProducto(prenda.nombre_producto);
            setDescripcion(prenda.descripcion);
            setTipoPrenda(prenda.tipo_prenda?.codigo || 'CAMISA');
            setGenero(prenda.genero?.nombre || '');
            setTallas(prenda.tallas.map(t => t.talla));
            setVariantes(prenda.variantes);
            setTelas(prenda.variantes[0]?.telas || []);
        } catch (err) {
            setError('Error cargando prenda: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        setSuccess(false);

        try {
            const formData = new FormData();

            // Datos básicos
            formData.append('nombre_producto', nombreProducto);
            formData.append('descripcion', descripcion);
            formData.append('tipo_prenda', tipoPrenda);
            formData.append('genero', genero);

            // Tallas
            tallas.forEach((talla, idx) => {
                formData.append(`tallas[${idx}]`, talla);
            });

            // Variantes
            variantes.forEach((variante, idx) => {
                formData.append(`variantes[${idx}][tipo_manga_id]`, variante.tipo_manga_id);
                formData.append(`variantes[${idx}][tipo_broche_id]`, variante.tipo_broche_id);
                formData.append(`variantes[${idx}][tiene_bolsillos]`, variante.tiene_bolsillos);
                formData.append(`variantes[${idx}][tiene_reflectivo]`, variante.tiene_reflectivo);
            });

            // Telas
            telas.forEach((tela, idx) => {
                formData.append(`telas[${idx}][nombre]`, tela.nombre);
                formData.append(`telas[${idx}][referencia]`, tela.referencia);
                formData.append(`telas[${idx}][color]`, tela.color);
                if (tela.foto instanceof File) {
                    formData.append(`telas[${idx}][foto]`, tela.foto);
                }
            });

            // Fotos
            fotos.forEach((foto, idx) => {
                if (foto.archivo instanceof File) {
                    formData.append(`fotos[${idx}][archivo]`, foto.archivo);
                    formData.append(`fotos[${idx}][tipo]`, foto.tipo || 'foto_prenda');
                }
            });

            let response;
            if (prendaId) {
                response = await prendaService.actualizar(prendaId, formData);
            } else {
                response = await prendaService.crear(formData);
            }

            setSuccess(true);
            if (onSuccess) {
                onSuccess(response.data);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Error guardando prenda');
        } finally {
            setLoading(false);
        }
    };

    const agregarTalla = () => {
        setTallas([...tallas, '']);
    };

    const eliminarTalla = (idx) => {
        setTallas(tallas.filter((_, i) => i !== idx));
    };

    const agregarTela = () => {
        setTelas([...telas, {
            nombre: '',
            referencia: '',
            color: '',
            foto: null
        }]);
    };

    const eliminarTela = (idx) => {
        setTelas(telas.filter((_, i) => i !== idx));
    };

    const agregarFoto = () => {
        setFotos([...fotos, {
            archivo: null,
            tipo: 'foto_prenda'
        }]);
    };

    const eliminarFoto = (idx) => {
        setFotos(fotos.filter((_, i) => i !== idx));
    };

    return (
        <div className="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow">
            <h1 className="text-3xl font-bold mb-6">
                {prendaId ? 'Editar Prenda' : 'Crear Nueva Prenda'}
            </h1>

            {error && (
                <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded flex items-center">
                    <AlertCircle className="mr-2" size={20} />
                    {error}
                </div>
            )}

            {success && (
                <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    ✅ Prenda guardada exitosamente
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-8">
                {/* DATOS BÁSICOS */}
                <section className="border-b pb-6">
                    <h2 className="text-xl font-semibold mb-4">Datos Básicos</h2>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-2">Nombre Producto *</label>
                            <input
                                type="text"
                                value={nombreProducto}
                                onChange={(e) => setNombreProducto(e.target.value)}
                                className="w-full px-3 py-2 border rounded-lg"
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-2">Tipo Prenda *</label>
                            <select
                                value={tipoPrenda}
                                onChange={(e) => setTipoPrenda(e.target.value)}
                                className="w-full px-3 py-2 border rounded-lg"
                                required
                            >
                                {tiposPrenda.map(tipo => (
                                    <option key={tipo} value={tipo}>{tipo}</option>
                                ))}
                            </select>
                        </div>
                        <div className="col-span-2">
                            <label className="block text-sm font-medium mb-2">Descripción *</label>
                            <textarea
                                value={descripcion}
                                onChange={(e) => setDescripcion(e.target.value)}
                                className="w-full px-3 py-2 border rounded-lg"
                                rows="3"
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-2">Género</label>
                            <input
                                type="text"
                                value={genero}
                                onChange={(e) => setGenero(e.target.value)}
                                placeholder="Ej: Dama, Caballero"
                                className="w-full px-3 py-2 border rounded-lg"
                            />
                        </div>
                    </div>
                </section>

                {/* TALLAS */}
                <section className="border-b pb-6">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-xl font-semibold">Tallas *</h2>
                        <button
                            type="button"
                            onClick={agregarTalla}
                            className="flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600"
                        >
                            <Plus size={18} /> Agregar Talla
                        </button>
                    </div>
                    <div className="grid grid-cols-4 gap-4">
                        {tallas.map((talla, idx) => (
                            <div key={idx} className="flex items-end gap-2">
                                <div className="flex-1">
                                    <select
                                        value={talla}
                                        onChange={(e) => {
                                            const newTallas = [...tallas];
                                            newTallas[idx] = e.target.value;
                                            setTallas(newTallas);
                                        }}
                                        className="w-full px-3 py-2 border rounded-lg"
                                    >
                                        <option value="">Seleccionar</option>
                                        {tallasDisponibles.map(t => (
                                            <option key={t} value={t}>{t}</option>
                                        ))}
                                    </select>
                                </div>
                                {tallas.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => eliminarTalla(idx)}
                                        className="bg-red-500 text-white p-2 rounded-lg hover:bg-red-600"
                                    >
                                        <Trash2 size={18} />
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                </section>

                {/* TELAS */}
                <section className="border-b pb-6">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-xl font-semibold">Telas *</h2>
                        <button
                            type="button"
                            onClick={agregarTela}
                            className="flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600"
                        >
                            <Plus size={18} /> Agregar Tela
                        </button>
                    </div>
                    <div className="space-y-4">
                        {telas.map((tela, idx) => (
                            <div key={idx} className="border p-4 rounded-lg">
                                <div className="grid grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-2">Nombre *</label>
                                        <input
                                            type="text"
                                            value={tela.nombre}
                                            onChange={(e) => {
                                                const newTelas = [...telas];
                                                newTelas[idx].nombre = e.target.value;
                                                setTelas(newTelas);
                                            }}
                                            className="w-full px-3 py-2 border rounded-lg"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-2">Referencia *</label>
                                        <input
                                            type="text"
                                            value={tela.referencia}
                                            onChange={(e) => {
                                                const newTelas = [...telas];
                                                newTelas[idx].referencia = e.target.value;
                                                setTelas(newTelas);
                                            }}
                                            className="w-full px-3 py-2 border rounded-lg"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-2">Color *</label>
                                        <input
                                            type="text"
                                            value={tela.color}
                                            onChange={(e) => {
                                                const newTelas = [...telas];
                                                newTelas[idx].color = e.target.value;
                                                setTelas(newTelas);
                                            }}
                                            className="w-full px-3 py-2 border rounded-lg"
                                            required
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex-1">
                                        <label className="block text-sm font-medium mb-2">Foto Tela</label>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={(e) => {
                                                const newTelas = [...telas];
                                                newTelas[idx].foto = e.target.files[0];
                                                setTelas(newTelas);
                                            }}
                                            className="w-full px-3 py-2 border rounded-lg"
                                        />
                                    </div>
                                    {telas.length > 1 && (
                                        <button
                                            type="button"
                                            onClick={() => eliminarTela(idx)}
                                            className="bg-red-500 text-white p-2 rounded-lg hover:bg-red-600 ml-4"
                                        >
                                            <Trash2 size={18} />
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                {/* FOTOS */}
                <section className="border-b pb-6">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-xl font-semibold">Fotos</h2>
                        <button
                            type="button"
                            onClick={agregarFoto}
                            className="flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600"
                        >
                            <Upload size={18} /> Agregar Foto
                        </button>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        {fotos.map((foto, idx) => (
                            <div key={idx} className="border p-4 rounded-lg">
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => {
                                        const newFotos = [...fotos];
                                        newFotos[idx].archivo = e.target.files[0];
                                        setFotos(newFotos);
                                    }}
                                    className="w-full px-3 py-2 border rounded-lg mb-2"
                                    required
                                />
                                <select
                                    value={foto.tipo}
                                    onChange={(e) => {
                                        const newFotos = [...fotos];
                                        newFotos[idx].tipo = e.target.value;
                                        setFotos(newFotos);
                                    }}
                                    className="w-full px-3 py-2 border rounded-lg"
                                >
                                    <option value="foto_prenda">Foto Prenda</option>
                                    <option value="foto_tela">Foto Tela</option>
                                </select>
                                {fotos.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => eliminarFoto(idx)}
                                        className="w-full bg-red-500 text-white p-2 rounded-lg hover:bg-red-600 mt-2"
                                    >
                                        <Trash2 size={18} className="mx-auto" />
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                </section>

                {/* BOTONES */}
                <div className="flex gap-4">
                    <button
                        type="submit"
                        disabled={loading}
                        className="flex-1 bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 disabled:bg-gray-400 font-semibold"
                    >
                        {loading ? 'Guardando...' : 'Guardar Prenda'}
                    </button>
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className="flex-1 bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 font-semibold"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    );
};

export default PrendaForm;
```

---

## 📊 COMPONENTE: LISTADO DE PRENDAS

### `resources/js/prendas/components/PrendaList.jsx`

```jsx
import React, { useState, useEffect } from 'react';
import { Edit2, Trash2, Eye, Search } from 'lucide-react';
import prendaService from '../services/prendaService';

const PrendaList = () => {
    const [prendas, setPrendas] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [busqueda, setBusqueda] = useState('');
    const [pagina, setPagina] = useState(1);
    const [total, setTotal] = useState(0);

    useEffect(() => {
        cargarPrendas();
    }, [pagina, busqueda]);

    const cargarPrendas = async () => {
        try {
            setLoading(true);
            let response;
            if (busqueda) {
                response = await prendaService.buscar(busqueda, pagina);
            } else {
                response = await prendaService.listar(pagina);
            }
            setPrendas(response.data);
            setTotal(response.pagination.total);
        } catch (err) {
            setError('Error cargando prendas: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleEliminar = async (id) => {
        if (window.confirm('¿Estás seguro de que deseas eliminar esta prenda?')) {
            try {
                await prendaService.eliminar(id);
                cargarPrendas();
            } catch (err) {
                setError('Error eliminando prenda: ' + err.message);
            }
        }
    };

    return (
        <div className="p-6">
            <div className="mb-6 flex gap-4">
                <div className="flex-1 relative">
                    <Search className="absolute left-3 top-3 text-gray-400" size={20} />
                    <input
                        type="text"
                        placeholder="Buscar prendas..."
                        value={busqueda}
                        onChange={(e) => {
                            setBusqueda(e.target.value);
                            setPagina(1);
                        }}
                        className="w-full pl-10 pr-4 py-2 border rounded-lg"
                    />
                </div>
                <a
                    href="/prendas/crear"
                    className="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600"
                >
                    + Nueva Prenda
                </a>
            </div>

            {error && (
                <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {error}
                </div>
            )}

            {loading ? (
                <div className="text-center py-8">Cargando...</div>
            ) : (
                <>
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse">
                            <thead className="bg-gray-100">
                                <tr>
                                    <th className="border p-3 text-left">Nombre</th>
                                    <th className="border p-3 text-left">Tipo</th>
                                    <th className="border p-3 text-left">Género</th>
                                    <th className="border p-3 text-left">Tallas</th>
                                    <th className="border p-3 text-left">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {prendas.map(prenda => (
                                    <tr key={prenda.id} className="hover:bg-gray-50">
                                        <td className="border p-3">{prenda.nombre_producto}</td>
                                        <td className="border p-3">{prenda.tipo_prenda?.nombre}</td>
                                        <td className="border p-3">{prenda.genero?.nombre}</td>
                                        <td className="border p-3">
                                            {prenda.tallas.map(t => t.talla).join(', ')}
                                        </td>
                                        <td className="border p-3 flex gap-2">
                                            <a
                                                href={`/prendas/${prenda.id}`}
                                                className="bg-blue-500 text-white p-2 rounded hover:bg-blue-600"
                                                title="Ver"
                                            >
                                                <Eye size={18} />
                                            </a>
                                            <a
                                                href={`/prendas/${prenda.id}/editar`}
                                                className="bg-yellow-500 text-white p-2 rounded hover:bg-yellow-600"
                                                title="Editar"
                                            >
                                                <Edit2 size={18} />
                                            </a>
                                            <button
                                                onClick={() => handleEliminar(prenda.id)}
                                                className="bg-red-500 text-white p-2 rounded hover:bg-red-600"
                                                title="Eliminar"
                                            >
                                                <Trash2 size={18} />
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginación */}
                    <div className="mt-6 flex justify-center gap-2">
                        <button
                            onClick={() => setPagina(p => Math.max(1, p - 1))}
                            disabled={pagina === 1}
                            className="px-4 py-2 border rounded disabled:bg-gray-100"
                        >
                            Anterior
                        </button>
                        <span className="px-4 py-2">Página {pagina}</span>
                        <button
                            onClick={() => setPagina(p => p + 1)}
                            className="px-4 py-2 border rounded hover:bg-gray-100"
                        >
                            Siguiente
                        </button>
                    </div>
                </>
            )}
        </div>
    );
};

export default PrendaList;
```

---

## 🛣️ RUTAS (React Router)

### `resources/js/prendas/pages/routes.jsx`

```jsx
import { lazy } from 'react';

const PrendasPage = lazy(() => import('./PrendasPage'));
const CrearPrendaPage = lazy(() => import('./CrearPrendaPage'));
const EditarPrendaPage = lazy(() => import('./EditarPrendaPage'));

export const prendaRoutes = [
    {
        path: '/prendas',
        element: <PrendasPage />
    },
    {
        path: '/prendas/crear',
        element: <CrearPrendaPage />
    },
    {
        path: '/prendas/:id/editar',
        element: <EditarPrendaPage />
    }
];
```

---

## 📝 EJEMPLO DE USO EN VITE + REACT

### `resources/js/app.jsx`

```jsx
import React, { Suspense } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { prendaRoutes } from './prendas/pages/routes';

function App() {
    return (
        <BrowserRouter>
            <Suspense fallback={<div>Cargando...</div>}>
                <Routes>
                    {prendaRoutes.map(route => (
                        <Route key={route.path} {...route} />
                    ))}
                </Routes>
            </Suspense>
        </BrowserRouter>
    );
}

export default App;
```

---

## 🎯 CONFIGURACIÓN EN BLADE

### `resources/views/prendas/index.blade.php`

```blade
@extends('layouts.app')

@section('content')
<div id="app"></div>

<script type="module">
    import { createRoot } from 'react-dom/client';
    import App from '@/prendas/pages/PrendasPage';
    
    const root = createRoot(document.getElementById('app'));
    root.render(<App />);
</script>
@endsection
```

---

## 🔐 AUTENTICACIÓN

Asegúrate de que el token esté disponible en localStorage:

```javascript
// En tu componente de login
localStorage.setItem('token', response.data.token);
```

---

## 📦 INSTALACIÓN DE DEPENDENCIAS

```bash
npm install axios react-router-dom lucide-react
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [ ] Crear estructura de carpetas
- [ ] Crear servicio API (prendaService.js)
- [ ] Crear componente PrendaForm.jsx
- [ ] Crear componente PrendaList.jsx
- [ ] Crear páginas (PrendasPage, CrearPrendaPage, EditarPrendaPage)
- [ ] Configurar rutas
- [ ] Instalar dependencias
- [ ] Configurar Vite
- [ ] Probar en navegador
- [ ] Validar envío de FormData
- [ ] Verificar carga de imágenes

---

**¡Frontend listo para usar!** 🚀

