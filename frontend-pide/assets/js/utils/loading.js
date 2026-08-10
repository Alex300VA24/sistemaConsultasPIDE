const Loading = {
    overlay: null,

    init() {
        this.overlay = document.getElementById('loadingOverlay');
    },

    show(targetElement = null) {
        if (targetElement) {
            this._showInline(targetElement);
        } else {
            this._showOverlay();
        }
    },

    hide(targetElement = null) {
        if (targetElement) {
            this._hideInline(targetElement);
        } else {
            this._hideOverlay();
        }
    },

    _showOverlay() {
        if (!this.overlay) this.init();
        if (this.overlay) {
            this.overlay.style.display = 'flex';
        }
    },

    _hideOverlay() {
        if (!this.overlay) this.init();
        if (this.overlay) {
            this.overlay.style.display = 'none';
        }
    },

    _showInline(element) {
        if (typeof element === 'string') {
            element = document.getElementById(element);
        }
        if (element) {
            element.dataset.originalDisplay = element.style.display;
            element.style.display = 'flex';
            element.classList.add('loading-active');
        }
    },

    _hideInline(element) {
        if (typeof element === 'string') {
            element = document.getElementById(element);
        }
        if (element) {
            element.style.display = element.dataset.originalDisplay || '';
            element.classList.remove('loading-active');
        }
    },

    button(btn, options = {}) {
        const originalText = btn.innerHTML;
        const originalDisabled = btn.disabled;
        
        btn.disabled = true;
        
        if (options.showSpinner !== false) {
            btn.innerHTML = options.text || '<span class="loading"></span>';
        } else {
            btn.innerHTML = options.text || 'Cargando...';
        }

        return {
            restore() {
                btn.disabled = originalDisabled;
                btn.innerHTML = originalText;
            },
            setText(text) {
                btn.innerHTML = text;
            },
            disable() {
                btn.disabled = true;
            },
            enable() {
                btn.disabled = false;
            }
        };
    },

    withButton(btn, callback, options = {}) {
        const loader = this.button(btn, options);
        return Promise.resolve(callback())
            .finally(() => loader.restore());
    },

    table(tbodyId, message = 'Cargando...') {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return { hide: () => {} };

        const colspan = tbody.closest('table')?.querySelector('thead th')?.colSpan || 1;
        tbody.innerHTML = `<tr><td colspan="${colspan}" style="text-align: center;">${message}</td></tr>`;

        return {
            show(data) {
                tbody.innerHTML = data;
            },
            hide() {
                tbody.innerHTML = '';
            },
            error(message = 'Error al cargar datos') {
                tbody.innerHTML = `<tr><td colspan="${colspan}" style="text-align: center; color: red;">${message}</td></tr>`;
            }
        };
    },

    setOverlay(overlayElement) {
        this.overlay = overlayElement;
    }
};

window.Loading = Loading;
