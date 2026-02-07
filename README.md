# Sistema de Consultas PIDE (MDESistemaPIDE)

Aplicacion web para consultar servicios de la **Plataforma de Interoperabilidad del Estado (PIDE)** y administrar usuarios, roles y modulos internos. Integra consultas a **RENIEC, SUNAT y SUNARP**, con autenticacion, control de acceso y vistas web (login + dashboard).

**Version:** 1.0.0  
**Estado:** En desarrollo / uso interno  
**Propietario:** Municipalidad (area de TI)  
**Contacto:** Mesa de ayuda TI / Soporte de sistemas

## Para que sirve
- Consultar informacion por DNI (RENIEC).
- Consultar RUC (SUNAT).
- Consultar partidas/propiedades y datos registrales (SUNARP).
- Administrar usuarios, roles y modulos habilitados.
- Proveer un dashboard con indicadores basicos.

## Tecnologias
- PHP 7.4+
- Apache (con `mod_rewrite`)
- SQL Server (driver `pdo_sqlsrv`)
- PHPUnit (tests)

## Requisitos
- PHP 7.4 o superior.
- Extensiones PHP: `pdo`, `pdo_sqlsrv`, `openssl`, `mbstring`, `json`.
- Apache con `mod_rewrite` habilitado.
- SQL Server accesible desde la app.
- Acceso a los endpoints PIDE (RENIEC/SUNAT/SUNARP).

## Instalacion (XAMPP/Windows)
1. Clonar o copiar el proyecto en `C:\xampp7.4\htdocs\MDESistemaPIDE`.
2. Crear la base de datos en SQL Server y restaurar el backup si aplica.
   - Backup disponible en `sistemaPIDE2.bak`.
3. Configurar el archivo `.env` (ver siguiente seccion).
4. Verificar que la URL base coincida con el path publico:
   - En `config/app.php` se define `BASE_URL` como `/MDESistemaPIDE/public/`.
   - En `public/.htaccess` se usa `RewriteBase /MDESistemaPIDE/public/`.
5. Iniciar Apache y acceder al modulo desde el navegador.

## Configuracion (.env)
Archivo de entorno ubicado en la raiz. **No publiques valores reales** en repositorios compartidos.

Ejemplo:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=SISTEMAPIDE2
DB_USER=usuario
DB_PASS=clave
DB_PORT=1433

# PIDE
PIDE_RUC_EMPRESA=XXXXXXXXXXX
PIDE_URL_RENIEC=URL_PRIVADA_RENIEC
PIDE_URL_SUNAT=URL_PRIVADA_SUNAT
PIDE_URL_SUNARP=URL_PRIVADA_SUNARP
PIDE_GOFICINA=URL_PRIVADA_GOFICINA
PIDE_SUNARP_USUARIO=usuario_sunarp
PIDE_SUNARP_PASS=clave_sunarp

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/MDESistemaPIDE/public/

# Session Settings
SESSION_LIFETIME=7200
```

Notas:
- `APP_URL` se usa para CORS y debe reflejar la URL real.
- La app carga `.env` en `config/app.php` y `app/config/Database.php`.

## Estructura del proyecto (que es cada cosa)
- `app/`
  - `config/`: configuracion interna (ej: `Database.php`).
  - `controllers/`: controladores HTTP (auth, usuarios, roles, consultas).
  - `core/`: router y request base.
  - `exceptions/`: excepciones personalizadas.
  - `helpers/`: utilidades (cache, validaciones, permisos).
  - `middleware/`: middlewares (CSRF, auth, seguridad, rate limit).
  - `models/`: modelos de dominio.
  - `repositories/`: acceso a datos (SQL Server).
  - `security/`: utilidades de seguridad (headers, password, validaciones).
  - `services/`: logica de negocio.
  - `views/`: vistas PHP (login, dashboard).
- `config/`
  - `app.php`: configuracion global y CORS.
- `public/`
  - `index.php`: front controller y definicion de rutas.
  - `.htaccess`: reglas de rewrite para rutas.
  - `assets/`: JS/CSS/imagenes del frontend.
- `views/`: (duplicado de `app/views`), contiene vistas del frontend.
- `vendor/`: dependencias PHP (phpunit, etc).
- `tests/`: pruebas unitarias e integracion.
- `cache/`: cache local.
- `logs/`: logs de aplicacion.
- `.encryption_key`: clave de cifrado usada por utilidades internas.
- `.env`: variables de entorno (no commitear con secretos).

## Pruebas
```bash
composer test
composer test:unit
composer test:integration
composer test:coverage
```

## Notas operativas
- El front controller es `public/index.php`.
- La sesion se usa para autenticacion (middleware `AuthMiddleware`).
- CSRF activo por defecto (middleware `CsrfMiddleware`).
- Base de datos: SQL Server via `sqlsrv` (ver `app/config/Database.php`).

## Diagrama (alto nivel)
Flujo general de la aplicacion:
```text
+-----------------+
|     Usuario     |
+-----------------+
         |
         v
+-------------------------+
| Interfaz Web (Views)    |
| login / dashboard       |
+-------------------------+
         |
         v
+-------------------------+
| Router / Front Control  |
| public/index.php         |
+-------------------------+
         |
         v
+-------------------------+
| Middlewares             |
| Security / CSRF / Auth  |
+-------------------------+
         |
         v
+-------------------------+          +-------------------------+
| Controladores           | <------> | Integraciones PIDE      |
+-------------------------+          | RENIEC / SUNAT / SUNARP |
         |                           +-------------------------+
         v
+-------------------------+
| Servicios               |
+-------------------------+
         |
         v
+-------------------------+
| Repositorios            |
+-------------------------+
         |
         v
+-------------------------+
| SQL Server              |
+-------------------------+
```
