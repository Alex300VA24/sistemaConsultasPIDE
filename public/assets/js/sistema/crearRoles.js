// ============================================
// 🎭 MÓDULO DE GESTIÓN DE ROLES
// ============================================

const ModuloRoles = {
    elementos: {},
    inicializado: false,
    
    // Estado del módulo
    modulosDisponibles: [],
    rolEnEdicion: null,
    tabActual: 'crear',

    // ============================================
    // 🚀 INICIALIZACIÓN
    // ============================================
    async init() {
        if (this.inicializado) {
            console.log('ℹ️ Módulo Roles ya está inicializado');
            return;
        }

        console.log('🎭 Inicializando Módulo Gestión de Roles...');
        
        this.cachearElementos();
        this.setupEventListeners();
        await this.cargarModulos();
        
        this.inicializado = true;
        console.log('✅ Módulo Roles inicializado correctamente');
    },

    // ============================================
    // 📦 CACHEAR ELEMENTOS DEL DOM
    // ============================================
    cachearElementos() {
        this.elementos = {
            // Tabs
            tabCrear: document.getElementById('tab-crear'),
            tabListar: document.getElementById('tab-listar'),
            
            // Formulario
            rolCodigo: document.getElementById('rolCodigo'),
            rolNombre: document.getElementById('rolNombre'),
            rolNivel: document.getElementById('rolNivel'),
            rolDescripcion: document.getElementById('rolDescripcion'),
            
            // Contenedores
            modulosContainer: document.getElementById('modulosContainer'),
            alertContainer: document.getElementById('alertContainerRoles'),
            tablaRoles: document.querySelector('#tablaRoles tbody')
        };
    },

    // ============================================
    // 🎯 CONFIGURAR EVENT LISTENERS
    // ============================================
    setupEventListeners() {
        // Los event listeners para tabs y botones se manejan desde HTML
        // o se pueden agregar aquí si lo prefieres
        console.log('✓ Event listeners configurados');
    },

    // ============================================
    // 📦 CARGAR MÓDULOS DISPONIBLES
    // ============================================
    async cargarModulos() {
        try {
            console.log('📥 Cargando módulos...');
            const response = await api.listarModulos();
            
            if (response.success && response.data) {
                this.modulosDisponibles = response.data;
                console.log('✅ Módulos cargados:', this.modulosDisponibles.length);
                this.renderizarModulos();
            } else {
                console.error('❌ Error al cargar módulos:', response.message);
                mostrarAlerta('Error al cargar módulos', 'error', 'alertContainerRoles');
            }
        } catch (error) {
            console.error('❌ Error al cargar módulos:', error);
            mostrarAlerta('Error de conexión al cargar módulos', 'error', 'alertContainerRoles');
        }
    },

    // ============================================
    // 🎨 RENDERIZAR MÓDULOS CON JERARQUÍA
    // ============================================
    renderizarModulos() {
        const container = this.elementos.modulosContainer;
        
        if (!container) {
            console.error('❌ Contenedor #modulosContainer no encontrado');
            return;
        }
        
        container.innerHTML = '';
        
        if (!this.modulosDisponibles || this.modulosDisponibles.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                    <i class="fas fa-puzzle-piece" style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px; display: block;"></i>
                    <p style="font-size: 14px; margin: 0;">No hay módulos disponibles</p>
                </div>
            `;
            return;
        }
        
        console.log('📦 Total módulos:', this.modulosDisponibles.length);
        
        // Organizar módulos por jerarquía
        const modulosPadre = this.modulosDisponibles.filter(m => 
            !m.MOD_padre_id || m.MOD_padre_id === null || m.MOD_padre_id === 0
        );
        
        console.log('👨 Módulos padre encontrados:', modulosPadre.length);
        
        if (modulosPadre.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                    <p>Todos los módulos tienen padre asignado</p>
                </div>
            `;
            return;
        }
        
        modulosPadre.forEach((padre, index) => {
            const moduloPadreDiv = this.crearElementoPadre(padre, index);
            container.appendChild(moduloPadreDiv);
        });
        
        console.log('✅ Módulos renderizados correctamente');
    },

    // ============================================
    // 🏗️ CREAR ELEMENTO PADRE
    // ============================================
    crearElementoPadre(padre, index) {
        const moduloPadreDiv = document.createElement('div');
        moduloPadreDiv.className = 'modulo-padre';
        moduloPadreDiv.style.animationDelay = `${index * 0.05}s`;
        
        // Header del módulo padre
        const headerDiv = document.createElement('div');
        headerDiv.className = 'modulo-padre-header';
        
        // Checkbox del padre
        const checkboxPadre = document.createElement('input');
        checkboxPadre.type = 'checkbox';
        checkboxPadre.value = padre.MOD_id;
        checkboxPadre.className = 'checkbox-padre';
        checkboxPadre.id = `modulo-${padre.MOD_id}`;
        checkboxPadre.onchange = () => this.toggleModuloPadre(checkboxPadre, padre.MOD_id);
        
        // Label del padre
        const labelPadre = document.createElement('label');
        labelPadre.htmlFor = `modulo-${padre.MOD_id}`;
        
        const iconoClase = padre.MOD_icono && padre.MOD_icono.startsWith('fa-') 
            ? `fas ${padre.MOD_icono}` 
            : 'fas fa-cube';
        
        labelPadre.innerHTML = `
            <i class="${iconoClase}"></i>
            <div class="modulo-info">
                <strong>${padre.MOD_nombre || 'Sin nombre'}</strong>
                <small>${padre.MOD_codigo || ''}</small>
            </div>
        `;
        
        headerDiv.appendChild(checkboxPadre);
        headerDiv.appendChild(labelPadre);
        moduloPadreDiv.appendChild(headerDiv);
        
        // Agregar hijos si existen
        const hijos = this.modulosDisponibles.filter(m => m.MOD_padre_id === padre.MOD_id);
        
        console.log(`👶 Padre "${padre.MOD_nombre}" tiene ${hijos.length} hijos`);
        
        if (hijos.length > 0) {
            const hijosContainer = this.crearContenedorHijos(padre.MOD_id, hijos, index);
            moduloPadreDiv.appendChild(hijosContainer);
        }
        
        return moduloPadreDiv;
    },

    // ============================================
    // 🏗️ CREAR CONTENEDOR DE HIJOS
    // ============================================
    crearContenedorHijos(padreId, hijos, indexPadre) {
        const hijosContainer = document.createElement('div');
        hijosContainer.className = 'modulo-hijos';
        hijosContainer.id = `hijos-${padreId}`;
        
        hijos.forEach((hijo, hijoIndex) => {
            const hijoDiv = document.createElement('div');
            hijoDiv.className = 'modulo-hijo';
            hijoDiv.style.animationDelay = `${(indexPadre * 0.05) + (hijoIndex * 0.03)}s`;
            
            const checkboxHijo = document.createElement('input');
            checkboxHijo.type = 'checkbox';
            checkboxHijo.value = hijo.MOD_id;
            checkboxHijo.className = 'checkbox-hijo';
            checkboxHijo.id = `modulo-${hijo.MOD_id}`;
            checkboxHijo.dataset.padre = padreId;
            checkboxHijo.onchange = () => this.toggleModuloHijo(checkboxHijo, padreId);
            
            const labelHijo = document.createElement('label');
            labelHijo.htmlFor = `modulo-${hijo.MOD_id}`;
            
            const iconoHijoClase = hijo.MOD_icono && hijo.MOD_icono.startsWith('fa-') 
                ? `fas ${hijo.MOD_icono}` 
                : 'fas fa-circle';
            
            labelHijo.innerHTML = `
                <i class="${iconoHijoClase}"></i>
                <div class="modulo-info">
                    <strong>${hijo.MOD_nombre || 'Sin nombre'}</strong>
                    <small>${hijo.MOD_codigo || ''}</small>
                    ${hijo.MOD_descripcion ? `<span class="modulo-desc">${hijo.MOD_descripcion}</span>` : ''}
                </div>
            `;
            
            hijoDiv.appendChild(checkboxHijo);
            hijoDiv.appendChild(labelHijo);
            hijosContainer.appendChild(hijoDiv);
        });
        
        return hijosContainer;
    },

    // ============================================
    // 🔄 TOGGLE MÓDULO PADRE
    // ============================================
    toggleModuloPadre(checkbox, padreId) {
        const hijosContainer = document.getElementById(`hijos-${padreId}`);
        
        if (hijosContainer) {
            const checkboxesHijos = hijosContainer.querySelectorAll('.checkbox-hijo');
            
            // Marcar/desmarcar todos los hijos
            checkboxesHijos.forEach(ch => {
                ch.checked = checkbox.checked;
            });
            
            // Quitar estado indeterminado
            checkbox.indeterminate = false;
            
            console.log(`${checkbox.checked ? '✅' : '❌'} Padre ${padreId}: ${checkboxesHijos.length} hijos ${checkbox.checked ? 'marcados' : 'desmarcados'}`);
        }
    },

    // ============================================
    // 🔄 TOGGLE MÓDULO HIJO
    // ============================================
    toggleModuloHijo(checkbox, padreId) {
        const checkboxPadre = document.getElementById(`modulo-${padreId}`);
        const hijosContainer = document.getElementById(`hijos-${padreId}`);
        
        if (hijosContainer && checkboxPadre) {
            const checkboxesHijos = hijosContainer.querySelectorAll('.checkbox-hijo');
            const todosMarcados = Array.from(checkboxesHijos).every(ch => ch.checked);
            const algunoMarcado = Array.from(checkboxesHijos).some(ch => ch.checked);
            
            // Lógica del padre según el estado de los hijos
            if (todosMarcados) {
                checkboxPadre.checked = true;
                checkboxPadre.indeterminate = false;
                console.log(`✅ Todos los hijos del padre ${padreId} marcados`);
            } else if (algunoMarcado) {
                checkboxPadre.checked = false;
                checkboxPadre.indeterminate = true;
                console.log(`⚠️ Algunos hijos del padre ${padreId} marcados (indeterminado)`);
            } else {
                checkboxPadre.checked = false;
                checkboxPadre.indeterminate = false;
                console.log(`❌ Ningún hijo del padre ${padreId} marcado`);
            }
        }
    },

    // ============================================
    // 💾 GUARDAR ROL
    // ============================================
    async guardarRol() {
        const codigo = this.elementos.rolCodigo.value.trim();
        const nombre = this.elementos.rolNombre.value.trim();
        const nivel = this.elementos.rolNivel.value;
        const descripcion = this.elementos.rolDescripcion.value.trim();
        
        // Validaciones
        if (!codigo || !nombre) {
            mostrarAlerta('Complete los campos obligatorios (Código y Nombre)', 'warning', 'alertContainerRoles');
            return;
        }
        
        // Obtener módulos seleccionados
        // Obtener módulos seleccionados (hijos y padres)
        const modulosSeleccionados = [];
        const checkboxesHijos = document.querySelectorAll('#modulosContainer .checkbox-hijo');
        const checkboxesPadres = document.querySelectorAll('#modulosContainer .checkbox-padre');

        // 1. Agregar hijos marcados y sus padres obligatorios
        checkboxesHijos.forEach(ch => {
            if (ch.checked) {
                const idHijo = parseInt(ch.value);
                const idPadre = parseInt(ch.dataset.padre);

                // Agrega el hijo
                modulosSeleccionados.push(idHijo);

                // Si el padre existe y no está añadido aún → añadirlo
                if (idPadre && !modulosSeleccionados.includes(idPadre)) {
                    modulosSeleccionados.push(idPadre);
                }
            }
        });

        // 2. Agregar padres marcados manualmente
        checkboxesPadres.forEach(cp => {
            if (cp.checked) {
                const idPadre = parseInt(cp.value);
                if (!modulosSeleccionados.includes(idPadre)) {
                    modulosSeleccionados.push(idPadre);
                }
            }
        });

        
        if (modulosSeleccionados.length === 0) {
            mostrarAlerta('Debe seleccionar al menos un módulo', 'warning', 'alertContainerRoles');
            return;
        }
        
        console.log('📦 Módulos seleccionados:', modulosSeleccionados);
        
        try {
            const data = {
                codigo,
                nombre,
                nivel: parseInt(nivel),
                descripcion,
                modulos: modulosSeleccionados
            };
            
            console.log('📤 Enviando datos:', data);
            
            const response = this.rolEnEdicion 
                ? await api.actualizarRol({ ...data, rol_id: this.rolEnEdicion })
                : await api.crearRol(data);
            
            if (response.success) {
                
                const alerta = document.getElementById('alertContainerRoles');
                const titulo = document.getElementById('tituloRoles');
                if (alerta) {
                    titulo.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                mostrarAlerta('Rol guardado exitosamente', 'success', 'alertContainerRoles');
                
                setTimeout(() => {
                    this.limpiarFormulario();
                }, 3000);
                // Limpiar formulario
                
                // Recargar roles si estamos en la pestaña de lista
                const tabListar = document.querySelector('.tab-btn:nth-child(2)');
                if (tabListar && tabListar.classList.contains('active')) {
                    this.cargarRoles();
                }
            } else {
                mostrarAlerta(response.message || 'Error al guardar el rol', 'error', 'alertContainerRoles');
            }
        } catch (error) {
            console.error('❌ Error al guardar rol:', error);
            mostrarAlerta(error.message || 'Error al guardar el rol', 'error', 'alertContainerRoles');
        }
    },

    // ============================================
    // 📋 CARGAR ROLES
    // ============================================
    async cargarRoles() {
        try {
            console.log('📥 Cargando roles...');
            const response = await api.listarRoles();
            
            if (response.success && response.data) {
                console.log('✅ Roles cargados:', response.data.length);
                this.renderizarTablaRoles(response.data);
            } else {
                console.error('❌ Error al cargar roles');
                mostrarAlerta('Error al cargar roles', 'danger', 'alertContainerRoles');
            }
        } catch (error) {
            console.error('❌ Error al cargar roles:', error);
            mostrarAlerta('Error de conexión al cargar roles', 'danger', 'alertContainerRoles');
        }
    },

    // ============================================
    // 🎨 RENDERIZAR TABLA DE ROLES
    // ============================================
    renderizarTablaRoles(roles) {
        const tbody = this.elementos.tablaRoles;
        
        if (!tbody) {
            console.error('❌ Tbody de tabla no encontrado');
            return;
        }
        
        tbody.innerHTML = '';
        
        if (roles.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No hay roles registrados</h3>
                        <p>Crea el primer rol desde la pestaña "Crear Rol"</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        roles.forEach(rol => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${rol.ROL_id}</td>
                <td><strong>${rol.ROL_codigo}</strong></td>
                <td>${rol.ROL_nombre}</td>
                <td>${rol.ROL_nivel}</td>
                <td>${rol.TOTAL_USUARIOS || 0}</td>
                <td><small>${rol.MODULOS_NOMBRES || 'Sin módulos'}</small></td>
                <td>
                    <span class="badge ${rol.ROL_activo ? 'badge-success' : 'badge-danger'}">
                        ${rol.ROL_activo ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td class="action-btns">
                    <button onclick="ModuloRoles.editarRol(${rol.ROL_id})" class="btn-icon btn-edit" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="ModuloRoles.eliminarRol(${rol.ROL_id})" class="btn-icon btn-delete" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    // ============================================
    // ✏️ EDITAR ROL
    // ============================================
    async editarRol(rolId) {
        try {
            console.log('✏️ Editando rol:', rolId);
            
            // Cambiar a la pestaña de creación
            this.cambiarTab('crear');
            
            // Esperar que el DOM se actualice
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const response = await api.obtenerRol(rolId);
            
            if (response.success && response.data) {
                const rol = response.data;
                
                console.log('📄 Datos del rol:', rol);
                console.log('📦 Módulos del rol:', rol.modulos);
                
                // Llenar campos del formulario
                this.elementos.rolCodigo.value = rol.ROL_codigo || '';
                this.elementos.rolNombre.value = rol.ROL_nombre || '';
                this.elementos.rolNivel.value = rol.ROL_nivel || 1;
                this.elementos.rolDescripcion.value = rol.ROL_descripcion || '';
                
                // Desmarcar todos los checkboxes
                document.querySelectorAll('#modulosContainer input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                    cb.indeterminate = false;
                });
                
                console.log('🧹 Todos los checkboxes desmarcados');
                
                // Marcar módulos seleccionados
                if (rol.modulos && Array.isArray(rol.modulos) && rol.modulos.length > 0) {
                    console.log('🔍 Procesando módulos:', rol.modulos);
                    
                    let modulosMarcados = 0;
                    
                    rol.modulos.forEach(moduloId => {
                        const checkbox = document.getElementById(`modulo-${moduloId}`);
                        
                        if (checkbox) {
                            checkbox.checked = true;
                            modulosMarcados++;
                            console.log(`✅ Marcado módulo ID: ${moduloId}`);
                            
                            // Si es un checkbox hijo, actualizar el estado del padre
                            if (checkbox.classList.contains('checkbox-hijo')) {
                                const padreId = parseInt(checkbox.dataset.padre);
                                if (padreId) {
                                    setTimeout(() => {
                                        this.actualizarEstadoPadre(padreId);
                                    }, 50);
                                }
                            }
                        } else {
                            console.warn(`⚠️ No se encontró checkbox para módulo ID: ${moduloId}`);
                        }
                    });
                    
                    console.log(`✅ Total módulos marcados: ${modulosMarcados} de ${rol.modulos.length}`);
                    
                    // Actualizar estado de todos los padres
                    setTimeout(() => {
                        this.actualizarTodosLosPadres();
                    }, 100);
                } else {
                    console.warn('⚠️ No hay módulos para marcar');
                }
                
                // Establecer modo edición
                this.rolEnEdicion = rolId;
                
                // Mostrar alerta de edición
                mostrarAlerta(`Editando rol: ${rol.ROL_nombre}`, 'info', 'alertContainerRoles');
                
                // Scroll al inicio del formulario
                setTimeout(() => {
                    const formulario = document.getElementById('tab-crear');
                    if (formulario) {
                        formulario.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 200);
            }
        } catch (error) {
            console.error('❌ Error al cargar rol:', error);
            mostrarAlerta('Error al cargar rol para edición', 'danger', 'alertContainerRoles');
        }
    },

    // ============================================
    // 🔄 ACTUALIZAR ESTADO DE UN PADRE
    // ============================================
    actualizarEstadoPadre(padreId) {
        const checkboxPadre = document.getElementById(`modulo-${padreId}`);
        const hijosContainer = document.getElementById(`hijos-${padreId}`);
        
        if (hijosContainer && checkboxPadre) {
            const checkboxesHijos = hijosContainer.querySelectorAll('.checkbox-hijo');
            const todosMarcados = Array.from(checkboxesHijos).every(ch => ch.checked);
            const algunoMarcado = Array.from(checkboxesHijos).some(ch => ch.checked);
            
            if (todosMarcados && checkboxesHijos.length > 0) {
                checkboxPadre.checked = true;
                checkboxPadre.indeterminate = false;
                console.log(`✅ Padre ${padreId}: Todos los hijos marcados`);
            } else if (algunoMarcado) {
                checkboxPadre.checked = false;
                checkboxPadre.indeterminate = true;
                console.log(`⚠️ Padre ${padreId}: Algunos hijos marcados (indeterminado)`);
            } else {
                checkboxPadre.checked = false;
                checkboxPadre.indeterminate = false;
                console.log(`❌ Padre ${padreId}: Ningún hijo marcado`);
            }
        }
    },

    // ============================================
    // 🔄 ACTUALIZAR TODOS LOS PADRES
    // ============================================
    actualizarTodosLosPadres() {
        console.log('🔄 Actualizando estado de todos los padres...');
        
        const checkboxesPadre = document.querySelectorAll('.checkbox-padre');
        
        checkboxesPadre.forEach(checkboxPadre => {
            const padreId = parseInt(checkboxPadre.value);
            this.actualizarEstadoPadre(padreId);
        });
        
        console.log('✅ Estado de todos los padres actualizado');
    },

    // ============================================
    // 🗑️ ELIMINAR ROL
    // ============================================
    async eliminarRol(rolId) {
        if (!confirm('¿Está seguro de eliminar este rol? Esta acción no se puede deshacer.')) {
            return;
        }
        
        try {
            console.log('🗑️ Eliminando rol:', rolId);
            const response = await api.eliminarRol(rolId);
            
            if (response.success) {
                mostrarAlerta(response.message || 'Rol eliminado exitosamente', 'success', 'alertContainerRoles');
                this.cargarRoles();
            } else {
                mostrarAlerta(response.message || 'Error al eliminar rol', 'danger', 'alertContainerRoles');
            }
        } catch (error) {
            console.error('❌ Error al eliminar rol:', error);
            mostrarAlerta(error.message || 'Error al eliminar rol', 'danger', 'alertContainerRoles');
        }
    },

    // ============================================
    // 📑 CAMBIAR TAB
    // ============================================
    cambiarTab(tab) {
        console.log('📑 Cambiando a tab:', tab);
        
        this.tabActual = tab;
        
        // Remover clases activas
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Activar tab correspondiente
        if (tab === 'crear') {
            document.querySelector('.tab-btn:first-child').classList.add('active');
            document.getElementById('tab-crear').classList.add('active');
        } else if (tab === 'listar') {
            document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
            document.getElementById('tab-listar').classList.add('active');
            this.cargarRoles();
        }
    },

    // ============================================
    // 🧹 LIMPIAR FORMULARIO
    // ============================================
    limpiarFormulario() {
        console.log('🧹 Limpiando formulario...');
        
        // Limpiar campos de texto
        this.elementos.rolCodigo.value = '';
        this.elementos.rolNombre.value = '';
        this.elementos.rolNivel.value = '1';
        
        if (this.elementos.rolDescripcion) {
            this.elementos.rolDescripcion.value = '';
        }
        
        // Desmarcar todos los checkboxes
        document.querySelectorAll('#modulosContainer input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
            cb.indeterminate = false;
        });
        
        // Limpiar alertas
        if (this.elementos.alertContainer) {
            this.elementos.alertContainer.innerHTML = '';
        }
        
        // Resetear modo edición
        this.rolEnEdicion = null;
        
        console.log('✅ Formulario limpiado');
    }
};

// ============================================
// 🌐 FUNCIONES GLOBALES
// ============================================
window.guardarRol = async function() {
    if (ModuloRoles.inicializado) {
        await ModuloRoles.guardarRol();
    } else {
        console.warn('⚠️ Módulo Roles no está inicializado');
    }
};

window.cambiarTab = function(tab) {
    if (ModuloRoles.inicializado) {
        ModuloRoles.cambiarTab(tab);
    } else {
        console.warn('⚠️ Módulo Roles no está inicializado');
    }
};

window.limpiarFormulario = function() {
    if (ModuloRoles.inicializado) {
        ModuloRoles.limpiarFormulario();
    }
};
