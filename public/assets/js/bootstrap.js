const Bootstrap = {
    scripts: {
        core: [
            'core/constants.js',
            'core/events.js',
            'utils/storage.js',
            'utils/dom.js',
            'utils/validator.js',
            'utils/alerts.js',
            'utils/loading.js',
            'core/api.js'
        ],
        modules: [
            'modules/auth/login.js'
        ]
    },

    async loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = `${this.getBaseUrl()}${src}`;
            script.onload = resolve;
            script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
            document.head.appendChild(script);
        });
    },

    getBaseUrl() {
        return '/MDESistemaPIDE/public/assets/js/';
    },

    async loadCore() {
        console.log('Loading core modules...');
        for (const script of this.scripts.core) {
            try {
                await this.loadScript(script);
            } catch (error) {
                console.error(error);
            }
        }
        console.log('Core modules loaded');
    },

    async loadModules() {
        console.log('Loading application modules...');
        for (const script of this.scripts.modules) {
            try {
                await this.loadScript(script);
            } catch (error) {
                console.error(error);
            }
        }
        console.log('Application modules loaded');
    },

    async init() {
        await this.loadCore();
        await this.loadModules();
        console.log('Application bootstrapped successfully');
    }
};

window.Bootstrap = Bootstrap;
