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
            this.limpiarFormulario();
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
                    // Solicitar contraseña actual mediante modal
                    const passwordAnterior = await this.solicitarPasswordActual();
                    
                    if (!passwordAnterior) {
                        window.mostrarAlerta(
                            'Debe ingresar la contraseña actual para cambiarla',
                            'warning',
                            'alertContainerCrearUsuario'
                        );
                        return;
                    }
                    
                    window.mostrarAlerta(
                        'Actualizando contraseña en RENIEC...',
                        'info',
                        'alertContainerCrearUsuario'
                    );
                    
                    const resultadoRENIEC = await api.actualizarPasswordRENIEC({
                        credencialAnterior: passwordAnterior,
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

    // Función para solicitar contraseña actual mediante modal
    solicitarPasswordActual() {
        return new Promise((resolve, reject) => {
            // Crear el modal dinámicamente
            const modalHTML = `
                <div id="modalPasswordActualWindow" class="modal-overlay-password">
                    <div class="modal-content-password">
                        <div class="modal-header-password">
                            <h3>Verificación de Contraseña</h3>
                            <button type="button" class="btn-close-modal-password" id="btnCerrarModalPassword">&times;</button>
                        </div>
                        <div class="modal-body-password">
                            <p class="modal-description-password">Para actualizar la contraseña, primero debe ingresar su contraseña actual.</p>
                            <div class="form-group-password">
                                <label for="passwordActualModal">Contraseña Actual *</label>
                                <div class="password-input-wrapper">
                                    <input 
                                        type="password" 
                                        id="passwordActualModal" 
                                        class="form-control-password" 
                                        placeholder="Ingrese su contraseña actual"
                                        autocomplete="current-password"
                                    >
                                    <i id="btnTogglePasswordWindow" class="fas fa-eye-slash toggle-password-modal"></i>
                                </div>
                            </div>
                            <div id="alertModalPassword" class="alert-container-password"></div>
                        </div>
                        <div class="modal-footer-password">
                            <button type="button" class="btn btn-secondary-password" id="btnCancelarPassword">Cancelar</button>
                            <button type="button" class="btn btn-primary-password" id="btnConfirmarPassword">Confirmar</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar estilos si no existen
            if (!document.getElementById('modalPasswordStyles')) {
                const styles = document.createElement('style');
                styles.id = 'modalPasswordStyles';
                styles.textContent = `
                    .modal-overlay-password {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.5);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 9999;
                        animation: fadeInPassword 0.3s ease;
                    }

                    .modal-content-password {
                        background: white;
                        border-radius: 12px;
                        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
                        max-width: 500px;
                        width: 90%;
                        max-height: 90vh;
                        overflow-y: auto;
                        animation: slideDownPassword 0.3s ease;
                    }

                    .modal-header-password {
                        padding: 20px 24px;
                        border-bottom: 1px solid #e5e7eb;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }

                    .modal-header-password h3 {
                        margin: 0;
                        font-size: 1.25rem;
                        font-weight: 600;
                        color: #1f2937;
                    }

                    .btn-close-modal-password {
                        background: none;
                        border: none;
                        font-size: 28px;
                        color: #6b7280;
                        cursor: pointer;
                        padding: 0;
                        width: 32px;
                        height: 32px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 6px;
                        transition: all 0.2s;
                    }

                    .btn-close-modal-password:hover {
                        background-color: #f3f4f6;
                        color: #1f2937;
                    }

                    .modal-body-password {
                        padding: 24px;
                    }

                    .modal-description-password {
                        color: #6b7280;
                        margin-bottom: 20px;
                        font-size: 0.95rem;
                        line-height: 1.5;
                    }

                    .form-group-password {
                        margin-bottom: 16px;
                    }

                    .form-group-password label {
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 500;
                        color: #374151;
                        font-size: 0.95rem;
                    }

                    .password-input-wrapper {
                        position: relative;
                        display: flex;
                        align-items: center;
                    }

                    .password-input-wrapper .form-control-password {
                        flex: 1;
                        padding-right: 50px !important;
                    }

                    .form-control-password {
                        width: 100%;
                        padding: 10px 12px;
                        border: 1px solid #d1d5db;
                        border-radius: 8px;
                        font-size: 0.95rem;
                        transition: all 0.2s;
                    }

                    .form-control-password:focus {
                        outline: none;
                        border-color: #3b82f6;
                        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                    }

                    .toggle-password-modal {
                        position: absolute;
                        right: 18px;
                        top: 50%;
                        transform: translateY(-50%);
                        cursor: pointer;
                        color: #5f6368;
                        transition: all 0.3s ease;
                        font-size: 1.1rem;
                        z-index: 10;
                    }

                    .toggle-password-modal:hover {
                        color: #3b82f6;
                    }

                    .modal-footer-password {
                        padding: 16px 24px;
                        border-top: 1px solid #e5e7eb;
                        display: flex;
                        justify-content: flex-end;
                        gap: 12px;
                    }

                    .btn {
                        padding: 10px 20px;
                        border: none;
                        border-radius: 8px;
                        font-size: 0.95rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.2s;
                    }

                    .btn-primary-password {
                        background-color: #3b82f6;
                        color: white;
                        padding: 10px 20px;
                        border: none;
                        border-radius: 8px;
                        font-size: 0.95rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.2s;
                    }

                    .btn-primary-password:hover {
                        background-color: #2563eb;
                    }

                    .btn-secondary-password {
                        background-color: #f3f4f6;
                        color: #374151;
                        padding: 10px 20px;
                        border: none;
                        border-radius: 8px;
                        font-size: 0.95rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.2s;
                    }

                    .btn-secondary-password:hover {
                        background-color: #e5e7eb;
                    }

                    .alert-container-password {
                        margin-top: 12px;
                    }

                    @keyframes fadeInPassword {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }

                    @keyframes slideDownPassword {
                        from {
                            transform: translateY(-20px);
                            opacity: 0;
                        }
                        to {
                            transform: translateY(0);
                            opacity: 1;
                        }
                    }
                `;
                document.head.appendChild(styles);
            }
            
            // Insertar modal en el DOM
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHTML;
            document.body.appendChild(modalContainer.firstElementChild);
            
            const modal = document.getElementById('modalPasswordActualWindow');
            const input = document.getElementById('passwordActualModal');
            const btnToggle = document.getElementById('btnTogglePasswordWindow');
            const btnCerrar = document.getElementById('btnCerrarModalPassword');
            const btnCancelar = document.getElementById('btnCancelarPassword');
            const btnConfirmar = document.getElementById('btnConfirmarPassword');
            const alertContainer = document.getElementById('alertModalPassword');
            
            // Función para cerrar modal
            const cerrarModal = (password = null) => {
                modal.remove();
                if (password) {
                    resolve(password);
                } else {
                    resolve(null);
                }
            };
            
            // Toggle visibilidad de contraseña
            btnToggle.addEventListener('click', () => {
                if (input.type === 'password') {
                    input.type = 'text';
                    btnToggle.classList.remove('fa-eye-slash');
                    btnToggle.classList.add('fa-eye');
                } else {
                    input.type = 'password';
                    btnToggle.classList.remove('fa-eye');
                    btnToggle.classList.add('fa-eye-slash');
                }
            });
            
            // Confirmar
            btnConfirmar.addEventListener('click', () => {
                const password = input.value.trim();
                if (!password) {
                    alertContainer.innerHTML = '<div style="color: #f59e0b; padding: 8px; background: #fef3c7; border-radius: 6px; font-size: 0.9rem;">Por favor, ingrese su contraseña actual.</div>';
                    return;
                }
                cerrarModal(password);
            });
            
            // Cerrar modal
            btnCerrar.addEventListener('click', () => cerrarModal());
            btnCancelar.addEventListener('click', () => cerrarModal());
            
            // Cerrar con ESC
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    cerrarModal();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
            
            // Confirmar con Enter
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    btnConfirmar.click();
                }
            });
            
            // Cerrar al hacer clic fuera
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    cerrarModal();
                }
            });
            
            // Enfocar input
            setTimeout(() => input.focus(), 100);
        });
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

                if (this.modoEdicion) {
                    document.getElementById('cui').disabled = true;
                }
                
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
        document.getElementById('cui').disabled = false;
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