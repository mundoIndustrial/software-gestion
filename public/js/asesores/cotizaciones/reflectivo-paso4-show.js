/**
 * Script para mejorar la interactividad del acordeón de Reflectivo PASO 4
 */

document.addEventListener('DOMContentLoaded', function() {
    // Hacer que los headers del acordeón sean interactivos
    const acordeonHeaders = document.querySelectorAll('[style*="background: linear-gradient(135deg, #3b82f6"]');
    
    acordeonHeaders.forEach(header => {
        // Hacer clickeable
        header.style.cursor = 'pointer';
        
        // Evento mouseenter
        header.addEventListener('mouseenter', function() {
            this.style.background = 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)';
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.3)';
        });
        
        // Evento mouseleave
        header.addEventListener('mouseleave', function() {
            this.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
        
        // Evento click para toggle del acordeón (ya está en onclick del HTML)
        header.addEventListener('click', function() {
            const chevron = this.querySelector('i.fa-chevron-down');
            if (chevron) {
                const contenido = this.parentElement.querySelector('.reflectivo-contenido');
                if (contenido && contenido.style.display === 'none') {
                    chevron.style.transform = 'rotate(180deg)';
                } else {
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        });
    });
    
    // Mejorar imágenes con efectos hover
    const imagenesCotizacion = document.querySelectorAll('.reflectivo-contenido img[src*="cotizaciones"]');
    imagenesCotizacion.forEach(img => {
        const contenedor = img.closest('div[style*="position: relative"]');
        if (contenedor) {
            contenedor.style.cursor = 'pointer';
            contenedor.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
            
            contenedor.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.boxShadow = '0 8px 24px rgba(59, 130, 246, 0.4)';
            });
            
            contenedor.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            });
        }
    });
    
    // Mejorar overlay de imágenes
    const overlays = document.querySelectorAll('.imagen-overlay');
    overlays.forEach(overlay => {
        overlay.style.transition = 'opacity 0.3s ease';
    });
    
    console.log('✅ Script de interactividad PASO 4 cargado correctamente');
});

