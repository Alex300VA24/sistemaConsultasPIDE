window.APP_CONFIG = {
    API: {
        // Etapa A: backend actual. Etapa B: cambiar a la URL de Laravel (p.ej. '/api' o 'http://localhost:8000/api').
        BASE_URL: '/MDESistemaPIDE/public/api'
    },
    ROUTES: {
        BASE:       '/MDESistemaPIDE/frontend-pide/',
        LOGIN:      '/MDESistemaPIDE/frontend-pide/login.html',
        DASHBOARD:  '/MDESistemaPIDE/frontend-pide/dashboard.html',
        ASSETS_JS:  '/MDESistemaPIDE/frontend-pide/assets/js/'
    },
    // Mapeo módulo (MOD_url) -> fragmento HTML dentro de pages/
    PAGES: {
        '/consultas/dni':         'pages/consultas/dni.html',
        '/consultas/ruc':         'pages/consultas/ruc.html',
        '/consultas/partidas':    'pages/consultas/partidas.html',
        '/consultas/papeletas':   'pages/consultas/papeletas.html',
        '/sistema/crear-usuario': 'pages/sistema/crearUsuario.html',
        '/sistema/crear-roles':   'pages/sistema/crearRoles.html',
        '/sistema/crear-modulo':  'pages/sistema/crearModulo.html',
        '/sistema/actualizar-pass': 'pages/sistema/actualizarPass.html'
    }
};
