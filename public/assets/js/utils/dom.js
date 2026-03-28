const DOM = {
    $(selector, context = document) {
        return context.querySelector(selector);
    },

    $$(selector, context = document) {
        return Array.from(context.querySelectorAll(selector));
    },

    cache(selectorMap, context = document) {
        const cache = {};
        for (const [key, selector] of Object.entries(selectorMap)) {
            cache[key] = typeof selector === 'string' 
                ? context.querySelector(selector) 
                : selector;
        }
        return cache;
    },

    create(tag, attrs = {}, children = []) {
        const el = document.createElement(tag);
        
        for (const [key, value] of Object.entries(attrs)) {
            if (key === 'className') {
                el.className = value;
            } else if (key === 'style' && typeof value === 'object') {
                Object.assign(el.style, value);
            } else if (key.startsWith('on') && typeof value === 'function') {
                el.addEventListener(key.slice(2).toLowerCase(), value);
            } else if (key === 'innerHTML' || key === 'textContent') {
                el[key] = value;
            } else {
                el.setAttribute(key, value);
            }
        }
        
        children.forEach(child => {
            if (typeof child === 'string') {
                el.insertAdjacentHTML('beforeend', child);
            } else if (child instanceof Node) {
                el.appendChild(child);
            }
        });
        
        return el;
    },

    show(elements) {
        const els = Array.isArray(elements) ? elements : [elements];
        els.forEach(el => {
            if (el) el.style.display = '';
        });
    },

    hide(elements) {
        const els = Array.isArray(elements) ? elements : [elements];
        els.forEach(el => {
            if (el) el.style.display = 'none';
        });
    },

    toggle(elements, force) {
        const els = Array.isArray(elements) ? elements : [elements];
        els.forEach(el => {
            if (el) {
                if (typeof force === 'boolean') {
                    el.style.display = force ? '' : 'none';
                } else {
                    el.style.display = el.style.display === 'none' ? '' : 'none';
                }
            }
        });
    },

    empty(element) {
        if (element) element.innerHTML = '';
    },

    remove(element) {
        if (element && element.parentNode) {
            element.parentNode.removeChild(element);
        }
    },

    addClass(elements, className) {
        const els = Array.isArray(elements) ? elements : [elements];
        els.forEach(el => {
            if (el) el.classList.add(className);
        });
    },

    removeClass(elements, className) {
        const els = Array.isArray(elements) ? elements : [elements];
        els.forEach(el => {
            if (el) el.classList.remove(className);
        });
    },

    toggleClass(elements, className) {
        const els = Array.isArray(elements) ? elements : [elements];
        els.forEach(el => {
            if (el) el.classList.toggle(className);
        });
    },

    on(elements, event, handler, options) {
        const els = Array.isArray(elements) ? elements : [elements];
        els.forEach(el => {
            if (el) el.addEventListener(event, handler, options);
        });
    },

    off(elements, event, handler, options) {
        const els = Array.isArray(elements) ? elements : [elements];
        els.forEach(el => {
            if (el) el.removeEventListener(event, handler, options);
        });
    },

    delegate(parent, selector, event, handler) {
        parent.addEventListener(event, (e) => {
            const target = e.target.closest(selector);
            if (target && parent.contains(target)) {
                handler.call(target, e);
            }
        });
    },

    html(element, html) {
        if (html === undefined) {
            return element ? element.innerHTML : '';
        }
        if (element) element.innerHTML = html;
    },

    text(element, text) {
        if (text === undefined) {
            return element ? element.textContent : '';
        }
        if (element) element.textContent = text;
    },

    val(element, value) {
        if (value === undefined) {
            return element ? element.value : '';
        }
        if (element) element.value = value;
    },

    closest(element, selector) {
        return element ? element.closest(selector) : null;
    },

    isVisible(element) {
        return element ? element.offsetParent !== null : false;
    },

    scrollTo(element, options = {}) {
        if (element) {
            element.scrollIntoView({
                behavior: options.behavior || 'smooth',
                block: options.block || 'start'
            });
        }
    }
};

window.DOM = DOM;
