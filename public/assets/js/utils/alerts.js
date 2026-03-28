const AlertConfig = {
    ICONS: {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle',
        danger: 'fa-times-circle',
        noData: 'fa-search-minus'
    },
    COLORS: {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    },
    MODAL: {
        error: {
            icon: 'error',
            confirmColor: '#dc3545',
            iconColor: '#dc3545'
        },
        warning: {
            icon: 'warning',
            confirmColor: '#f59e0b',
            iconColor: '#f59e0b'
        },
        info: {
            icon: 'info',
            confirmColor: '#3b82f6',
            iconColor: '#3b82f6'
        },
        success: {
            icon: 'success',
            confirmColor: '#10b981',
            iconColor: '#10b981'
        }
    },
    ANIMATION: {
        enter: 'slideIn 0.3s ease-out',
        exit: 'slideOut 0.3s ease-out'
    }
};

const Alerts = {
    inline(mensaje, tipo = 'info', contenedorId = 'alertContainer') {
        const alertContainer = document.getElementById(contenedorId);
        
        if (!alertContainer) {
            console.warn('No se encontró el contenedor de alertas:', contenedorId);
            return;
        }
        
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 no está cargado');
            return;
        }
        
        const config = AlertConfig.MODAL[tipo] || AlertConfig.MODAL.info;
        
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo}`;
        alerta.style.cssText = `
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            background-color: ${AlertConfig.COLORS[tipo] || AlertConfig.COLORS.info}15;
            border-left: 4px solid ${AlertConfig.COLORS[tipo] || AlertConfig.COLORS.info};
            display: flex;
            align-items: center;
            gap: 12px;
            animation: ${AlertConfig.ANIMATION.enter};
        `;
        
        alerta.innerHTML = `
            <i class="fas ${AlertConfig.ICONS[tipo]}" style="color: ${AlertConfig.COLORS[tipo] || AlertConfig.COLORS.info}; font-size: 20px;"></i>
            <span style="flex: 1; color: #333;">${mensaje}</span>
            <button class="alert-close" onclick="this.parentElement.remove()" style="
                background: none;
                border: none;
                color: ${AlertConfig.COLORS[tipo] || AlertConfig.COLORS.info};
                cursor: pointer;
                font-size: 18px;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.2s;
            " onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        if (tipo === 'error' || tipo === 'warning') {
            alertContainer.innerHTML = '';
        }
        
        alertContainer.appendChild(alerta);
        
        const timeout = tipo === 'error' ? Constants.UI.ALERT_TIMEOUT.ERROR : Constants.UI.ALERT_TIMEOUT.DEFAULT;
        setTimeout(() => {
            if (alerta.parentElement) {
                alerta.style.animation = AlertConfig.ANIMATION.exit;
                setTimeout(() => alerta.remove(), 300);
            }
        }, timeout);
    },

    modal(titulo, mensaje, tipo = 'error') {
        if (typeof Swal === 'undefined') {
            alert(`${titulo}: ${mensaje}`);
            return Promise.resolve();
        }
        
        const config = AlertConfig.MODAL[tipo] || AlertConfig.MODAL.error;

        return Swal.fire({
            title: titulo,
            html: `<p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">${mensaje}</p>`,
            icon: config.icon,
            iconColor: config.iconColor,
            confirmButtonText: 'Entendido',
            confirmButtonColor: config.confirmColor,
            background: '#ffffff',
            backdrop: 'rgba(0, 0, 0, 0.5)',
            customClass: {
                popup: 'swal-modal-custom',
                title: 'swal-title-custom',
                confirmButton: 'swal-btn-custom'
            },
            didOpen: (popup) => {
                popup.style.borderRadius = '1.25rem';
                popup.style.padding = '2rem';
                popup.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.25)';
                const title = popup.querySelector('.swal2-title');
                if (title) {
                    title.style.fontSize = '1.35rem';
                    title.style.fontWeight = '700';
                    title.style.color = '#1e293b';
                }
                const btn = popup.querySelector('.swal2-confirm');
                if (btn) {
                    btn.style.borderRadius = '0.75rem';
                    btn.style.padding = '0.65rem 2rem';
                    btn.style.fontWeight = '600';
                    btn.style.fontSize = '0.9rem';
                    btn.style.boxShadow = '0 4px 14px 0 rgba(0, 0, 0, 0.15)';
                }
            }
        });
    },

    confirm(titulo, mensaje, tipo = 'warning') {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(confirm(`${titulo}: ${mensaje}`));
        }
        
        const config = AlertConfig.MODAL[tipo] || AlertConfig.MODAL.warning;

        return Swal.fire({
            title: titulo,
            html: `<p style="color: #64748b; font-size: 0.95rem;">${mensaje}</p>`,
            icon: config.icon,
            iconColor: config.iconColor,
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: config.confirmColor,
            cancelButtonColor: '#6b7280',
            background: '#ffffff',
            backdrop: 'rgba(0, 0, 0, 0.5)',
            customClass: {
                popup: 'swal-modal-custom',
                title: 'swal-title-custom'
            },
            didOpen: (popup) => {
                popup.style.borderRadius = '1.25rem';
                popup.style.padding = '2rem';
            }
        });
    },

    success(titulo, mensaje) {
        return this.modal(titulo, mensaje, 'success');
    },

    error(titulo, mensaje) {
        return this.modal(titulo, mensaje, 'error');
    },

    warning(titulo, mensaje) {
        return this.modal(titulo, mensaje, 'warning');
    },

    info(titulo, mensaje) {
        return this.modal(titulo, mensaje, 'info');
    }
};

window.mostrarAlerta = Alerts.inline.bind(Alerts);
window.mostrarAlertaModal = Alerts.modal.bind(Alerts);
window.mostrarConfirmacion = Alerts.confirm.bind(Alerts);
window.Alerts = Alerts;
