(function() {
    'use strict';

    const App = {
        isReady: false,

        async init() {
            if (this.isReady) return;
            
            console.log('Initializing MDESistemaPIDE...');
            
            this.initUtilities();
            this.initEventListeners();
            
            this.isReady = true;
            console.log('Application ready');
        },

        initUtilities() {
            if (typeof Loading !== 'undefined') {
                Loading.init();
            }
        },

        initEventListeners() {
            document.addEventListener('DOMContentLoaded', () => {
                this.onDOMReady();
            });
        },

        onDOMReady() {
            if (typeof LoginModule !== 'undefined' && document.getElementById('formLogin')) {
                LoginModule.init();
            }

            if (typeof Dashboard !== 'undefined' && document.querySelector('.page-content')) {
                Dashboard.init();
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => App.init());
    } else {
        App.init();
    }

    window.App = App;
})();
