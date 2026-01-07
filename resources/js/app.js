// Configuración global de Alpine.js
document.addEventListener('alpine:init', () => {
    // Store global para el estado de la aplicación
    Alpine.store('app', {
        user: null,
        token: null,
        darkMode: false,

        init() {
            // Cargar datos del usuario desde localStorage
            const userData = localStorage.getItem('user');
            const token = localStorage.getItem('token');

            if (userData && token) {
                this.user = JSON.parse(userData);
                this.token = token;
            }

            // Cargar preferencia de modo oscuro
            this.darkMode = localStorage.getItem('darkMode') === 'true';
        },

        setUser(user, token) {
            this.user = user;
            this.token = token;
            localStorage.setItem('user', JSON.stringify(user));
            localStorage.setItem('token', token);
        },

        logout() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            window.location.href = '/login';
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
        }
    });

    // Store para notificaciones
    Alpine.store('notifications', {
        items: [],

        add(message, type = 'info', duration = 3000) {
            const id = Date.now();
            this.items.push({ id, message, type, duration });

            setTimeout(() => {
                this.remove(id);
            }, duration);
        },

        remove(id) {
            this.items = this.items.filter(item => item.id !== id);
        },

        success(message, duration = 3000) {
            this.add(message, 'success', duration);
        },

        error(message, duration = 5000) {
            this.add(message, 'error', duration);
        },

        info(message, duration = 3000) {
            this.add(message, 'info', duration);
        },

        warning(message, duration = 4000) {
            this.add(message, 'warning', duration);
        }
    });

    // Store para el estado de carga
    Alpine.store('loading', {
        active: false,
        message: 'Cargando...',

        show(message = 'Cargando...') {
            this.active = true;
            this.message = message;
        },

        hide() {
            this.active = false;
        }
    });
});

// Utilidades globales
window.api = {
    // Configuración base para las llamadas a la API
    baseURL: '/api',

    // Headers por defecto
    getHeaders() {
        const token = localStorage.getItem('token');
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` })
        };
    },

    // Método GET
    async get(url, options = {}) {
        return this.request('GET', url, options);
    },

    // Método POST
    async post(url, data = {}, options = {}) {
        return this.request('POST', url, { ...options, body: JSON.stringify(data) });
    },

    // Método PUT
    async put(url, data = {}, options = {}) {
        return this.request('PUT', url, { ...options, body: JSON.stringify(data) });
    },

    // Método DELETE
    async delete(url, options = {}) {
        return this.request('DELETE', url, options);
    },

    // Método base para todas las peticiones
    async request(method, url, options = {}) {
        const config = {
            method,
            headers: this.getHeaders(),
            ...options
        };

        try {
            const response = await fetch(`${this.baseURL}${url}`, config);

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Error en la petición');
            }

            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
};

// Utilidades para fechas
window.dateUtils = {
    format(date, format = 'DD/MM/YYYY HH:mm') {
        const d = new Date(date);
        const day = d.getDate().toString().padStart(2, '0');
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const year = d.getFullYear();
        const hours = d.getHours().toString().padStart(2, '0');
        const minutes = d.getMinutes().toString().padStart(2, '0');

        return format
            .replace('DD', day)
            .replace('MM', month)
            .replace('YYYY', year)
            .replace('HH', hours)
            .replace('mm', minutes);
    },

    relative(date) {
        const now = new Date();
        const d = new Date(date);
        const diff = now - d;

        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (days > 0) return `hace ${days} día${days > 1 ? 's' : ''}`;
        if (hours > 0) return `hace ${hours} hora${hours > 1 ? 's' : ''}`;
        if (minutes > 0) return `hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
        return 'ahora';
    },

    isToday(date) {
        const today = new Date();
        const d = new Date(date);
        return d.toDateString() === today.toDateString();
    },

    isYesterday(date) {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const d = new Date(date);
        return d.toDateString() === yesterday.toDateString();
    }
};

// Utilidades para validación
window.validation = {
    email(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    required(value) {
        return value !== null && value !== undefined && value !== '';
    },

    minLength(value, min) {
        return value && value.length >= min;
    },

    maxLength(value, max) {
        return value && value.length <= max;
    },

    phone(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone);
    },

    url(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
};

// Utilidades para el DOM
window.domUtils = {
    // Scroll suave a un elemento
    scrollTo(element, offset = 0) {
        const target = typeof element === 'string' ? document.querySelector(element) : element;
        if (target) {
            const targetPosition = target.offsetTop - offset;
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    },

    // Copiar texto al portapapeles
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback para navegadores más antiguos
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                document.body.removeChild(textArea);
                return true;
            } catch (err) {
                document.body.removeChild(textArea);
                return false;
            }
        }
    },

    // Descargar archivo
    downloadFile(data, filename, type = 'text/plain') {
        const blob = new Blob([data], { type });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    },

    // Obtener el tamaño de la ventana
    getWindowSize() {
        return {
            width: window.innerWidth,
            height: window.innerHeight
        };
    },

    // Verificar si un elemento está en el viewport
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
};

// Utilidades para el manejo de archivos
window.fileUtils = {
    // Leer archivo como texto
    readAsText(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.onerror = e => reject(e);
            reader.readAsText(file);
        });
    },

    // Leer archivo como base64
    readAsBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.onerror = e => reject(e);
            reader.readAsDataURL(file);
        });
    },

    // Obtener extensión de archivo
    getExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    },

    // Verificar tipo de archivo
    isImage(filename) {
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        return imageExtensions.includes(this.getExtension(filename));
    },

    // Formatear tamaño de archivo
    formatSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
};

// Utilidades para el manejo de errores
window.errorHandler = {
    // Manejar errores de API
    handleApiError(error) {
        console.error('API Error:', error);

        if (error.status === 401) {
            // No redirigir automáticamente para rutas de dialogos-v2 que usan sesión web
            if (error.url && error.url.includes('/api/dialogos-v2/')) {
                console.log('Error 401 en dialogos-v2, no redirigiendo automáticamente');
                return;
            }
            
            // Token expirado o no válido
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
            return;
        }

        if (error.status === 403) {
            Alpine.store('notifications').error('No tienes permisos para realizar esta acción');
            return;
        }

        if (error.status === 404) {
            Alpine.store('notifications').error('Recurso no encontrado');
            return;
        }

        if (error.status === 422) {
            // Errores de validación
            if (error.errors) {
                Object.values(error.errors).forEach(errors => {
                    errors.forEach(error => {
                        Alpine.store('notifications').error(error);
                    });
                });
            }
            return;
        }

        if (error.status >= 500) {
            Alpine.store('notifications').error('Error interno del servidor');
            return;
        }

        Alpine.store('notifications').error(error.message || 'Error desconocido');
    },

    // Manejar errores de red
    handleNetworkError(error) {
        console.error('Network Error:', error);
        Alpine.store('notifications').error('Error de conexión. Verifica tu conexión a internet.');
    }
};

// Configuración de interceptores para fetch
const originalFetch = window.fetch;
window.fetch = async function (...args) {
    try {
        const response = await originalFetch.apply(this, args);

        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Error desconocido' }));
            error.status = response.status;
            
            // No redirigir automáticamente para rutas de dialogos-v2 que usan sesión web
            const url = args[0];
            if (typeof url === 'string' && url.includes('/api/dialogos-v2/')) {
                console.log('Error en dialogos-v2, no redirigiendo automáticamente:', error);
                throw error;
            }
            
            throw error;
        }

        return response;
    } catch (error) {
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            window.errorHandler.handleNetworkError(error);
        } else {
            // No manejar errores automáticamente para dialogos-v2
            const url = args[0];
            if (typeof url === 'string' && url.includes('/api/dialogos-v2/')) {
                console.log('Error en dialogos-v2, dejando que el código maneje el error:', error);
                throw error;
            }
            window.errorHandler.handleApiError(error);
        }
        throw error;
    }
};

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function () {
    // Configurar el modo oscuro
    const darkMode = localStorage.getItem('darkMode') === 'true';
    if (darkMode) {
        document.documentElement.classList.add('dark');
    }

    // Configurar el tema del sistema
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    if (!localStorage.getItem('darkMode')) {
        if (prefersDark.matches) {
            document.documentElement.classList.add('dark');
        }
    }

    // Escuchar cambios en la preferencia del sistema
    prefersDark.addEventListener('change', (e) => {
        if (!localStorage.getItem('darkMode')) {
            if (e.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    });

    // Configurar el auto-save para formularios
    const forms = document.querySelectorAll('form[data-autosave]');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                localStorage.setItem(`autosave_${form.id}`, JSON.stringify(data));
            });
        });

        // Restaurar datos guardados
        const savedData = localStorage.getItem(`autosave_${form.id}`);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.entries(data).forEach(([key, value]) => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = value;
                }
            });
        }
    });

    // Configurar el lazy loading para imágenes
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Configurar el auto-refresh para datos en tiempo real
    const refreshElements = document.querySelectorAll('[data-refresh]');
    refreshElements.forEach(element => {
        const interval = parseInt(element.dataset.refresh) || 30000; // 30 segundos por defecto
        setInterval(() => {
            if (element.offsetParent !== null) { // Solo si el elemento es visible
                element.dispatchEvent(new CustomEvent('refresh'));
            }
        }, interval);
    });
});

// Exportar utilidades para uso global
window.utils = {
    api: window.api,
    dateUtils: window.dateUtils,
    validation: window.validation,
    domUtils: window.domUtils,
    fileUtils: window.fileUtils,
    errorHandler: window.errorHandler
};