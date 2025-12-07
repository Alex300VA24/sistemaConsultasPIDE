/* ---- MÓDULO DE CREAR USUARIO ---- */

const ModuloCrearUsuario = {
    moduloActualId: null,
    personaIdActual: null,
    modoEdicion: false,
    inicializado: false,

    // Usuario escogido
    usuarioEscogido: {
        id: null,
        personaId: null,
        dni: null,
        login: null,
        nombreCompleto: null,
        modulos: [] // ← Agregar módulos del usuario
    },

    /* ---- INICIALIZACIÓN ---- */
    init() {
        this.setupEventListeners();
        this.cargarDatosIniciales();
        this.switchTab('crearUsuario');
    },

    /* ---- CONFIGURAR EVENT LISTENERS ---- */
    setupEventListeners() {
        // Tabs
        document.querySelectorAll('.user-container .tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab;
                this.switchTab(tab);
            });
        });

        // Formulario de creación
        const form = document.getElementById('formCrearUsuario');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.guardarUsuario();
            });
        }

        // Botón limpiar
        document.getElementById('btnLimpiarUsuario')?.addEventListener('click', () => {
            this.limpiarFormulario();
        });

        // Toggle password
        this.configurarTogglePassword('usuPass', 'togglePasswordCrear');
        this.configurarTogglePassword('usuPassConfirm', 'togglePasswordConfirmCrear');
    },

    configurarTogglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (input && icon) {
            icon.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    },

    switchTab(tabName) {
        if (!['crearUsuario', 'listarUsuarios'].includes(tabName)) return;

        // Actualizar botones
        document.querySelectorAll('.user-container .tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tabName) {
                btn.classList.add('active');
            }
        });

        // Actualizar contenido
        document.querySelectorAll('.user-container .tab-content').forEach(content => {
            content.classList.remove('active');
        });

        const activeTab = document.getElementById(`tab${this.capitalize(tabName)}`);
        if (activeTab) {
            activeTab.classList.add('active');
        }
        

        // Cargar datos según la tab
        if (tabName === 'listarUsuarios') {
            this.cargarListadoUsuarios();
        }
    },

    /* ---- CARGAR DATOS INICIALES ---- */
    async cargarDatosIniciales() {
        try {
            await Promise.all([
                this.cargarRoles(),
                this.cargarTiposDePersonal()
            ]);
        } catch (error) {
            console.error('Error al cargar datos iniciales:', error);
        }
    },

    async cargarRoles() {
        try {
            const response = await api.obtenerRoles();
            const select = document.getElementById('usuPermiso');
            
            if (!select) return;

            select.innerHTML = '<option value="">Seleccionar...</option>';

            if (response.success && response.data) {
                response.data.forEach(rol => {
                    const option = document.createElement('option');
                    option.value = rol.ROL_id;
                    option.textContent = rol.ROL_nombre;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error cargando roles:', error);
            window.mostrarAlerta('No se pudieron cargar los roles.', 'error', 'alertContainerCrearUsuario');
        }
    },

    async cargarTiposDePersonal() {
        try {
            const response = await api.obtenerTipoPersonal();
            const select = document.getElementById('perTipoPersonal');
            
            if (!select) return;

            select.innerHTML = '<option value="">Seleccionar...</option>';

            if (response.success && response.data) {
                response.data.forEach(tipo => {
                    const option = document.createElement('option');
                    option.value = tipo.TPE_id;
                    option.textContent = tipo.TPE_nombre;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error cargando tipos de personal:', error);
            window.mostrarAlerta('No se pudieron cargar los tipos de personal.', 'error', 'alertContainerCrearUsuario');
        }
    },

    /* ---- GUARDAR USUARIO ---- */
    async guardarUsuario() {
        const btn = document.getElementById('btnGuardarUsuario');
        const originalText = btn.innerHTML;

        try {
            // Deshabilitar botón
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> <span>Guardando...</span>';

            // Obtener datos del formulario
            const data = await this.recolectarDatos();

            // Validaciones
            if (!this.validarDatosPersonales(data)) {
                return;
            }
            if (!this.validarDatosUsuario(data)) {
                return;
            }

            let response;
            if (this.modoEdicion && this.moduloActualId) {
                // MODO EDICIÓN
                data.USU_id = this.moduloActualId;
                data.PER_id = this.personaIdActual;

                // Verificar si tiene acceso a RENIEC
                const tieneAccesoRENIEC = this.tieneAccesoRENIEC();
                // Si hay contraseña nueva y tiene acceso a RENIEC, actualizar en RENIEC primero
                if (data.usuPass && data.usuPass.trim() !== '' && tieneAccesoRENIEC) {
                    window.mostrarAlerta(
                        'Actualizando contraseña en RENIEC...',
                        'info',
                        'alertContainerCrearUsuario'
                    );
                    
                    const resultadoRENIEC = await api.actualizarPasswordRENIEC({
                        credencialAnterior: data.usuPassActual,
                        credencialNueva: data.usuPass,
                        nuDni: this.usuarioEscogido.dni
                    });
                    
                    if (!resultadoRENIEC.success) {
                        window.mostrarAlerta(
                            'Error al actualizar contraseña en RENIEC: ' + resultadoRENIEC.message,
                            'error',
                            'alertContainerCrearUsuario'
                        );
                        return;
                    }
                    
                    window.mostrarAlerta(
                        '✓ Contraseña actualizada en RENIEC',
                        'success',
                        'alertContainerCrearUsuario'
                    );
                }
                // Actualizar en base de datos
                window.mostrarAlerta(
                    'Actualizando datos en el sistema...',
                    'info',
                    'alertContainerCrearUsuario'
                );
                response = await api.actualizarUsuario(data);
            } else {
                // MODO CREACIÓN
                response = await api.crearUsuario(data);
            }

            if (response.success) {
                window.mostrarAlerta(
                    response.message || (this.modoEdicion ? 'Usuario actualizado correctamente.' : 'Usuario guardado correctamente.'),
                    'success',
                    'alertContainerCrearUsuario'
                );
                setTimeout(() => {
                    this.limpiarFormulario();
                }, 2000);
                
                // Si estamos en edición, volver a crear
                if (this.modoEdicion) {
                    this.modoEdicion = false;
                    this.moduloActualId = null;
                    this.personaIdActual = null;
                }
            } else {
                window.mostrarAlerta(
                    response.message || 'Error al guardar el usuario.',
                    'error',
                    'alertContainerCrearUsuario'
                );
                
            }
        } catch (error) {
            console.error('Error en guardarUsuario:', error);
            window.mostrarAlerta(
                 error.message|| 'Error de conexión con el servidor.',
                'error',
                'alertContainerCrearUsuario'
            );
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;

            const alerta = document.getElementById('alertContainerCrearUsuario');
            const titulo = document.getElementById("tituloCrearUsuario");
            if (alerta) {
                titulo.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    },

    tieneAccesoRENIEC() {
        const modulosRENIEC = ['DNI', 'PAR'];
        
        // Verificar si el usuario tiene acceso a módulos RENIEC
        const tieneAcceso = this.usuarioEscogido.modulos.some(modulo =>
            modulosRENIEC.includes(modulo) ||
            modulosRENIEC.some(cod => modulo.includes(cod))
        );
        
        return tieneAcceso;
    },

    recolectarDatos() {
        return {
            perTipo: this.getValue('perTipo'),
            perDocumentoTipo: this.getValue('perDocumentoTipo'),
            perDocumentoNum: this.getValue('perDocumentoNum'),
            perNombre: this.getValue('perNombre'),
            perApellidoPat: this.getValue('perApellidoPat'),
            perApellidoMat: this.getValue('perApellidoMat'),
            perSexo: this.getValue('perSexo'),
            perEmail: this.getValue('perEmail'),
            perTipoPersonal: this.getValue('perTipoPersonal'),
            usuUsername: this.getValue('usuLogin'),
            usuPass: this.getValue('usuPass'),
            usuPassConfirm: this.getValue('usuPassConfirm'),
            usuPermiso: this.getValue('usuPermiso'),
            usuEstado: this.getValue('usuEstado'),
            cui: this.getValue('cui')
        };
    },

    validarDatosPersonales(data) {
        if (!data.perTipo || !data.perDocumentoTipo || !data.perDocumentoNum || 
            !data.perNombre || !data.perApellidoPat || !data.perSexo || !data.perTipoPersonal) {
            window.mostrarAlerta(
                'Completa todos los campos personales obligatorios.',
                'warning',
                'alertContainerCrearUsuario'
            );
            return false;
        }
        return true;
    },

    validarDatosUsuario(data) {
        if (!data.usuUsername) {
            window.mostrarAlerta(
                'Completa el usuario',
                'warning',
                'alertContainerCrearUsuario'
            );
            return false;
        }

        // Validar contraseñas según el modo
        if (this.modoEdicion) {
            // En modo edición: contraseña opcional, pero si se llena debe coincidir
            if (data.usuPass || data.usuPassConfirm) {
                if (data.usuPass !== data.usuPassConfirm) {
                    window.mostrarAlerta(
                        'Las contraseñas no coinciden.',
                        'warning',
                        'alertContainerCrearUsuario'
                    );
                    return false;
                }
                
                if (data.usuPass.length < 6) {
                    window.mostrarAlerta(
                        'La contraseña debe tener al menos 6 caracteres.',
                        'warning',
                        'alertContainerCrearUsuario'
                    );
                    return false;
                }
            }
        } else {
            // En modo creación: contraseñas obligatorias
            if (!data.usuPass || !data.usuPassConfirm) {
                window.mostrarAlerta(
                    'Completa los campos de contraseña.',
                    'warning',
                    'alertContainerCrearUsuario'
                );
                return false;
            }

            if (data.usuPass !== data.usuPassConfirm) {
                window.mostrarAlerta(
                    'Las contraseñas no coinciden.',
                    'warning',
                    'alertContainerCrearUsuario'
                );
                return false;
            }
            
            if (data.usuPass.length < 6) {
                window.mostrarAlerta(
                    'La contraseña debe tener al menos 6 caracteres.',
                    'warning',
                    'alertContainerCrearUsuario'
                );
                return false;
            }
        }

        return true;
    },

    async cargarListadoUsuarios() {
        try {
            const response = await api.listarUsuarios();
            const tbody = document.getElementById('tablaUsuariosBody');
            
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Cargando...</td></tr>';

            if (response.success && response.data && response.data.length > 0) {
                tbody.innerHTML = '';
                
                response.data.forEach(usuario => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${usuario.USU_username}</strong></td>
                        <td>${usuario.nombre_completo || ''}</td>
                        <td>${usuario.rol_nombre || 'Sin rol'}</td>
                        <td>
                            <span class="badge ${usuario.USU_estado_id == 1 ? 'badge-success' : 'badge-danger'}">
                                ${usuario.USU_estado_id == 1 ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon btn-edit" onclick="ModuloCrearUsuario.editarUsuario(${usuario.USU_id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-toggle" onclick="ModuloCrearUsuario.toggleEstadoUsuario(${usuario.USU_id}, ${usuario.USU_estado_id})" title="${usuario.USU_estado_id == 1 ? 'Desactivar' : 'Activar'}">
                                    <i class="fas fa-${usuario.USU_estado_id == 1 ? 'eye' : 'eye-slash'}"></i>
                                </button>
                                <button class="btn-icon btn-delete" onclick="ModuloCrearUsuario.eliminarUsuario(${usuario.USU_id})" title="Eliminar">
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
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No hay usuarios registrados</h3>
                            <p>Crea tu primer usuario desde la pestaña "Crear Usuario"</p>
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error('Error al cargar listado de usuarios:', error);
            const tbody = document.getElementById('tablaUsuariosBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; color: red;">
                            Error al cargar los usuarios
                        </td>
                    </tr>
                `;
            }
        }
    },

    async editarUsuario(usuarioId) {
        try {
            const response = await api.obtenerUsuario(usuarioId);
            
            if (response.success && response.data) {
                const usuario = response.data;
                
                // Cambiar a tab de crear
                this.switchTab('crearUsuario');
                
                // Guardar IDs para actualización
                this.moduloActualId = usuario.USU_id;
                this.personaIdActual = usuario.PER_id;

                const listaModulos = usuario.modulos_acceso
                ? usuario.modulos_acceso.split(',').map(m => m.trim())
                : [];

                // Guardar datos del usuario escogido
                this.usuarioEscogido = {
                    id: usuario.USU_id,
                    dni: usuario.PER_documento_numero || null,
                    login: usuario.USU_username || null,
                    nombreCompleto: usuario.nombre_completo || null,
                    modulos: listaModulos
                };
                
                // Llenar el formulario con los datos correctos del backend
                document.getElementById('perTipo').value = String(usuario.PER_tipo_persona ?? '');
                document.getElementById('perDocumentoTipo').value = String(usuario.PER_documento_tipo_id ?? '');
                document.getElementById('perDocumentoNum').value = usuario.PER_documento_numero || '';
                document.getElementById('perNombre').value = usuario.PER_nombres || '';
                document.getElementById('perApellidoPat').value = usuario.PER_apellido_paterno || '';
                document.getElementById('perApellidoMat').value = usuario.PER_apellido_materno || '';
                document.getElementById('perSexo').value = String(usuario.PER_sexo ?? '');
                document.getElementById('perEmail').value = usuario.USU_email || '';
                document.getElementById('perTipoPersonal').value = String(usuario.PER_tipo_personal_id ?? '');
                document.getElementById('usuLogin').value = usuario.USU_username || '';
                document.getElementById('usuPermiso').value = String(usuario.rol_id ?? '');
                document.getElementById('usuEstado').value = String(usuario.PER_estado_id ?? '1');
                document.getElementById('cui').value = usuario.USU_cui || '';
                
                // Limpiar campos de contraseña en edición
                document.getElementById('usuPass').value = '';
                document.getElementById('usuPassConfirm').value = '';
                
                // Cambiar modo a edición
                this.modoEdicion = true;
                
                // Cambiar texto del botón
                const btn = document.getElementById('btnGuardarUsuario');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-save"></i> <span>Actualizar Usuario</span>';
                }
                
                window.mostrarAlerta(
                    'Usuario cargado para edición',
                    'info',
                    'alertContainerCrearUsuario'
                );
            }
        } catch (error) {
            console.error('Error al cargar usuario:', error);
            window.mostrarAlerta(
                'Error al cargar el usuario',
                'error',
                'alertContainerCrearUsuario'
            );
        }
    },

    async toggleEstadoUsuario(usuarioId, estadoActual) {
        const nuevoEstado = estadoActual == 1 ? 0 : 1;
        const accion = nuevoEstado == 1 ? 'activar' : 'desactivar';
        
        if (!confirm(`¿Está seguro que desea ${accion} este usuario?`)) {
            return;
        }
        
        try {
            const response = await api.toggleEstadoUsuario(usuarioId, nuevoEstado);
            
            if (response.success) {
                window.mostrarAlerta(
                    `Usuario ${accion === 'activar' ? 'activado' : 'desactivado'} exitosamente`,
                    'success',
                    'alertContainerCrearUsuario'
                );
                this.cargarListadoUsuarios();
            } else {
                window.mostrarAlerta(
                    response.message || 'Error al cambiar el estado del usuario',
                    'error',
                    'alertContainerCrearUsuario'
                );
            }
        } catch (error) {
            console.error('Error al cambiar estado:', error);
            window.mostrarAlerta(
                'Error al cambiar el estado del usuario',
                'error',
                'alertContainerCrearUsuario'
            );
        }
    },

    async eliminarUsuario(usuarioId) {
        if (!confirm('¿Está seguro que desea eliminar este usuario?\n\nEsta acción no se puede deshacer.')) {
            return;
        }
        
        try {
            const response = await api.eliminarUsuario(usuarioId);
            
            if (response.success) {
                window.mostrarAlerta(
                    'Usuario eliminado exitosamente',
                    'success',
                    'alertContainerCrearUsuario'
                );
                this.cargarListadoUsuarios();
            } else {
                window.mostrarAlerta(
                    response.message || 'Error al eliminar el usuario',
                    'error',
                    'alertContainerCrearUsuario'
                );
            }
        } catch (error) {
            console.error('Error al eliminar usuario:', error);
            window.mostrarAlerta(
                'Error al eliminar el usuario',
                'error',
                'alertContainerCrearUsuario'
            );
        }
    },

    getValue(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    },

    limpiarFormulario() {
        document.getElementById('formCrearUsuario').reset();
        this.modoEdicion = false;
        this.moduloActualId = null;
        this.personaIdActual = null;
        
        const btn = document.getElementById('btnGuardarUsuario');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-save"></i> <span>Guardar Usuario</span>';
        }
        
        // Limpiar alertas
        const alertContainer = document.getElementById('alertContainerCrearUsuario');
        if (alertContainer) {
            alertContainer.innerHTML = '';
        }
    },

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
};

// ============================================
// AUTO-REGISTRO DEL MÓDULO
// ============================================
if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('crearusuario', ModuloCrearUsuario);
}

// Auto-inicializar cuando se cargue el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // No auto-inicializar, esperar a que Dashboard lo llame
    });
}