import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Enviar siempre la cookie de sesión y forzar el header X-XSRF-TOKEN. En axios 1.x el header no siempre
// se agrega (según el chequeo interno de same-origin), lo que provocaba "CSRF token mismatch" intermitentes.
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Lee el valor actual de la cookie XSRF-TOKEN (Laravel la refresca en cada respuesta).
function leerXsrfToken() {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : null;
}

// Red de seguridad: ante un 419 (CSRF token mismatch) refresca el token desde el servidor y reintenta
// la request una sola vez. Evita que el usuario tenga que recargar la página para volver a operar.
window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const cfg = error.config;
        if (error.response && error.response.status === 419 && cfg && !cfg._csrfRetried) {
            cfg._csrfRetried = true;
            try {
                await window.axios.get('/csrf-token');
            } catch (e) {
                // Si el refresh falla (p. ej. sesión realmente expirada), se rechaza el error más abajo.
            }
            const token = leerXsrfToken();
            if (token) {
                cfg.headers = cfg.headers || {};
                cfg.headers['X-XSRF-TOKEN'] = token;
            }
            return window.axios(cfg);
        }
        return Promise.reject(error);
    }
);
