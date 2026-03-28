const ModuloCrearUsuario = {
    currentId: null,
    currentPersonId: null,
    editMode: false,
    initialized: false,
    selectedUser: { id: null, personId: null, dni: null, login: null, fullName: null, modules: [] },

    init() {
        this.setupEventListeners();
        this.loadInitialData();
        this.switchTab('crearUsuario');
    },

    setupEventListeners() {
        document.querySelectorAll('.user-container .tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab;
                this.switchTab(tab);
            });
        });

        const form = document.getElementById('formCrearUsuario');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveUser();
            });
        }

        document.getElementById('btnLimpiarUsuario')?.addEventListener('click', () => this.clearForm());
        this.setupPasswordToggles();
    },

    setupPasswordToggles() {
        this.togglePasswordVisibility('usuPass', 'togglePasswordCrear');
        this.togglePasswordVisibility('usuPassConfirm', 'togglePasswordConfirmCrear');
    },

    togglePasswordVisibility(inputId, iconId) {
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

        document.querySelectorAll('.user-container .tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });

        document.querySelectorAll('.user-container .tab-content').forEach(content => {
            content.classList.remove('active');
        });

        const activeTab = document.getElementById(`tab${this.capitalize(tabName)}`);
        if (activeTab) activeTab.classList.add('active');

        if (tabName === 'listarUsuarios') {
            this.loadUserList();
            this.clearForm();
        }
    },

    async loadInitialData() {
        try {
            await Promise.all([this.loadRoles(), this.loadPersonalTypes()]);
        } catch (error) {
            console.error('Error loading initial data:', error);
        }
    },

    async loadRoles() {
        try {
            const response = await usuarioService.obtenerRoles();
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
            console.error('Error loading roles:', error);
            Alerts.inline('No se pudieron cargar los roles.', 'error', 'alertContainerCrearUsuario');
        }
    },

    async loadPersonalTypes() {
        try {
            const response = await usuarioService.obtenerTipoPersonal();
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
            console.error('Error loading personal types:', error);
            Alerts.inline('No se pudieron cargar los tipos de personal.', 'error', 'alertContainerCrearUsuario');
        }
    },

    async saveUser() {
        const btn = document.getElementById('btnGuardarUsuario');
        const loader = Loading.button(btn, { text: '<span class="loading"></span> Guardando...' });

        try {
            const data = this.collectData();

            if (!this.validatePersonalData(data)) return;
            if (!this.validateUserData(data)) return;

            let response;
            if (this.editMode && this.currentId) {
                data.USU_id = this.currentId;
                data.PER_id = this.currentPersonId;

                const hasRENIECAccess = this.hasRENIECAccess();
                if (data.usuPass && data.usuPass.trim() !== '' && hasRENIECAccess) {
                    const oldPassword = await this.requestOldPassword();
                    if (!oldPassword) {
                        Alerts.inline('Debe ingresar la contraseña actual para cambiarla', 'warning', 'alertContainerCrearUsuario');
                        return;
                    }

                    Alerts.inline('Actualizando contraseña en RENIEC...', 'info', 'alertContainerCrearUsuario');
                    
                    const reniecResult = await consultaService.actualizarPasswordRENIEC({
                        credencialAnterior: oldPassword,
                        credencialNueva: data.usuPass,
                        nuDni: this.selectedUser.dni
                    });
                    
                    if (!reniecResult.success) {
                        Alerts.inline('Error al actualizar contraseña en RENIEC: ' + reniecResult.message, 'error', 'alertContainerCrearUsuario');
                        return;
                    }
                    
                    Alerts.inline('Contraseña actualizada en RENIEC', 'success', 'alertContainerCrearUsuario');
                }

                Alerts.inline('Actualizando datos en el sistema...', 'info', 'alertContainerCrearUsuario');
                response = await usuarioService.actualizar(data);
            } else {
                response = await usuarioService.crear(data);
            }

            if (response.success) {
                Alerts.inline(response.message || (this.editMode ? 'Usuario actualizado correctamente.' : 'Usuario guardado correctamente.'), 'success', 'alertContainerCrearUsuario');
                setTimeout(() => this.clearForm(), 2000);
                
                if (this.editMode) {
                    this.editMode = false;
                    this.currentId = null;
                    this.currentPersonId = null;
                }
            } else {
                Alerts.inline(response.message || 'Error al guardar el usuario.', 'error', 'alertContainerCrearUsuario');
            }
        } catch (error) {
            console.error('Error in saveUser:', error);
            Alerts.inline(error.message || 'Error de conexión con el servidor.', 'error', 'alertContainerCrearUsuario');
        } finally {
            loader.restore();
            DOM.scrollTo(document.getElementById('alertContainerCrearUsuario'), { behavior: 'smooth' });
        }
    },

    requestOldPassword() {
        return new Promise((resolve) => {
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
                                    <input type="password" id="passwordActualModal" class="form-control-password" placeholder="Ingrese su contraseña actual" autocomplete="current-password">
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

            const styleId = 'modalPasswordStyles';
            if (!document.getElementById(styleId)) {
                const styles = document.createElement('style');
                styles.id = styleId;
                styles.textContent = this.getModalStyles();
                document.head.appendChild(styles);
            }

            const container = document.createElement('div');
            container.innerHTML = modalHTML;
            document.body.appendChild(container.firstElementChild);

            const modal = document.getElementById('modalPasswordActualWindow');
            const input = document.getElementById('passwordActualModal');
            const btnToggle = document.getElementById('btnTogglePasswordWindow');
            const btnCerrar = document.getElementById('btnCerrarModalPassword');
            const btnCancelar = document.getElementById('btnCancelarPassword');
            const btnConfirmar = document.getElementById('btnConfirmarPassword');
            const alertContainer = document.getElementById('alertModalPassword');

            const closeModal = (password = null) => {
                modal.remove();
                resolve(password);
            };

            btnToggle.addEventListener('click', () => {
                input.type = input.type === 'password' ? 'text' : 'password';
            });

            btnConfirmar.addEventListener('click', () => {
                const password = input.value.trim();
                if (!password) {
                    alertContainer.innerHTML = '<div style="color: #f59e0b; padding: 8px; background: #fef3c7; border-radius: 6px;">Por favor, ingrese su contraseña actual.</div>';
                    return;
                }
                closeModal(password);
            });

            btnCerrar.addEventListener('click', () => closeModal());
            btnCancelar.addEventListener('click', () => closeModal());

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            setTimeout(() => input.focus(), 100);
        });
    },

    getModalStyles() {
        return `
            .modal-overlay-password { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999; }
            .modal-content-password { background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); max-width: 500px; width: 90%; }
            .modal-header-password { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
            .modal-header-password h3 { margin: 0; font-size: 1.25rem; font-weight: 600; color: #1f2937; }
            .btn-close-modal-password { background: none; border: none; font-size: 28px; color: #6b7280; cursor: pointer; }
            .modal-body-password { padding: 24px; }
            .modal-description-password { color: #6b7280; margin-bottom: 20px; }
            .form-group-password { margin-bottom: 16px; }
            .form-group-password label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
            .password-input-wrapper { position: relative; display: flex; align-items: center; }
            .password-input-wrapper .form-control-password { flex: 1; padding-right: 50px !important; }
            .form-control-password { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; }
            .form-control-password:focus { outline: none; border-color: #3b82f6; }
            .toggle-password-modal { position: absolute; right: 18px; cursor: pointer; color: #5f6368; }
            .modal-footer-password { padding: 16px 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 12px; }
            .btn-primary-password { background-color: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
            .btn-secondary-password { background-color: #f3f4f6; color: #374151; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        `;
    },

    hasRENIECAccess() {
        const reniecModules = ['DNI', 'PAR'];
        return this.selectedUser.modules.some(mod => reniecModules.includes(mod) || reniecModules.some(c => mod.includes(c)));
    },

    collectData() {
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

    getValue(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    },

    validatePersonalData(data) {
        if (!data.perTipo || !data.perDocumentoTipo || !data.perDocumentoNum || !data.perNombre || !data.perApellidoPat || !data.perSexo || !data.perTipoPersonal) {
            Alerts.inline('Complete todos los campos personales obligatorios.', 'warning', 'alertContainerCrearUsuario');
            return false;
        }
        return true;
    },

    validateUserData(data) {
        if (!data.usuUsername) {
            Alerts.inline('Complete el usuario', 'warning', 'alertContainerCrearUsuario');
            return false;
        }

        if (this.editMode) {
            if (data.usuPass || data.usuPassConfirm) {
                if (data.usuPass !== data.usuPassConfirm) {
                    Alerts.inline('Las contraseñas no coinciden.', 'warning', 'alertContainerCrearUsuario');
                    return false;
                }
                if (data.usuPass.length < 6) {
                    Alerts.inline('La contraseña debe tener al menos 6 caracteres.', 'warning', 'alertContainerCrearUsuario');
                    return false;
                }
            }
        } else {
            if (!data.usuPass || !data.usuPassConfirm) {
                Alerts.inline('Complete los campos de contraseña.', 'warning', 'alertContainerCrearUsuario');
                return false;
            }
            if (data.usuPass !== data.usuPassConfirm) {
                Alerts.inline('Las contraseñas no coinciden.', 'warning', 'alertContainerCrearUsuario');
                return false;
            }
            if (data.usuPass.length < 6) {
                Alerts.inline('La contraseña debe tener al menos 6 caracteres.', 'warning', 'alertContainerCrearUsuario');
                return false;
            }
        }
        return true;
    },

    async loadUserList() {
        try {
            const response = await usuarioService.listar();
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
                        <td><span class="badge ${usuario.USU_estado_id == 1 ? 'badge-success' : 'badge-danger'}">${usuario.USU_estado_id == 1 ? 'Activo' : 'Inactivo'}</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon btn-edit" onclick="ModuloCrearUsuario.editUser(${usuario.USU_id})" title="Editar"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon btn-delete" onclick="ModuloCrearUsuario.deleteUser(${usuario.USU_id})" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><i class="fas fa-inbox"></i><h3>No hay usuarios registrados</h3><p>Crea tu primer usuario</p></td></tr>';
            }
        } catch (error) {
            console.error('Error loading user list:', error);
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Error al cargar los usuarios</td></tr>';
        }
    },

    async editUser(usuarioId) {
        try {
            const response = await usuarioService.obtener(usuarioId);
            if (response.success && response.data) {
                const usuario = response.data;
                this.switchTab('crearUsuario');
                this.currentId = usuario.USU_id;
                this.currentPersonId = usuario.PER_id;

                const moduleList = usuario.modulos_acceso ? usuario.modulos_acceso.split(',').map(m => m.trim()) : [];

                this.selectedUser = {
                    id: usuario.USU_id,
                    dni: usuario.PER_documento_numero || null,
                    login: usuario.USU_username || null,
                    fullName: usuario.nombre_completo || null,
                    modules: moduleList
                };

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
                document.getElementById('usuPass').value = '';
                document.getElementById('usuPassConfirm').value = '';

                this.editMode = true;
                document.getElementById('cui').disabled = true;

                const btn = document.getElementById('btnGuardarUsuario');
                if (btn) btn.innerHTML = '<i class="fas fa-save"></i> <span>Actualizar Usuario</span>';

                Alerts.inline('Usuario cargado para edición', 'info', 'alertContainerCrearUsuario');
            }
        } catch (error) {
            console.error('Error loading user:', error);
            Alerts.inline('Error al cargar el usuario', 'error', 'alertContainerCrearUsuario');
        }
    },

    async deleteUser(usuarioId) {
        if (!confirm('¿Está seguro que desea eliminar este usuario?')) return;

        try {
            const response = await usuarioService.eliminar(usuarioId);
            if (response.success) {
                Alerts.inline('Usuario eliminado exitosamente', 'success', 'alertContainerCrearUsuario');
                this.loadUserList();
            } else {
                Alerts.inline(response.message || 'Error al eliminar el usuario', 'error', 'alertContainerCrearUsuario');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            Alerts.inline('Error al eliminar el usuario', 'error', 'alertContainerCrearUsuario');
        }
    },

    clearForm() {
        document.getElementById('cui').disabled = false;
        document.getElementById('formCrearUsuario').reset();
        this.editMode = false;
        this.currentId = null;
        this.currentPersonId = null;

        const btn = document.getElementById('btnGuardarUsuario');
        if (btn) btn.innerHTML = '<i class="fas fa-save"></i> <span>Guardar Usuario</span>';

        DOM.empty(document.getElementById('alertContainerCrearUsuario'));
    },

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
};

if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('crearusuario', ModuloCrearUsuario);
}

window.ModuloCrearUsuario = ModuloCrearUsuario;
