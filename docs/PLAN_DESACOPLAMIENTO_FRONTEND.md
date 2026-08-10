# Plan: Desacoplar el Frontend del Backend

**Proyecto:** MDESistemaPIDE
**Objetivo:** Separar el frontend (hoy vanilla JS embebido en vistas PHP) del backend (hoy PHP MVC; mañana Laravel API + JWT) para poder reemplazar el backend sin tocar el frontend.

---

## 1. Contexto y estado actual

### 1.1. Qué hay hoy

El frontend ya consume el backend a través de una **API JSON** (`/api/*`) usando `fetch` (`public/assets/js/core/api.js`). Eso hace la separación viable. Sin embargo, el frontend sigue **acoplado al backend** por 5 puntos:

| # | Acoplamiento | Dónde |
|---|--------------|-------|
| 1 | **Vistas PHP servidas por el backend** que inyectan `BASE_URL`, `$_SESSION`, permisos y módulos de la BD | `app/views/login.php`, `app/views/dashboard/index.php`, `app/views/layouts/*`, `app/views/dashboard/pages/*` |
| 2 | **URLs hardcodeadas** con `/MDESistemaPIDE/public/...` | `assets/js/core/constants.js`, `assets/js/bootstrap.js`, `assets/js/sistema/actualizarPassword.js` |
| 3 | **Render dinámico en servidor** del menú y de las páginas según módulos/permisos de la BD | `app/helpers/generarPaginasDinamicas.php`, `app/views/layouts/sidebar.php` |
| 4 | **Sesión por cookies + CSRF** de mismo origen (token vía `/api/csrf-token`) | `app/views/layouts/header.php` (inyecta `SESSION_CONFIG`), `assets/js/core/api.js` |
| 5 | **PDF** generado en servidor con dompdf | `POST /api/consultas/dni/pdf`, `app/views/dashboard/pages/consultas/dni_pdf.php` |

### 1.2. Estrategia en 2 etapas

1. **Etapa A (este plan):** Extraer un frontend **estático** (HTML + CSS + JS vanilla) que siga funcionando contra el **backend actual** (misma API, misma sesión/CSRF, mismo origen). Solo cambia la URL base de la API en un único archivo de configuración.
2. **Etapa B (cuando Laravel esté listo):** cambiar `config.js` para apuntar a Laravel y migrar la autenticación de sesión/CSRF a **JWT** (`Authorization: Bearer`). El frontend React + TypeScript se construye luego sobre esta base.

> **Ventaja clave:** como la Etapa A se valida contra el backend actual, el sistema **nunca deja de funcionar**. Cambiar a Laravel después es editar un solo archivo (`config.js`) + adaptar el auth en `api.js`.

---

## 2. Arquitectura de destino (intermedia)

```
MDESistemaPIDE/
├── public/            ← BACKEND actual (NO se toca). Se mantiene sirviendo /api/*
├── frontend/          ← NUEVO: frontend estático
│   ├── login.html
│   ├── dashboard.html
│   ├── pages/                       ← fragmentos HTML de cada módulo
│   │   ├── inicio.html
│   │   ├── consultas/
│   │   │   ├── dni.html
│   │   │   ├── ruc.html
│   │   │   └── partidas.html
│   │   └── sistema/
│   │       ├── crearUsuario.html
│   │       ├── crearRoles.html
│   │       ├── crearModulo.html
│   │       └── actualizarPass.html
│   ├── assets/
│   │   ├── css/                     ← tailwind.css + CSS personalizados
│   │   ├── images/ fonts/ webfonts/
│   │   └── js/
│   │       ├── config.js            ← NUEVO: única fuente de URLs
│   │       ├── core/
│   │       │   ├── constants.js
│   │       │   ├── events.js
│   │       │   ├── api.js
│   │       │   ├── dashboard.js
│   │       │   └── modules-loader.js  ← NUEVO: carga menú + páginas desde la API
│   │       ├── utils/  (storage, dom, validator, alerts, loading)
│   │       └── modules/ (login.js, consultas/*.js, sistema/*.js)
│   ├── src/tailwind.css
│   ├── tailwind.config.js
│   └── package.json
```

El frontend se sirve como **estático por Apache** bajo el mismo host (p. ej. `http://localhost/MDESistemaPIDE/frontend/`), por lo que las cookies de sesión del backend siguen funcionando (mismo dominio, `path=/`).

---

## 3. Pasos detallados

### Paso 1 — Copiar assets y tooling

1. Copiar `public/assets/*` → `frontend/assets/` (CSS, JS, imágenes, fuentes, webfonts).
2. Copiar `src/tailwind.css`, `tailwind.config.js`, `package.json` → `frontend/`.
3. En `frontend/package.json`, ajustar los scripts de build para que el CSS salga a `assets/css/tailwind.css` (relativo a `frontend/`).
4. (Opcional) En `tailwind.config.js` verificar que `content` incluya los nuevos `.html`.

```jsonc
// frontend/package.json
{
  "scripts": {
    "build:css": "npx tailwindcss -i src/tailwind.css -o assets/css/tailwind.css --minify",
    "watch:css": "npx tailwindcss -i src/tailwind.css -o assets/css/tailwind.css --watch"
  }
}
```

### Paso 2 — Convertir las vistas PHP a HTML estático

Regla general: **reemplazar todo `<?= BASE_URL ?>` por rutas relativas** (`assets/...`) y **quitar todo PHP**; lo que el servidor inyectaba (usuario, permisos, módulos, fecha) lo llena el JS llamando a la API.

#### 2.1 `app/views/login.php` → `frontend/login.html`
- `<?= BASE_URL ?>assets/...` → `assets/...`.
- Bloque PHP `if (isset($_GET['sesion_exp']))` → mostrar el aviso con JS leyendo `URLSearchParams('sesion_exp')`.
- Redirección PHP "si ya está autenticado" → hacerla en JS al cargar (si la sesión/cookie es válida, ir a dashboard).
- El resto del HTML (formulario, modal CUI) se copia tal cual; `modules/auth/login.js` ya hace el login por API.

#### 2.2 `app/views/dashboard/index.php` → `frontend/dashboard.html`
- Mantener el shell visual: loading overlay, `#main-content`, header, contenedor de páginas.
- **Quitar** la llamada a `generarPaginasDinamicas()` y el `<div>` con las páginas. En su lugar dejar:
  ```html
  <main id="main-content" ...>
    <header ...> ... </header>
    <div id="pageContainer"></div>   <!-- el JS inyecta aquí las .page-content -->
  </main>
  ```
- **Quitar** la inyección de datos de sesión (nombre, cargo, área, fecha) y dejar placeholders con `id` que el JS rellena desde `/api/usuarios/actual` (y `new Date()` para la fecha).
- El `<aside id="sidebar">` queda como plantilla **vacía**: el `nav` se llena dinámicamente (Paso 4). El CSS/JS del sidebar (colapsable, tooltips, logout modal) se conserva en `dashboard.html` o se extrae a `assets/js/core/sidebar.js`.

#### 2.3 `app/views/layouts/header.php` → parte de `dashboard.html`
- Conservar el `<head>` (Tailwind, FontAwesome, SweetAlert2, CSS personalizados) y los `<style>` globales.
- **Quitar** el bloque `<?php ... SESSION_CONFIG ... ?>` y `if (!$_SESSION['authenticated'])`; la validación de sesión la hace el JS (`/api/usuarios/actual` → 401 redirige a login).

#### 2.4 `app/views/layouts/footer.php` → parte de `dashboard.html`
- Conservar la lista de `<script>` (constants, events, utils, api, dashboard, sidebar, módulos, app).
- **Anteponer** `assets/js/config.js` como primer script.
- Ajustar rutas `assets/js/...`.

#### 2.5 `app/views/dashboard/pages/*.php` → `frontend/pages/*.html`

Inventario y tratamiento:

| Archivo actual | PHP | Acción |
|---|---|---|
| `inicio.php` | No | Copiar tal cual → `pages/inicio.html` |
| `consultas/partidas.php` | No | Copiar tal cual → `pages/consultas/partidas.html` |
| `consultas/papeletas.php` | No | Copiar tal cual → `pages/consultas/papeletas.html` |
| `sistema/crearUsuario.php` | No | Copiar tal cual → `pages/sistema/crearUsuario.html` |
| `sistema/crearRoles.php` | No | Copiar tal cual → `pages/sistema/crearRoles.html` |
| `sistema/crearModulo.php` | No | Copiar tal cual → `pages/sistema/crearModulo.html` |
| `sistema/actualizarPass.php` | No | Copiar tal cual → `pages/sistema/actualizarPass.html` |
| `consultas/dni.php` | Sí | Convertir (ver 2.5.1) |
| `consultas/ruc.php` | Sí | Convertir (ver 2.5.1) |
| `consultas/dni_pdf.php` | Sí | **NO se extrae**: es la plantilla que el backend usa para generar el PDF (output buffer + dompdf). Queda en el backend y se porta a Laravel (Etapa B). |

##### 2.5.1 Conversión de `dni.php` y `ruc.php`
Estas dos vistas hoy son **server-rendered**: el controlador pinta el último resultado (`$persona`, `$contribuyente`) y un mensaje (`$mensaje`, `$tipo_mensaje`). Al pasar a estático:

1. Quitar todo el bloque `<?php if (isset($mensaje)) ... ?>` (los mensajes ya se muestran con `Alerts` en JS).
2. Quitar `value="<?php echo isset($_POST['dni']) ... ?>"` → dejar `value=""`.
3. La sección de resultados queda **estática** con `data-campo="..."` y valores vacíos (`-`):
   ```html
   <div class="mt-1 text-2xl font-bold text-red-800" data-campo="ruc">-</div>
   ```
   El JS (`modules/consultas/dni.js`, `ruc.js`) ya localiza los resultados por `data-campo` para rellenarlos tras la consulta (los ID/selectores se conservan).
4. En `dni.php`, el `<?php if (isset($persona['foto'])) ?>` → dejar el `<img id="personaFoto">` con una imagen placeholder y ocultar/mostrar por JS según la respuesta.
5. En `ruc.php`, la tabla de campos generada con `foreach ($campos ...)` → escribir los 23 campos **estáticos** (cada `<div data-campo="<key>">-</div>` con su label), usando el mismo orden y clases.

> Nota: verificar que los selectores usados por `dni.js`/`ruc.js` (p. ej. `#result-nombres`, `#result-paterno`, `#photoContainer img`, `.page-content`) existan en los HTML convertidos. La lógica JS de llenado **no cambia**.

### Paso 3 — Configuración centralizada: `frontend/assets/js/config.js`

Crear el archivo que será la **única fuente de verdad** de URLs:

```js
window.APP_CONFIG = {
    API: {
        // Etapa A: backend actual. Etapa B: cambiar a la URL de Laravel (p.ej. '/api' o 'http://localhost:8000/api').
        BASE_URL: '/MDESistemaPIDE/public/api'
    },
    ROUTES: {
        BASE:       '/MDESistemaPIDE/frontend/',
        LOGIN:      '/MDESistemaPIDE/frontend/login.html',
        DASHBOARD:  '/MDESistemaPIDE/frontend/dashboard.html',
        ASSETS_JS:  '/MDESistemaPIDE/frontend/assets/js/'
    }
};
```

Luego modificar los archivos que hoy tienen URLs hardcodeadas:

| Archivo | Cambio |
|---|---|
| `core/constants.js` | `API.BASE_URL` → `APP_CONFIG.API.BASE_URL`; `ROUTES.*` → `APP_CONFIG.ROUTES.*`. Dejar `ENDPOINTS`, `MODULOS`, `UI`, `VALIDATION` sin cambios. |
| `bootstrap.js` | `getBaseUrl()` → devolver `APP_CONFIG.ROUTES.ASSETS_JS`. |
| `sistema/actualizarPassword.js` | `BASE_URL:` hardcodeada → usar `APP_CONFIG.ROUTES.BASE`. |
| `core/dashboard.js` | `BASE_URL: Constants.ROUTES.BASE` → `APP_CONFIG.ROUTES.BASE` (o dejarlo, ya depende de constants). |
| `modules/auth/login.js` | `window.location.href = 'dashboard'` → `APP_CONFIG.ROUTES.DASHBOARD`. |

### Paso 4 — Carga dinámica de menú y páginas (JS)

Replicar en JS la lógica que hoy hace el servidor (`sidebar.php` + `generarPaginasDinamicas.php`). Crear **`frontend/assets/js/core/modules-loader.js`**:

```js
const ModulesLoader = {
    async loadSession() {
        // GET /api/usuarios/actual → usuario para header/sidebar.
        // Si responde 401 → api.js ya redirige a login (redirectToLogin).
    },

    async loadModules() {
        // POST /api/modulos/obtener-por-usuario (payload {}) → árbol de módulos.
        // Filtrar por Storage.getPermisos() (equivale a Permisos::obtenerPermisos en PHP).
        return modulos; // array jerárquico {MOD_id, MOD_url, MOD_icono, MOD_nombre, hijos[]}
    },

    renderSidebar(modulos) {
        // Replica sidebar.php: <a class="option"> si no tiene hijos con permiso,
        // <a class="option has-submenu"> + <div class="submenu"> con <a class="suboption">.
        // onclick="showPage('<NombrePagina>', this)" — obtenerNombrePagina(MOD_url) en JS.
    },

    async renderPages(modulos) {
        // Para cada módulo con permiso: derivar ruta del fragmento desde MOD_url
        // (igual que obtenerRutaArchivo(): consultas/dni -> pages/consultas/dni.html).
        // fetch('pages/.../xx.html') y crear <div id="pageXxx" class="page-content">...</div>
        // dentro de #pageContainer. Evitar duplicados (Set de ids).
    },

    async init() {
        await this.loadSession();
        const modulos = await this.loadModules();
        this.renderSidebar(modulos);
        await this.renderPages(modulos);
        Dashboard.init();   // el Dashboard.showPage ya gestiona mostrar/ocultar .page-content
    }
};
```

Detalles:
- Funciones auxiliares `obtenerIdPagina(url)` y `obtenerNombrePagina(url)` pasan a JS (misma transformación: `MOD_url` → camelCase `PageXxx`).
- El mapeo **módulo → fragmento** se hace con una tabla en `config.js` (si no se quiere depender de `MOD_url`):
  ```js
  PAGES: {
      '/consultas/dni':          'pages/consultas/dni.html',
      '/consultas/ruc':          'pages/consultas/ruc.html',
      '/consultas/partidas':     'pages/consultas/partidas.html',
      '/sistema/crear-usuario':  'pages/sistema/crearUsuario.html',
      // ...
  }
  ```
- En `dashboard.html` el `<script>` final llama `ModulesLoader.init()` en lugar de `Dashboard.init()` directamente.

### Paso 5 — Ajustar el flujo de login / logout

En la **Etapa A se mantiene la autenticación actual** (cookies de sesión + CSRF), porque frontend y backend comparten origen. Solo ajustar:

1. `login.js`: después de `validarCUI` exitoso, redirigir a `APP_CONFIG.ROUTES.DASHBOARD` (hoy usa la ruta relativa `'dashboard'`).
2. `dashboard.js` / `sidebar.js`: las redirecciones a login usan `Constants.ROUTES.LOGIN` → pasar a `APP_CONFIG.ROUTES.LOGIN`.
3. El botón de logout del sidebar (`sidebar.php` hoy hace `fetch('<?= BASE_URL ?>api/logout')`) → dejarlo a `authService.logout()` (ya existe en `api.js`), que llama `POST /api/logout`, y luego redirigir a `APP_CONFIG.ROUTES.LOGIN`.
4. `core/api.js` **no cambia** en Etapa A: sigue pidiendo CSRF (`/api/csrf-token`) y enviando `X-CSRF-Token`; en 401 redirige a login (ya implementado).

> **Etapa B (JWT):** cuando el backend sea Laravel, en `api.js` guardar el token (`Storage.local.set('token', token)`) tras el login, añadir el header `Authorization: Bearer <token>` en cada request, eliminar `ensureCSRF()`/`X-CSRF-Token`, y ajustar `login.js` para leer el JWT de la respuesta.

### Paso 6 — Servir el frontend (Apache)

1. Crear la carpeta `frontend/` dentro de `htdocs` (está al lado de `public/`). Apache sirve los archivos estáticos directamente: `http://localhost/MDESistemaPIDE/frontend/`.
2. No hace falta `.htaccess` especial para los estáticos; si se quiere, uno que habilite `Indexes` o una página de inicio por defecto.
3. Verificar que las cookies de sesión se compartan: el backend define la sesión con `path=/` (ver `config/app.php`), así que un `fetch` desde `/MDESistemaPIDE/frontend/...` a `/MDESistemaPIDE/public/api/...` envía las cookies del mismo dominio. **No se requiere CORS** en Etapa A.
4. (Etapa B) Cuando Laravel reemplace al backend, servir el frontend donde corresponda y apuntar `config.js` a la URL de Laravel; si el frontend se sirve en otro origen, habilitar CORS en Laravel o usar un proxy/rewrite de mismo dominio.

### Paso 7 — Validación funcional (checklist)

- [ ] `frontend/login.html` carga y el login funciona contra `/api/login` (mismo backend actual).
- [ ] Flujo CUI: `validarCUI` responde y redirige a `dashboard.html`.
- [ ] Menú lateral renderizado según módulos/permisos (equivale a `sidebar.php`).
- [ ] Páginas de módulos cargadas dinámicamente (`#pageContainer`) según `MOD_url`.
- [ ] Consulta DNI: resultados y foto se rellenan vía JS (`modules/consultas/dni.js`).
- [ ] Consulta RUC: resultados se rellenan por `data-campo` (`modules/consultas/ruc.js`).
- [ ] Consulta Partidas (SUNARP).
- [ ] PDF: `POST /api/consultas/dni/pdf` descarga/abre el PDF (vía blob en `dni.js`).
- [ ] CRUD usuarios / roles / módulos.
- [ ] Cambio de contraseña (obligatorio y voluntario).
- [ ] Logout y redirección por sesión expirada (401 → login).

### Paso 8 — Migrar a JWT (cuando Laravel esté listo)

1. En `frontend/assets/js/config.js`: cambiar `API.BASE_URL` a la URL de Laravel.
2. En `core/api.js`:
   - Añadir el header `Authorization: Bearer <token>` en `request()`.
   - Quitar la llamada a `ensureCSRF()` y el header `X-CSRF-Token`.
   - En `redirectToLogin` limpiar también el token.
3. En `modules/auth/login.js`: tras `login`/`validarCUI`, guardar el JWT (p. ej. `Storage.local.set('token', ...)`) y redirigir a `APP_CONFIG.ROUTES.DASHBOARD`.
4. En `core/constants.js`: eliminar `ENDPOINTS.CSRF_TOKEN` (o dejarlo inofensivo).
5. Ajustar el endpoint `obtenerDniYPassword` del backend (hoy depende de `$_SESSION['password']`): en Laravel pedir la contraseña en el request o emitir un claim en el JWT.

---

## 4. Riesgos y consideraciones

- **Mismo origen obligatorio en Etapa A**: si el frontend se sirve en otro puerto/dominio, las cookies de sesión no viajan y la Etapa A falla hasta migrar a JWT (Etapa B).
- **`dni_pdf.php` permanece en el backend**: es la plantilla de generación del PDF; no se extrae al frontend.
- **`/api/inicio` y `/api/usuarios/actual`** deben seguir respondiendo igual; son los endpoints que alimentan el header/dashboard en el frontend desacoplado.
- **Selectores del JS de resultados**: al convertir `dni.php`/`ruc.php` a HTML estático hay que respetar los `id`/`data-campo` que el JS usa para llenar resultados.
- **El folder raíz `views/`** es un duplicado parcial de `app/views`; no interviene en el desacoplamiento.
- **Versión de PHP**: el backend Laravel (Etapa B) requiere PHP 8.1+ con drivers `sqlsrv`/`pdo_sqlsrv`; el XAMPP actual (7.4) solo soporta Laravel 8. Planificar la actualización del entorno.

---

## 5. Inventario de archivos

### Crear
- `frontend/` completo (estructura del punto 2).
- `frontend/assets/js/config.js`.
- `frontend/assets/js/core/modules-loader.js`.
- `frontend/login.html`, `frontend/dashboard.html`.
- `frontend/pages/*.html` (fragmentos de módulos).

### Modificar (copias en `frontend/assets/js/`)
- `core/constants.js` (leer de `APP_CONFIG`).
- `bootstrap.js` (leer de `APP_CONFIG`).
- `sistema/actualizarPassword.js` (quitar `BASE_URL` hardcodeada).
- `core/dashboard.js` (rutas desde `APP_CONFIG`).
- `modules/auth/login.js` (redirección a `APP_CONFIG.ROUTES.DASHBOARD`).

### NO tocar
- `public/`, `app/`, `config/`, `views/` (backend actual, intacto durante Etapa A).

---

## 6. Orden de ejecución sugerido

1. **Paso 1**: copiar assets y tooling a `frontend/`.
2. **Paso 3**: crear `config.js` y ajustar `constants.js`, `bootstrap.js`, `dashboard.js`, `login.js`, `actualizarPassword.js`.
3. **Paso 2.1**: crear `login.html` y validar el login contra el backend actual.
4. **Paso 4**: crear `modules-loader.js` + `sidebar.js`.
5. **Paso 2.2–2.5**: crear `dashboard.html` y los fragmentos `pages/*.html` (dni/ruc convertidos).
6. **Paso 5 y 6**: ajustar login/logout y servir por Apache.
7. **Paso 7**: recorrer el checklist completo.

Al terminar el checklist, el frontend queda **desacoplado** y listo para que, en Etapa B, solo se cambie `config.js` (URL de la API) y el auth en `api.js` para apuntar al backend Laravel con JWT.
