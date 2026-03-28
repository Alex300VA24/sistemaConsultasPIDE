// Legacy Dashboard wrapper - Redirige al nuevo módulo
// Este archivo se mantiene por compatibilidad
// Por favor usar core/dashboard.js

(function() {
    if (typeof window.Dashboard === 'undefined') {
        console.warn('Dashboard no cargado. Por favor asegurese de cargar core/dashboard.js');
    }
    
    // Exponer Dashboard globalmente para compatibilidad legacy
    window.Dashboard = window.Dashboard || {
        init: function() { console.warn('Dashboard no cargado'); },
        showPage: function() { console.warn('Dashboard no cargado'); }
    };
})();
