#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Servidor Flask para Analizador de Artículos
Conecta el HTML con Python para análisis profesional
"""

from flask import Flask, request, jsonify
from werkzeug.utils import secure_filename
from flask_cors import CORS
import json
import os
import re
from collections import defaultdict
from pathlib import Path

import pandas as pd

try:
    from PyPDF2 import PdfReader
    PDF_AVAILABLE = True
except:
    PDF_AVAILABLE = False
    print("⚠️ PyPDF2 no disponible")

app = Flask(__name__, static_folder=str(Path(__file__).parent), static_url_path='')
CORS(app)  # Habilitar CORS para todos los endpoints
app.config['MAX_CONTENT_LENGTH'] = 50 * 1024 * 1024
app.config['UPLOAD_FOLDER'] = Path(__file__).parent / 'uploads'
app.config['UPLOAD_FOLDER'].mkdir(exist_ok=True)

ALLOWED_EXTENSIONS = {'pdf', 'csv', 'xlsx', 'xls', 'json', 'txt'}


class AnalizadorArticulos:
    """Analizador inteligente de artículos"""
    
    COLORES = ['NEGRO', 'NEGRA', 'BLANCO', 'BLANCA', 'ROJO', 'ROJA', 'AZUL', 'AMARILLO', 'AMARILLA', 
               'VERDE', 'GRIS', 'NARANJA', 'CAFE', 'MARRÓN', 'ROSA', 'VINOTINTO', 'TURQUEZA', 
               'ORO', 'CHOCOLATE', 'MARINO', 'CELESTE', 'MORADO', 'PLATA', 'PLATEADO', 'DORADO', 
               'GRIS OSCURO', 'GRIS CLARO', 'PLATEADA', 'DORADA']
    
    MARCAS = ['STEELPRO', 'NIKE', 'SAGA', 'KONDOR', 'GRULLA', 'WARRIOR', 'ARMADURA', 'BRAHMA',
              'WELDER', 'NORSEG', 'INDIANA', 'ROGER', 'ROBUSTA', 'TROOPER', 'TOLEDO', 'ANSELL',
              'HONEYWELL', 'MSA', '3M', 'PETZL', 'MOLDEX', 'UVEX', 'NORTH', 'JSP', 'PATROL',
              'RAYDER', 'PETROL', 'EVACOL', 'TORINO', 'SAGA', 'WARRIOR']
    
    MATERIALES = ['ALGODÓN', 'VAQUETA', 'CUERO', 'CAUCHO', 'LATEX', 'NITRILO', 'NEOPRENO',
                  'POLIURETANO', 'DRIL', 'LONA', 'CARNAZA', 'ACERO', 'POLYCARBONATO', 'NYLON',
                  'FIBERGLASS', 'GOMA', 'PVC', 'TELA', 'POLIÉSTER', 'SPANDEX', 'LYCRA']
    
    CATEGORIAS = {
        'BOTA': 'CALZADO',
        'ZAPATO': 'CALZADO',
        'CASCO': 'PROTECCIÓN CABEZA',
        'LENTE': 'PROTECCIÓN OJOS',
        'GUANTE': 'GUANTES',
        'MASCARILLA': 'PROTECCIÓN RESPIRATORIA',
        'CARETA': 'PROTECCIÓN CARA',
        'CHALECO': 'PROTECCIÓN TORSO',
        'ARNÉS': 'LÍNEAS DE VIDA',
        'ALCOHOL': 'DESINFECTANTES',
        'ANTISÉPTICO': 'DESINFECTANTES',
        'SEÑALIZACIÓN': 'SEÑALIZACIÓN',
        'EXTINTOR': 'SEGURIDAD',
        'BOTIQUÍN': 'PRIMEROS AUXILIOS',
        'LINTERNA': 'ILUMINACIÓN',
        'CUERDA': 'ACCESORIOS',
        'ESLINGA': 'ACCESORIOS',
    }
    
    def __init__(self):
        self.articulos = []
        self.duplicaciones = {}
    
    def procesar_pdf(self, ruta_pdf: str) -> list:
        """Extrae texto de PDF"""
        if not PDF_AVAILABLE:
            return []
        
        texto_completo = ""
        try:
            with open(ruta_pdf, 'rb') as file:
                pdf_reader = PdfReader(file)
                for page in pdf_reader.pages:
                    texto = page.extract_text() or ""
                    texto_completo += texto + "\n"
        except Exception as e:
            print(f"Error leyendo PDF: {e}")
            return []
        
        if not texto_completo.strip():
            return []
        
        return self.procesar_listado_desordenado(texto_completo)
    
    def procesar_csv(self, ruta_csv: str) -> list:
        """Lee archivo CSV o Excel"""
        try:
            if ruta_csv.endswith(('.xlsx', '.xls')):
                df = pd.read_excel(ruta_csv)
            else:
                df = pd.read_csv(ruta_csv)
            
            return df.to_dict('records')
        except Exception as e:
            print(f"Error leyendo archivo: {e}")
            return []
    
    def procesar_listado_desordenado(self, texto: str) -> list:
        """Procesa listado donde artículos pueden estar en líneas separadas O pegados"""
        
        # Primero intentar dividir por saltos de línea normales
        lineas = texto.split('\n')
        
        # Si hay pocas líneas, probablemente todo está pegado - dividir líneas largas
        if len(lineas) <= 3:
            lineas = self._dividir_articulos_pegados(texto)
        
        lineas = [l.strip().strip('"') for l in lineas 
                  if l.strip() and l.strip() != '""']
        
        articulos = []
        
        for linea in lineas:
            if not linea or len(linea) < 3:
                continue
            
            # Cada línea es un artículo - NO agrupar líneas
            articulos.append(self._estructurar_articulo_linea(linea))
        
        return articulos
    
    def _dividir_articulos_pegados(self, texto: str) -> list:
        """Divide artículos pegados en una sola línea"""
        # Palabras clave que indican INICIO de nuevo artículo
        patrones_inicio = [
            r'(?:^|\s)(SEÑAL|ALCOHOL|BOTA|BOT\s|CASQUETE|STEELPRO(?!\s(?:ESCOBAS|ESPONJA))|ESCOBAS|ESPONJA|JABON|RECOGEDOR|TRAPERO|DUCHA|LENTE|ESLINGA|ZAPATO|GUANTE|VALVULA|ARAÑA|ARNES|YODO)',
        ]
        
        # Marcar posiciones donde comienzan artículos
        texto_upper = texto.upper()
        divisiones = [0]
        
        # Palabras que típicamente indican inicio de artículo
        inicios = ['SEÑAL', 'ALCOHOL', 'BOTA ', 'BOT ', 'CASQUETE', 'ESCOBAS', 'ESPONJA', 
                   'JABON', 'RECOGEDOR', 'TRAPERO', 'DUCHA', 'LENTE', 'ESLINGA', 'ZAPATO',
                   'GUANTE', 'VALVULA', 'ARAÑA', 'ARNES', 'YODO', 'ANTIBACTERIAL']
        
        for inicio in inicios:
            pos = 0
            while True:
                pos = texto_upper.find(inicio, pos)
                if pos == -1:
                    break
                # Solo si está al inicio o después de espacio
                if pos == 0 or texto_upper[pos-1] == ' ':
                    if pos not in divisiones and pos > 0:
                        divisiones.append(pos)
                pos += 1
        
        divisiones.sort()
        divisiones.append(len(texto))
        
        # Extraer artículos entre divisiones
        articulos_divididos = []
        for i in range(len(divisiones)-1):
            articulo = texto[divisiones[i]:divisiones[i+1]].strip()
            if articulo:
                articulos_divididos.append(articulo)
        
        return articulos_divididos if articulos_divididos else [texto]
    
    def _estructurar_articulo_linea(self, linea: str) -> dict:
        """Estructura un artículo de una sola línea con campos específicos"""
        texto_upper = linea.upper()
        
        return {
            'nombre_completo': linea,
            'marca': self._extraer_marca(texto_upper) or None,
            'categoria': self._extraer_categoria(texto_upper) or None,
            'tipo': self._extraer_tipo(texto_upper),
            'talla': self._extraer_talla(texto_upper) or None,
            'color': self._extraer_color(texto_upper) or None,
            'material': self._extraer_material(texto_upper) or None,
        }
    
    
    def _extraer_marca(self, texto: str) -> str:
        for marca in self.MARCAS:
            if marca in texto:
                return marca
        return ''
    
    def _extraer_color(self, texto: str) -> str:
        for color in self.COLORES:
            if color in texto:
                return color
        return ''
    
    def _extraer_material(self, texto: str) -> str:
        for material in self.MATERIALES:
            if material in texto:
                return material
        return ''
    
    def _extraer_talla(self, texto: str) -> str:
        match = re.search(r'T[:\s]*(\d+|S|M|L|XL|XXL|XXXL)', texto)
        return match.group(1) if match else ''
    
    def _extraer_medida(self, texto: str) -> str:
        match = re.search(r'(\d+[\*x]\d+)|(\d+")|(\d+\s*cm)', texto, re.I)
        return match.group(0) if match else ''
    
    def _extraer_referencia(self, texto: str) -> str:
        match = re.search(r'REF[:\.\s]+([A-Z0-9\-\.]+)', texto)
        return match.group(1).strip() if match else ''
    
    def _extraer_categoria(self, texto: str) -> str:
        """Extrae la categoría basada en palabras clave"""
        for palabra_clave, categoria in self.CATEGORIAS.items():
            if palabra_clave in texto:
                return categoria
        return None
    
    def _extraer_tipo(self, texto: str) -> str:
        """Determina si es PRODUCTO o SERVICIO"""
        palabras_servicio = ['SERVICIO', 'RECARGA', 'INSPECCIÓN', 'REVISIÓN', 'REPARACIÓN', 
                             'MANTENIMIENTO', 'INSTALACIÓN', 'CAPACITACIÓN', 'CONSULTORÍA']
        
        for palabra in palabras_servicio:
            if palabra in texto:
                return 'SERVICIO'
        
        return 'PRODUCTO'
    
    def procesar_datos(self, datos: list) -> dict:
        """Procesa datos y retorna análisis completo"""
        if not datos:
            return {'error': 'No hay datos para procesar'}
        
        self.articulos = datos  # Ya están estructurados
        duplicaciones = self._analizar_duplicaciones()
        
        return {
            'total_articulos': len(self.articulos),
            'articulos': self.articulos,
            'duplicaciones': duplicaciones,
            'total_duplicaciones': len(duplicaciones)
        }
    
    def _normalizar_datos(self, datos: list) -> list:
        """Normaliza nombres de columnas"""
        resultado = []
        
        for item in datos:
            obj = {}
            
            for key, valor in item.items():
                lower_key = key.lower().strip()
                valor_str = str(valor or '').strip()
                
                if 'nombre' in lower_key or 'articulo' in lower_key or 'producto' in lower_key or 'descripción' in lower_key:
                    obj['nombre_completo'] = valor_str
                elif 'marca' in lower_key:
                    obj['marca'] = valor_str
                elif 'color' in lower_key:
                    obj['color'] = valor_str
                elif 'material' in lower_key:
                    obj['Material'] = valor_str
                elif 'talla' in lower_key or 'tamaño' in lower_key or 'size' in lower_key:
                    obj['Talla'] = valor_str
                elif 'medida' in lower_key or 'dimensión' in lower_key:
                    obj['Medida'] = valor_str
                elif 'referencia' in lower_key or 'ref' in lower_key or 'sku' in lower_key:
                    obj['Referencia'] = valor_str
                else:
                    obj[key] = valor_str
            
            if not obj.get('Nombre'):
                obj['Nombre'] = str(list(item.values())[0] if item else '')
            
            resultado.append(obj)
        
        return resultado
    
    def _analizar_duplicaciones(self) -> list:
        """Analiza y agrupa duplicaciones"""
        campos_agrupacion = ['Color', 'Marca', 'Material', 'Talla', 'Medida']
        
        grupos = defaultdict(list)
        
        for idx, articulo in enumerate(self.articulos):
            clave = tuple(
                articulo.get(campo, 'N/A')
                for campo in campos_agrupacion
                if articulo.get(campo, '').strip()
            )
            
            if clave:
                grupos[clave].append({**articulo, '_indice': idx})
        
        # Filtrar solo duplicados
        duplicados = [
            {
                'criterios': {
                    'Color': k[0] if len(k) > 0 else 'N/A',
                    'Marca': k[1] if len(k) > 1 else 'N/A',
                    'Material': k[2] if len(k) > 2 else 'N/A',
                    'Talla': k[3] if len(k) > 3 else 'N/A',
                    'Medida': k[4] if len(k) > 4 else 'N/A',
                },
                'articulos': v
            }
            for k, v in grupos.items()
            if len(v) > 1
        ]
        
        # Ordenar por cantidad
        return sorted(duplicados, key=lambda x: len(x['articulos']), reverse=True)


@app.route('/api/procesar', methods=['POST'])
def procesar():
    """Endpoint para procesar datos - acepta texto, JSON o listados desordenados"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'error': 'No hay datos'}), 400
        
        analizador = AnalizadorArticulos()
        
        # Determinar tipo de entrada
        if 'texto' in data and data['texto'].strip():
            # Procesar texto directo (listado desordenado o CSV)
            texto = data['texto']
            
            # Intentar como JSON primero
            try:
                if texto.strip().startswith(('[', '{')):
                    datos_json = json.loads(texto)
                    if isinstance(datos_json, dict):
                        datos_json = [datos_json]
                    articulos = datos_json
                else:
                    # Procesar como listado desordenado
                    articulos = analizador.procesar_listado_desordenado(texto)
            except:
                # Si falla JSON, procesar como listado desordenado
                articulos = analizador.procesar_listado_desordenado(texto)
        
        elif 'datos' in data:
            # Procesar datos JSON directamente
            articulos = data['datos']
            if not isinstance(articulos, list):
                articulos = [articulos]
        
        else:
            return jsonify({'error': 'No se proporcionaron datos válidos'}), 400
        
        if not articulos:
            return jsonify({'error': 'No se pudieron procesar los datos'}), 400
        
        # Procesar y analizar
        resultado = analizador.procesar_datos(articulos)
        
        return jsonify(resultado)
    
    except Exception as e:
        import traceback
        print(f"Error en /api/procesar: {e}")
        print(traceback.format_exc())
        return jsonify({'error': f'Error procesando: {str(e)}'}), 500


@app.route('/api/cargar-pdf', methods=['POST'])
def cargar_pdf():
    """Endpoint para cargar y procesar PDF"""
    try:
        if 'archivo' not in request.files:
            return jsonify({'error': 'No hay archivo'}), 400
        
        archivo = request.files['archivo']
        
        if archivo.filename == '':
            return jsonify({'error': 'Archivo vacío'}), 400
        
        if not archivo.filename.lower().endswith('.pdf'):
            return jsonify({'error': 'Solo se aceptan archivos PDF'}), 400
        
        # Guardar temporalmente
        filename = secure_filename(archivo.filename)
        filepath = app.config['UPLOAD_FOLDER'] / filename
        archivo.save(str(filepath))
        
        # Procesar
        analizador = AnalizadorArticulos()
        articulos = analizador.procesar_pdf(str(filepath))
        
        # Limpiar
        try:
            filepath.unlink()
        except:
            pass
        
        if not articulos:
            return jsonify({'error': 'No se pudieron extraer datos del PDF. Asegúrate que sea un PDF válido con texto.'}), 400
        
        resultado = analizador.procesar_datos(articulos)
        return jsonify(resultado)
    
    except Exception as e:
        return jsonify({'error': f'Error procesando PDF: {str(e)}'}), 500


@app.route('/api/cargar-archivo', methods=['POST'])
def cargar_archivo():
    """Endpoint para cargar CSV/Excel"""
    try:
        if 'archivo' not in request.files:
            return jsonify({'error': 'No hay archivo'}), 400
        
        archivo = request.files['archivo']
        ext = Path(archivo.filename).suffix.lower()
        
        if ext not in ['.csv', '.xlsx', '.xls']:
            return jsonify({'error': 'Formato no soportado'}), 400
        
        # Guardar temporalmente
        filename = secure_filename(archivo.filename)
        filepath = app.config['UPLOAD_FOLDER'] / filename
        archivo.save(str(filepath))
        
        # Procesar
        analizador = AnalizadorArticulos()
        datos = analizador.procesar_csv(str(filepath))
        
        # Limpiar
        filepath.unlink()
        
        resultado = analizador.procesar_datos(datos)
        return jsonify(resultado)
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/health', methods=['GET'])
def health():
    """Health check"""
    return jsonify({'status': 'ok', 'server': 'running'})


@app.route('/api/cargar-excel-local', methods=['GET'])
def cargar_excel_local():
    """Endpoint para procesar el Excel local del servidor"""
    try:
        excel_path = Path(__file__).parent / 'LISTADO_ARTICULOS.xlsx'
        
        if not excel_path.exists():
            return jsonify({'error': 'Archivo LISTADO_ARTICULOS.xlsx no encontrado'}), 404
        
        # Leer Excel
        df = pd.read_excel(str(excel_path))
        
        # Obtener la primera columna (contiene los artículos)
        primera_columna = df.iloc[:, 0]
        
        # Convertir a lista de textos
        lineas = [str(val).strip() for val in primera_columna.values if pd.notna(val) and str(val).strip()]
        
        print(f" Cargado Excel con {len(lineas)} artículos")
        
        # Procesar con el analizador
        analizador = AnalizadorArticulos()
        articulos = []
        
        for linea in lineas:
            if linea and len(linea) > 2:
                articulos.append(analizador._estructurar_articulo_linea(linea))
        
        resultado = analizador.procesar_datos(articulos)
        
        return jsonify(resultado)
    
    except Exception as e:
        import traceback
        print(f"Error: {e}")
        print(traceback.format_exc())
        return jsonify({'error': f'Error: {str(e)}'}), 500


@app.route('/', methods=['GET'])
@app.route('/analizador-articulos.html', methods=['GET'])
def servir_html():
    """Servir el HTML directamente desde Flask"""
    html_path = Path(__file__).parent / 'analizador-articulos.html'
    if html_path.exists():
        with open(html_path, 'r', encoding='utf-8') as f:
            return f.read(), 200, {'Content-Type': 'text/html; charset=utf-8'}
    return 'No encontrado', 404


if __name__ == '__main__':
    print("🚀 Servidor Analizador iniciando...")
    print("📍 http://localhost:5000")
    app.run(debug=False, host='127.0.0.1', port=5000)
