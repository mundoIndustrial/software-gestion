export function injectDashboardStyles() {
    if (document.getElementById('operario-dashboard-injected-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'operario-dashboard-injected-styles';
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Estilos para cards completados por costurero */
        .card-completado-costura {
            background-color: #e3f2fd !important;
            border-left: 4px solid #2196f3 !important;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1) !important;
        }

        /* Borde verde para recibos reflectivos */
        .borde-reflectivo {
            border-left: 4px solid #4caf50 !important;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2) !important;
        }

        /* Botones para costureros */
        .btn-completar-costura {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-completar-costura:hover {
            background: linear-gradient(135deg, #1976d2, #1565c0);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }

        .btn-deshacer-costura {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-deshacer-costura:hover {
            background: linear-gradient(135deg, #f57c00, #ef6c00);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }

        /* Badge para estado completado de costura */
        .badge-completado-costura {
            background: #2196f3;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-completado-costura.is-on {
            background: #1976d2;
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
        }

        .badge-estado-completado {
            background: #e3f2fd !important;
            color: #0d47a1 !important;
            border: 1px solid #90caf9 !important;
        }

        /* Posicionamiento especial para mobile */
        .badge-completado-costura.mobile-top-right {
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            font-size: 0.65rem;
            padding: 0.2rem 0.6rem;
            display: none;
        }

        /* Sección del botón ver recibo para mobile */
        .mobile-ver-recibo-section {
            display: none;
            margin: 8px 0;
            text-align: center;
        }

        .mobile-ver-recibo-section .btn-ver-recibos {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Responsive para mobile */
        @media (max-width: 768px) {
            .orden-top {
                position: relative;
            }

            .mobile-ver-recibo-section {
                display: block;
            }

            .badge-completado-costura.mobile-top-right {
                display: inline-block;
            }

            .orden-numero-section .badge-completado-costura:not(.mobile-top-right) {
                display: none;
            }

            .mobile-ver-recibo-section .btn-ver-recibos:not(.vista-costura-mobile) {
                border-radius: 12px !important;
                border: 1px solid #e0e0e0 !important;
                box-shadow: none !important;
            }

            .orden-buttons .btn-ver-recibos:not(.mobile-under-state):not(.vista-costura-mobile) {
                display: none;
            }

            body[data-user-role="vista-costura"] .mobile-ver-recibo-section {
                display: none !important;
            }

            body[data-user-role="vista-costura"] .orden-buttons .btn-ver-recibos {
                display: inline-flex !important;
            }

            .btn-completar-costura,
            .btn-deshacer-costura {
                padding: 0.75rem;
                font-size: 0.8rem;
                min-height: 44px;
                justify-content: center;
            }

            .btn-completar-costura span,
            .btn-deshacer-costura span {
                font-size: 1.2rem;
            }
        }
    `;

    document.head.appendChild(style);
}

