// Legacy API wrapper - Redirige al nuevo módulo
// Este archivo se mantiene por compatibilidad con otras partes del código
// Por favor usar los nuevos servicios en core/api.js

(function() {
    if (typeof window.api === 'undefined') {
        console.warn('API no cargada. Por favor asegurese de cargar core/api.js');
    }
    
    // Exponer api globalmente para compatibilidad legacy
    window.api = window.api || {
        login: function() { console.error('API no cargada'); return Promise.reject('API no cargada'); },
        get: function() { console.error('API no cargada'); return Promise.reject('API no cargada'); },
        post: function() { console.error('API no cargada'); return Promise.reject('API no cargada'); }
    };
})();
