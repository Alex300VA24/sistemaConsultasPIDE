// ============================================
// 🧩 MÓDULO DE GESTIÓN DE MÓDULOS
// ============================================

const ModuloCrearModulo = {
    moduloActualId: null,
    modoEdicion: false,

    init() {
        console.log('🧩 Inicializando Módulo de Gestión de Módulos...');
        this.setupEventListeners();
        this.cargarModulosPadre();
        this.switchTab('crearModulo');
    },

    setupEventListeners() {
        // Tabs
        document.querySelectorAll('.usuario-container .tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab;
                this.switchTab(tab);
            });
        });

        // Formulario de creación
        const form = document.getElementById('formCrearModulo');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.guardarModulo();
            });
        }

        // Botón limpiar
        document.getElementById('btnLimpiarModulo')?.addEventListener('click', () => {
            this.limpiarFormulario();
        });

        // Auto-convertir código a mayúsculas
        document.getElementById('codigoModulo')?.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();
        });
    },

    switchTab(tabName) {
        if (!['crearModulo','listarModulos'].includes(tabName)) return;
        // Actualizar botones
        document.querySelectorAll('.modulo-container .tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tabName) {
                btn.classList.add('active');
            }
        });

        // Actualizar contenido
        document.querySelectorAll('.modulo-container .tab-content').forEach(content => {
            content.classList.remove('active');
        });

        const activeTab = document.getElementById(`tab${this.capitalize(tabName)}`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Cargar datos según la tab
        if (tabName === 'listarModulos') {
            this.cargarListadoModulos();
        }
    },

    async cargarModulosPadre() {
        try {
            const response = await api.listarModulos();
            const select = document.getElementById('moduloPadre');
            
            if (!select) return;

            // Limpiar opciones anteriores (excepto la primera)
            select.innerHTML = '<option value=""> Sin Padre (Módulo Principal)</option>';

            if (response.success && response.data) {
                // Filtrar solo módulos de nivel 1 y 2 como posibles padres
                const modulosPadre = response.data.filter(m => m.MOD_nivel <= 2);
                
                modulosPadre.forEach(modulo => {
                    const option = document.createElement('option');
                    option.value = modulo.MOD_id;
                    option.textContent = `${modulo.MOD_codigo}  |  ${modulo.MOD_nombre}`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error al cargar módulos padre:', error);
        }
    },

    async guardarModulo() {
        const btn = document.getElementById('btnGuardarModulo');
        const originalText = btn.innerHTML;
        
        try {
            // Deshabilitar botón
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> <span>Guardando...</span>';

            // Obtener datos del formulario
            const formData = new FormData(document.getElementById('formCrearModulo'));
            const data = {
                modulo_padre_id: formData.get('modulo_padre_id') || null,
                codigo: formData.get('codigo'),
                nombre: formData.get('nombre'),
                descripcion: formData.get('descripcion'),
                url: formData.get('url'),
                icono: formData.get('icono'),
                orden: parseInt(formData.get('orden')),
                nivel: parseInt(formData.get('nivel'))
            };

            // Validaciones
            if (!this.validarFormulario(data)) {
                return;
            }

            let response;
            if (this.modoEdicion && this.moduloActualId) {
                data.modulo_id = this.moduloActualId;
                response = await api.actualizarModulo(data);
            } else {
                response = await api.crearModulo(data);
            }

            if (response.success) {
                window.mostrarAlerta(
                    response.message || 'Módulo guardado exitosamente',
                    'success',
                    'alertContainerModulo'
                );

                this.limpiarFormulario();
                this.cargarModulosPadre();
                
                // Recargar el sidebar dinámicamente
                await this.recargarSidebar();
                
                // Si estamos en edición, volver a crear
                if (this.modoEdicion) {
                    this.modoEdicion = false;
                    this.moduloActualId = null;
                }
            } else {
                window.mostrarAlerta(
                    response.message || 'Error al guardar el módulo',
                    'error',
                    'alertContainerModulo'
                );
            }
        } catch (error) {
            console.error('Error al guardar módulo:', error);
            window.mostrarAlerta(
                'Error al guardar el módulo. Por favor, intente nuevamente.',
                'error',
                'alertContainerModulo'
            );
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    },

    validarFormulario(data) {
        // Validar campos requeridos
        if (!data.codigo || !data.nombre || !data.descripcion || !data.url || !data.icono) {
            window.mostrarAlerta(
                'Por favor complete todos los campos obligatorios',
                'warning',
                'alertContainerModulo'
            );
            return false;
        }

        // Validar código (solo letras, números y guiones)
        if (!/^[A-Z0-9_-]+$/.test(data.codigo)) {
            window.mostrarAlerta(
                'El código solo puede contener letras mayúsculas, números, guiones y guiones bajos',
                'warning',
                'alertContainerModulo'
            );
            return false;
        }

        // Validar nivel
        if (data.nivel < 1 || data.nivel > 4) {
            window.mostrarAlerta(
                'El nivel debe estar entre 1 y 4',
                'warning',
                'alertContainerModulo'
            );
            return false;
        }

        // Validar orden
        if (data.orden < 1) {
            window.mostrarAlerta(
                'El orden debe ser mayor a 0',
                'warning',
                'alertContainerModulo'
            );
            return false;
        }

        return true;
    },

    async cargarListadoModulos() {
        try {
            const response = await api.listarModulos();
            const tbody = document.getElementById('tablaModulosBody');
            
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Cargando...</td></tr>';

            if (response.success && response.data && response.data.length > 0) {
                tbody.innerHTML = '';
                
                response.data.forEach(modulo => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${modulo.MOD_codigo}</strong></td>
                        <td>${modulo.MOD_nombre}</td>
                        <td>${modulo.padre_nombre || '<em>SIN PADRE</em>'}</td>
                        <td>
                            <span class="badge badge-info">Nivel ${modulo.MOD_nivel}</span>
                        </td>
                        <td>${modulo.MOD_orden}</td>
                        <td>
                            <span class="badge ${modulo.MOD_activo == 1 ? 'badge-success' : 'badge-danger'}">
                                ${modulo.MOD_activo == 1 ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon btn-edit" onclick="ModuloCrearModulo.editarModulo(${modulo.MOD_id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-toggle" onclick="ModuloCrearModulo.toggleEstadoModulo(${modulo.MOD_id}, ${modulo.MOD_activo})" title="${modulo.MOD_activo == 1 ? 'Desactivar' : 'Activar'}">
                                    <i class="fas fa-${modulo.MOD_activo == 1 ? 'eye' : 'eye-slash'}"></i>
                                </button>
                                <button class="btn-icon btn-delete" onclick="ModuloCrearModulo.eliminarModulo(${modulo.MOD_id})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No hay módulos registrados</h3>
                            <p>Crea tu primer módulo desde la pestaña "Crear Módulo"</p>
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error('Error al cargar listado de módulos:', error);
            const tbody = document.getElementById('tablaModulosBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; color: red;">
                            Error al cargar los módulos
                        </td>
                    </tr>
                `;
            }
        }
    },

    async editarModulo(moduloId) {
        try {
            const response = await api.obtenerModulo(moduloId);
            
            if (response.success && response.data) {
                const modulo = response.data;
                
                // Cambiar a tab de crear
                this.switchTab('crearModulo');
                
                // Llenar el formulario
                document.getElementById('moduloPadre').value = modulo.MOD_padre_id || '';
                document.getElementById('codigoModulo').value = modulo.MOD_codigo;
                document.getElementById('nombreModulo').value = modulo.MOD_nombre;
                document.getElementById('descripcionModulo').value = modulo.MOD_descripcion;
                document.getElementById('urlModulo').value = modulo.MOD_url;
                document.getElementById('iconoModulo').value = modulo.MOD_icono;
                document.getElementById('ordenModulo').value = modulo.MOD_orden;
                document.getElementById('nivelModulo').value = modulo.MOD_nivel;
                
                // Cambiar modo a edición
                this.modoEdicion = true;
                this.moduloActualId = moduloId;
                
                // Cambiar texto del botón
                const btn = document.getElementById('btnGuardarModulo');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-save"></i> <span>Actualizar Módulo</span>';
                }
                
                window.mostrarAlerta(
                    'Módulo cargado para edición',
                    'info',
                    'alertContainerModulo'
                );
            }
        } catch (error) {
            console.error('Error al cargar módulo:', error);
            window.mostrarAlerta(
                'Error al cargar el módulo',
                'error',
                'alertContainerModulo'
            );
        }
    },

    async toggleEstadoModulo(moduloId, estadoActual) {
        const nuevoEstado = estadoActual == 1 ? 0 : 1;
        const accion = nuevoEstado == 1 ? 'activar' : 'desactivar';
        
        if (!confirm(`¿Está seguro que desea ${accion} este módulo?`)) {
            return;
        }
        
        try {
            const response = await api.toggleEstadoModulo(moduloId, nuevoEstado);
            
            if (response.success) {
                window.mostrarAlerta(
                    `Módulo ${accion === 'activar' ? 'activado' : 'desactivado'} exitosamente`,
                    'success',
                    'alertContainerModulo'
                );
                this.cargarListadoModulos();
                await this.recargarSidebar();
            } else {
                window.mostrarAlerta(
                    response.message || 'Error al cambiar el estado del módulo',
                    'error',
                    'alertContainerModulo'
                );
            }
        } catch (error) {
            console.error('Error al cambiar estado:', error);
            window.mostrarAlerta(
                'Error al cambiar el estado del módulo',
                'error',
                'alertContainerModulo'
            );
        }
    },

    async eliminarModulo(moduloId) {
        if (!confirm('¿Está seguro que desea eliminar este módulo?\n\nEsta acción no se puede deshacer.')) {
            return;
        }
        
        try {
            const response = await api.eliminarModulo(moduloId);
            
            if (response.success) {
                window.mostrarAlerta(
                    'Módulo eliminado exitosamente',
                    'success',
                    'alertContainerModulo'
                );
                this.cargarListadoModulos();
                this.cargarModulosPadre();
                await this.recargarSidebar();
            } else {
                window.mostrarAlerta(
                    response.message || 'Error al eliminar el módulo',
                    'error',
                    'alertContainerModulo'
                );
            }
        } catch (error) {
            console.error('Error al eliminar módulo:', error);
            window.mostrarAlerta(
                'Error al eliminar el módulo',
                'error',
                'alertContainerModulo'
            );
        }
    },

    async recargarSidebar() {
        // Aquí recargaríamos la barra de navegación dinámicamente
        // Por ahora, recargamos la página completa
        console.log('🔄 Recargando sidebar...');
        window.location.reload();
    },

    limpiarFormulario() {
        document.getElementById('formCrearModulo').reset();
        this.modoEdicion = false;
        this.moduloActualId = null;
        
        const btn = document.getElementById('btnGuardarModulo');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-save"></i> <span>Guardar Módulo</span>';
        }
        
        // Limpiar alertas
        const alertContainer = document.getElementById('alertContainerModulo');
        if (alertContainer) {
            alertContainer.innerHTML = '';
        }
    },

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
};

// ============================================
// 🔧 AUTO-REGISTRO DEL MÓDULO
// ============================================
if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('crearmodulo', ModuloCrearModulo);
    console.log('✅ crearmodulo registrado en Dashboard');
}

// Auto-inicializar cuando se cargue el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // No auto-inicializar, esperar a que Dashboard lo llame
    });
}