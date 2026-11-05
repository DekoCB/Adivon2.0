# Despliegue en Hostinger (subdominio, con acceso SSH)

Guía paso a paso para subir el sistema ADIVON a un subdominio de Hostinger.
Está escrita asumiendo un hosting Hostinger con SSH habilitado (Business,
Cloud o VPS) — no un hosting compartido básico sin SSH.

## 0. Qué ya está preparado en el repo (no hace falta tocar nada)

- `.htaccess` en la raíz del proyecto ya redirige todo a `public/` — sirve
  tanto si configuras el *document root* del subdominio directo a `public/`
  (recomendado) como si Hostinger solo te deja apuntar a la carpeta raíz del
  proyecto.
- `config/app.php` ya tiene `timezone` en `America/Lima` por defecto.
- El scheduler (`routes/console.php`) ya corre en hora de Lima
  independientemente de la zona horaria del servidor — solo falta que algo
  llame a `php artisan schedule:run` cada minuto (ver paso 6).
- `database/seeders/DatabaseSeeder.php` ya separa seeders de producción
  (roles, catálogo, usuario admin) de los de desarrollo (usuarios de
  prueba) — estos últimos NO corren si `APP_ENV` no es
  `local`/`development`/`testing`.
- No hay rutas de archivo ni URLs de `localhost`/XAMPP hardcodeadas en el
  código de la aplicación (`app/`, `resources/`).
- No se usan colas (`QUEUE_CONNECTION`) de verdad — ningún Job implementa
  `ShouldQueue` todavía, así que `QUEUE_CONNECTION=sync` es suficiente, no
  se necesita un worker corriendo todo el tiempo.
- `CertificadoService` (conversión de certificados SUNAT .pfx → .pem) ya
  usa `openssl_pkcs12_read()` nativo de PHP como método principal — no
  depende de `proc_open`/`exec`, así que funciona aunque el hosting los
  tenga deshabilitados (ver paso 8 para la limitación real que esto sí
  tiene con certificados muy antiguos).

## 0.1 Ya verificado en el servidor real de este proyecto (`adivon2.leongrup.com`)

Esto ya se probó por SSH contra el subdominio real, no es solo teoría:

| Verificación | Resultado |
|---|---|
| Ruta real del subdominio | `~/domains/adivon2.leongrup.com/public_html` (hay un archivo `DO_NOT_UPLOAD_HERE` en la carpeta padre — **subir el proyecto dentro de `public_html`**, no al nivel de arriba) |
| PHP (SSH/CLI) | 8.2.35 |
| PHP (web, LiteSpeed) | 8.3.36 — **distinto al de CLI**, ambos ≥ 8.2 así que son compatibles con `composer.json`, pero correr `composer install`/`artisan` por SSH usa la 8.2 mientras la app real corre en 8.3; si algo se comporta distinto entre CLI y web, es la primera cosa a revisar |
| Extensiones PHP | `bcmath curl dom exif fileinfo gd mbstring openssl pdo_mysql soap xml zip` — todas las que necesita el sistema (dompdf, phpspreadsheet, simple-qrcode, intervention/image) están presentes |
| `proc_open`/`exec`/`shell_exec`/`passthru`/`popen`/`symlink`/`link` | **Deshabilitados** (`disable_functions` del PHP de este hosting) — confirmado con una prueba real y también en el despliegue real: rompe el hook `post-autoload-dump` de Composer (hay que usar `--no-scripts` + `package:discover` manual) y `artisan storage:link` (symlink real imposible; el disco `public` ahora apunta directo a `public/storage`, sin symlink). Ver paso 6. `CertificadoService` tampoco depende de `proc_open` como método principal (ver paso 8) |
| `.user.ini` / `auto_prepend_file` | Deshabilitado (`user_ini.filename` vacío) — no sirve como mecanismo para configurar nada a nivel de proceso |
| `open_basedir` | Sin restricción |
| Node.js / npm por SSH | No está instalado — confirma que hay que compilar los assets (`npm run build`) en otra parte y subir `public/build/` (ver paso 5) |
| Composer por SSH | Disponible (`composer -V` funciona) |
| Conectividad saliente a SUNAT | **Probada y funciona**: los 3 endpoints de Greenter (`FE_BETA`, `FE_PRODUCCION`, `FE_CONSULTA_CDR`) respondieron HTTP 200 en menos de 1 segundo desde este servidor |
| Lectura de certificados .pfx con cifrado moderno (AES) | **Probada y funciona** con `openssl_pkcs12_read()` nativo, sin necesitar `proc_open` |
| Lectura de certificados .pfx con cifrado antiguo (RC2/3DES) | **Confirmado que NO funciona** en este hosting — ver la explicación completa y qué hacer en el paso 8 |

## 1. Crear el subdominio en hPanel

1. hPanel → Sitios web → (tu dominio) → Subdominios → crear, p.ej.
   `sistema.tudominio.com`.
2. Configura el *document root* del subdominio apuntando directamente a
   `.../sistema.tudominio.com/public` si hPanel lo permite (más limpio y
   seguro: deja fuera del acceso web todo lo que no es `public/`). Si no te
   deja elegir una subcarpeta como raíz, usa la carpeta que te dé por
   defecto — el `.htaccess` de la raíz del repo ya hace el reenvío a
   `public/`.
   - En la cuenta real de este proyecto (`adivon2.leongrup.com`), hPanel
     creó la ruta `~/domains/adivon2.leongrup.com/public_html` como
     document root fijo, con un archivo `DO_NOT_UPLOAD_HERE` en la carpeta
     padre advirtiendo que NO se suba nada ahí — todo el proyecto (tal cual,
     con `public/` como subcarpeta) va dentro de ese `public_html`. El
     `.htaccess` de la raíz del repo ya maneja el reenvío a `public/` para
     este caso exacto.
3. Activa el certificado SSL (Hostinger ofrece Let's Encrypt gratis desde
   hPanel → SSL) y fuerza HTTPS.

## 2. Base de datos

1. hPanel → Bases de datos → MySQL → crear una base y un usuario dedicados
   para este sistema (no reutilices una base de otro proyecto).
2. Anota host, nombre de base, usuario y contraseña — van al `.env`.

## 3. Subir el código por SSH

```bash
ssh usuario@tu-servidor-hostinger
cd ~/domains/tudominio.com/sistema.tudominio.com   # o la ruta real del subdominio
git clone <url-del-repo> .
# o, si ya existe el repo: git pull origin main
```

Verifica qué hay disponible en el servidor:

```bash
php -v            # debe ser 8.2 o superior
composer -V       # si no existe, usar el Composer que ofrece hPanel, o
                   # instalar composer.phar manualmente
node -v; npm -v    # puede no estar disponible — ver paso 5
```

Si Hostinger tiene varias versiones de PHP instaladas (selector de PHP en
hPanel), selecciona 8.2+ para el dominio/subdominio **antes** de correr
`composer install`, porque Composer respeta los requisitos de
`composer.json` (`"php": "^8.2"`) contra el PHP del CLI.

## 4. Archivo `.env` de producción

Crear `.env` en la raíz del proyecto en el servidor (nunca se sube por git:
está en `.gitignore`). Punto de partida — ajustar los valores en mayúsculas:

```env
APP_NAME="ADIVON"
APP_ENV=production
APP_KEY=                          # generar con: php artisan key:generate
APP_DEBUG=false
APP_URL=https://sistema.tudominio.com
APP_TIMEZONE=America/Lima
APP_LOCALE=es

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=TU_BASE_DE_DATOS
DB_USERNAME=TU_USUARIO_MYSQL
DB_PASSWORD=TU_PASSWORD_MYSQL

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true        # true porque todo corre bajo HTTPS
SESSION_DOMAIN=null               # dejar null: la cookie queda atada solo
                                   # a este subdominio, no se comparte con
                                   # el resto de tudominio.com

CACHE_STORE=database
QUEUE_CONNECTION=sync             # no hay Jobs en cola reales en el sistema

FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=notificaciones@tudominio.com
MAIL_PASSWORD=TU_PASSWORD_DE_ESE_CORREO
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=notificaciones@tudominio.com
MAIL_FROM_NAME="${APP_NAME}"

# Usuario administrador que crea AdminUserSeeder (solo se usa en el primer seed)
ADMIN_EMAIL=admin@tudominio.com
ADMIN_NAME="Administrador"
ADMIN_PASSWORD=UnaContraseñaLargaYUnica123!
```

Notas:
- `APP_KEY` se genera en el propio servidor, no se copia del `.env` local.
- Crea primero el correo `notificaciones@tudominio.com` (o el que uses)
  desde hPanel → Correo, y usa esas credenciales SMTP reales.
- Cambia `ADMIN_PASSWORD` apenas inicies sesión la primera vez.

## 5. Dependencias y build de assets

```bash
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
php artisan package:discover --ansi
php artisan key:generate --force
```

(El `--no-scripts` y el `package:discover` manual son por el mismo motivo
que se explica en el paso 6 — `proc_open` deshabilitado en este hosting.)

**Compilar los assets (Vite/Tailwind/Vue):** `public/build/` está en
`.gitignore` y no se sube por git, así que tiene que generarse en algún
lado antes de que la app sirva nada con estilos/JS.

- Si Node está disponible por SSH en el servidor:
  ```bash
  npm ci
  npm run build
  ```
- Si no hay Node en el servidor (lo más común en hosting compartido/Business
  de Hostinger): compílalo **en tu máquina local** con
  `npm ci && npm run build` y sube la carpeta `public/build/` generada por
  SFTP/SCP/rsync (es la única carpeta que necesitas subir fuera de git para
  que el frontend funcione).

## 6. Migraciones, seed inicial y primer arranque

```bash
php artisan migrate --force
php artisan db:seed --force      # crea roles, catálogo base y el admin de ADMIN_EMAIL
mkdir -p public/storage          # ver nota abajo — reemplaza a "artisan storage:link"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
chmod -R ug+rwX storage bootstrap/cache
```

**Nota sobre `composer install` y `storage:link` en este hosting específico**
(ya resuelto en el código, se explica para que no sorprenda al leer los
logs): este hosting tiene `symlink()`, `exec()` y `proc_open` deshabilitados.
Por eso:
- `composer install` debe correr con `--no-scripts`, y después correr
  manualmente `php artisan package:discover --ansi` — el hook
  `post-autoload-dump` de Composer necesita `proc_open` para ejecutar ese
  comando como subproceso, y sin `--no-scripts` el `composer install`
  completo falla ahí (aunque ya haya instalado todo lo demás
  correctamente).
- `artisan storage:link` no se usa: necesita `symlink()`/`exec()` para crear
  el enlace `public/storage → storage/app/public`, y ninguno de los dos
  está disponible. En su lugar, `config/filesystems.php` ya configura el
  disco `public` para apuntar directamente a `public/storage` (sin pasar
  por `storage/app/public` ni por ningún symlink) — por eso acá solo hace
  falta crear esa carpeta, no enlazarla.

`deploy/deploy.sh` ya hace ambas cosas correctamente.

(Todo esto ya está encapsulado en `deploy/deploy.sh` — para despliegues
posteriores basta con `git pull && bash deploy/deploy.sh`, el seed no se
repite ahí a propósito porque ya existen los datos.)

## 7. Cron: tareas programadas (obligatorio)

El sistema depende del scheduler de Laravel para dos tareas reales:
cierre automático de cajas (`caja:cerrar-automatico`, 00:01 hora Lima) y
marcado de cuentas por cobrar/cuotas vencidas (00:05 hora Lima). Sin esto
NO se ejecutan solas.

En hPanel → Avanzado → Cron Jobs (o `crontab -e` por SSH), agregar, cada
minuto:

```
* * * * * cd /ruta/completa/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Usa la ruta absoluta real del proyecto en el servidor. Por SSH, `php` ya
resuelve a la 8.2.35 sin necesitar ruta completa, pero el entorno de cron
es más limitado que una sesión interactiva — si la tarea no corre, probar
con la ruta completa del binario (verla con `which php` por SSH) antes de
asumir que es otro el problema.

## 8. Verificaciones específicas de este sistema

Ya probadas contra el servidor real de este proyecto (ver tabla del
paso 0.1) — esto es lo que encontramos y cómo quedó resuelto:

- **Extensiones PHP**: todas las que usa el sistema (`barryvdh/laravel-dompdf`,
  `phpoffice/phpspreadsheet`/`maatwebsite/excel`,
  `simplesoftwareio/simple-qrcode`, `intervention/image`) ya están activas
  en el PHP de este hosting — no requiere ningún cambio en el selector de
  PHP de hPanel.
- **`proc_open`/`exec` deshabilitados**: confirmado, es una restricción real
  de este hosting (no un supuesto). `CertificadoService` ya se adaptó: usa
  `openssl_pkcs12_read()` nativo de PHP como método principal (no necesita
  `proc_open`), con el método anterior (proceso externo `openssl -legacy`)
  como *fallback* solo para cuando `proc_open` sí esté disponible (p.ej. en
  desarrollo local).
- **Certificados digitales SUNAT (.pfx → .pem) — limitación real confirmada**:
  un certificado con cifrado **moderno (AES)** se lee perfecto en este
  servidor con el método nativo. Un certificado con cifrado **antiguo
  (RC2/3DES, típico en certificados SUNAT viejos)** **no se puede leer en
  este hosting**: OpenSSL 3.x necesita activar su *provider* "legacy" para
  esos algoritmos, y eso solo se puede hacer con la variable de entorno
  `OPENSSL_CONF` leída *antes* de que el proceso de PHP arranque — algo que
  no se puede cambiar desde dentro de un request (se probó con `putenv()` y
  con `.user.ini`/`auto_prepend_file`; este hosting además tiene
  `.user.ini` deshabilitado por completo). Si al subir un certificado real
  aparece el mensaje "cifrado antiguo (RC2 o 3DES)...", la solución es
  pedir al proveedor del certificado un .pfx re-exportado con cifrado AES
  (o convertirlo una vez en una PC local con openssl, como indica el propio
  mensaje de error), no es un bug a perseguir en el código.
- **Emisión de comprobantes a SUNAT (Greenter)**: probado — los 3 endpoints
  (`FE_BETA`, `FE_PRODUCCION`, `FE_CONSULTA_CDR`) responden correctamente
  desde este servidor. Aun así, probar con una venta real de prueba antes
  de dar por cerrado el despliegue, porque esto solo confirma que el WSDL
  responde, no que el envío SOAP completo con el certificado real funcione
  de punta a punta.
- **Zona horaria del servidor vs `APP_TIMEZONE`**: Laravel ya fuerza
  `America/Lima` vía `config/app.php`, así que no debería requerir cambios
  en el PHP del servidor, pero si ves fechas corridas revisa también
  `date.timezone` en el `php.ini` de Hostinger.

## 9. Despliegues siguientes (una vez configurado todo lo anterior)

```bash
cd /ruta/al/proyecto
git pull origin main
bash deploy/deploy.sh
```

Si hubo cambios en `resources/js`, `resources/css` o `package.json`, repetir
también el paso de build de assets (paso 5) antes o después del script,
porque `deploy.sh` no compila assets (ver razón en el paso 5).
