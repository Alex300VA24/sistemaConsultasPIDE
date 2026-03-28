const Constants = {
    API: {
        BASE_URL: '/MDESistemaPIDE/public/api',
        ENDPOINTS: {
            LOGIN: '/login',
            LOGOUT: '/logout',
            CSRF_TOKEN: '/csrf-token',
            VALIDAR_CUI: '/validar-cui',
            INICIO: '/inicio',
            USUARIOS: '/usuarios',
            USUARIOS_LISTAR: '/usuarios',
            USUARIOS_OBTENER: '/usuarios/obtener',
            USUARIOS_ACTUAL: '/usuarios/actual',
            USUARIOS_ROL: '/usuarios/rol',
            USUARIOS_TIPO_PERSONAL: '/usuarios/tipo-personal',
            USUARIOS_CAMBIAR_PASS: '/usuarios/cambiar-pass',
            USUARIOS_REGISTRAR: '/usuarios/registrar',
            USUARIOS_ACTUALIZAR: '/usuarios/actualizar',
            USUARIOS_ELIMINAR: '/usuarios/eliminar',
            USUARIOS_OBTENER_DNI_PASS: '/usuarios/obtener-dni-pass',
            ROLES: '/roles',
            ROLES_LISTAR: '/roles',
            ROLES_OBTENER: '/roles/obtener',
            ROLES_CREAR: '/roles/crear',
            ROLES_ACTUALIZAR: '/roles/actualizar',
            ROLES_ELIMINAR: '/roles/eliminar',
            ROLES_MODULOS: '/roles/modulos',
            MODULOS: '/modulos',
            MODULOS_LISTAR: '/modulos',
            MODULOS_OBTENER: '/modulos/obtener',
            MODULOS_REGISTRAR: '/modulos/registrar',
            MODULOS_ACTUALIZAR: '/modulos/actualizar',
            MODULOS_ELIMINAR: '/modulos/eliminar',
            MODULOS_TOGGLE_ESTADO: '/modulos/toggle-estado',
            MODULOS_POR_USUARIO: '/modulos/obtener-port-usuario',
            CONSULTAS_DNI: '/consultas/dni',
            CONSULTAS_RUC: '/consultas/ruc',
            CONSULTAS_BUSCAR_RAZON_SOCIAL: '/buscar-razon-social',
            CONSULTAS_BUSCAR_NATURAL: '/consultas/buscar/natural',
            CONSULTAS_BUSCAR_JURIDICA: '/consultas/buscar/juridica',
            CONSULTAS_OFICINAS: '/consultas/goficinas',
            CONSULTAS_PARTIDAS_LASIRSARP: '/consultas/partidas/lasirsarp',
            CONSULTAS_PARTIDAS_NATURAL: '/consultas/partidas/natural',
            CONSULTAS_PARTIDAS_JURIDICA: '/consultas/partidas/juridica',
            CONSULTAS_DETALLE_PARTIDA: '/consultas/sunarp/cargar-detalle-partida',
            ACTUALIZAR_PASS_RENIEC: '/actualizar-pass-reniec'
        }
    },

    MODULOS: {
        CODIGOS: {
            DNI: 'DNI',
            RUC: 'RUC',
            PARTIDAS: 'PAR',
            USUARIOS: 'USU',
            ROLES: 'ROL',
            MODULOS: 'MOD'
        },
        NOMBRES: {
            DNI: 'ConsultasDni',
            RUC: 'ConsultasRuc',
            PARTIDAS: 'ConsultasPartidas',
            CREAR_USUARIO: 'CrearUsuario',
            ACTUALIZAR_USUARIO: 'ActualizarUsuario',
            CREAR_ROLES: 'CrearRoles',
            CREAR_MODULO: 'CrearModulo',
            ACTUALIZAR_PASSWORD: 'ActualizarPassword'
        }
    },

    UI: {
        ALERT_TIMEOUT: {
            ERROR: 8000,
            DEFAULT: 5000
        },
        MODAL_TIMEOUT: 1000,
        STORAGE_KEYS: {
            PAGINA_ACTIVA: 'paginaActiva',
            MENU_ACTIVO: 'menuActivo',
            USUARIO: 'usuario',
            PERMISOS: 'permisos',
            LOGIN_RECIENTE: 'loginReciente',
            REQUIERE_CAMBIO_PASSWORD: 'requiere_cambio_password',
            DIAS_DESDE_CAMBIO: 'dias_desde_cambio',
            DIAS_RESTANTES: 'dias_restantes',
            CAMBIO_PASSWORD_POSPTO: 'cambio_password_pospuesto_'
        },
        SESSION_KEYS: {
            USUARIO: 'usuario',
            PERMISOS: 'permisos',
            PAGINA_ACTIVA: 'paginaActiva',
            MENU_ACTIVO: 'menuActivo',
            LOGIN_RECIENTE: 'loginReciente',
            REQUIERE_CAMBIO_PASSWORD: 'requiere_cambio_password',
            DIAS_DESDE_CAMBIO: 'dias_desde_cambio',
            DIAS_RESTANTES: 'dias_restantes',
            USUARIO_ID: 'usuarioID'
        }
    },

    VALIDATION: {
        DNI_LENGTH: 8,
        RUC_LENGTH: 11,
        PASSWORD_MIN_LENGTH: 6,
        DOCUMENTO_MIN_LENGTH: 1
    },

    ROUTES: {
        BASE: '/MDESistemaPIDE/public/',
        LOGIN: '/MDESistemaPIDE/public/login',
        DASHBOARD: '/MDESistemaPIDE/public/dashboard'
    }
};

window.Constants = Constants;
