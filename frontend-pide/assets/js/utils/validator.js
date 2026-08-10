const Validator = {
    isDNI(value) {
        return /^\d{8}$/.test(value);
    },

    isRUC(value) {
        return /^\d{11}$/.test(value);
    },

    isEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    },

    isPhone(value) {
        return /^\d{7,9}$/.test(value);
    },

    isRequired(value) {
        if (typeof value === 'string') return value.trim().length > 0;
        return value !== null && value !== undefined;
    },

    minLength(value, min) {
        return value && value.length >= min;
    },

    maxLength(value, max) {
        return value && value.length <= max;
    },

    matches(value, otherValue) {
        return value === otherValue;
    },

    isNumber(value) {
        return !isNaN(parseFloat(value)) && isFinite(value);
    },

    isInteger(value) {
        return Number.isInteger(Number(value));
    },

    isPositive(value) {
        return this.isNumber(value) && Number(value) > 0;
    },

    isAlpha(value) {
        return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(value);
    },

    isAlphanumeric(value) {
        return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]+$/.test(value);
    },

    validateDNI(dni, alertContainer = 'alertContainer') {
        if (!this.isRequired(dni)) {
            window.mostrarAlerta('El DNI es requerido', 'warning', alertContainer);
            return false;
        }
        if (!this.isDNI(dni)) {
            window.mostrarAlerta('El DNI debe tener 8 dígitos', 'warning', alertContainer);
            return false;
        }
        return true;
    },

    validateRUC(ruc, alertContainer = 'alertContainer') {
        if (!this.isRequired(ruc)) {
            window.mostrarAlerta('El RUC es requerido', 'warning', alertContainer);
            return false;
        }
        if (!this.isRUC(ruc)) {
            window.mostrarAlerta('El RUC debe tener 11 dígitos', 'warning', alertContainer);
            return false;
        }
        return true;
    },

    validateEmail(email, alertContainer = 'alertContainer') {
        if (!this.isRequired(email)) {
            window.mostrarAlerta('El email es requerido', 'warning', alertContainer);
            return false;
        }
        if (!this.isEmail(email)) {
            window.mostrarAlerta('Ingrese un email válido', 'warning', alertContainer);
            return false;
        }
        return true;
    },

    validatePassword(password, minLength = 6, alertContainer = 'alertContainer') {
        if (!this.isRequired(password)) {
            window.mostrarAlerta('La contraseña es requerida', 'warning', alertContainer);
            return false;
        }
        if (!this.minLength(password, minLength)) {
            window.mostrarAlerta(`La contraseña debe tener al menos ${minLength} caracteres`, 'warning', alertContainer);
            return false;
        }
        return true;
    },

    validatePasswordMatch(password, confirmPassword, alertContainer = 'alertContainer') {
        if (!this.matches(password, confirmPassword)) {
            window.mostrarAlerta('Las contraseñas no coinciden', 'warning', alertContainer);
            return false;
        }
        return true;
    },

    validateRequired(data, fields, alertContainer = 'alertContainer') {
        const missing = [];
        for (const field of fields) {
            if (!this.isRequired(data[field])) {
                missing.push(field);
            }
        }
        if (missing.length > 0) {
            window.mostrarAlerta(`Complete los campos requeridos: ${missing.join(', ')}`, 'warning', alertContainer);
            return false;
        }
        return true;
    },

    sanitizeInput(value) {
        if (typeof value !== 'string') return value;
        return value
            .replace(/[<>]/g, '')
            .trim();
    },

    sanitizeNumber(value) {
        return value.replace(/[^0-9]/g, '');
    },

    sanitizeAlpha(value) {
        return value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
    },

    validateForm(formId, rules) {
        const form = document.getElementById(formId);
        if (!form) return false;

        const data = {};
        const errors = [];

        for (const [field, validations] of Object.entries(rules)) {
            const input = form.querySelector(`[name="${field}"]`) || document.getElementById(field);
            if (!input) continue;

            data[field] = input.value.trim();

            for (const rule of validations) {
                const { type, message, ...params } = rule;
                let isValid = true;

                switch (type) {
                    case 'required':
                        isValid = this.isRequired(data[field]);
                        break;
                    case 'dni':
                        isValid = data[field].length === 8 && this.isNumber(data[field]);
                        break;
                    case 'ruc':
                        isValid = data[field].length === 11 && this.isNumber(data[field]);
                        break;
                    case 'email':
                        isValid = this.isEmail(data[field]);
                        break;
                    case 'minLength':
                        isValid = this.minLength(data[field], params.value);
                        break;
                    case 'maxLength':
                        isValid = this.maxLength(data[field], params.value);
                        break;
                    case 'matches':
                        const matchInput = form.querySelector(`[name="${params.field}"]`) || document.getElementById(params.field);
                        isValid = matchInput && data[field] === matchInput.value.trim();
                        break;
                }

                if (!isValid) {
                    errors.push(message || `Campo ${field} inválido`);
                    break;
                }
            }
        }

        if (errors.length > 0) {
            window.mostrarAlerta(errors[0], 'warning');
            return { valid: false, errors, data };
        }

        return { valid: true, errors: [], data };
    }
};

window.Validator = Validator;
