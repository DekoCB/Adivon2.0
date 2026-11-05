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
  código de la aplicación (`app/`, `resources/`). La única referencia a una
  ruta de Windows (`CertificadoService.php`, para convertir certificados
  SUNAT .pfx a .pem) solo se usa si `PHP_OS_FAMILY === 'Windows'`; en Linux
  usa automáticamente el `openssl` del sistema — ver advertencia en el
  paso 7.
- No se usan colas (`QUEUE_CONNECTION`) de verdad — ningún Job implementa
  `ShouldQueue` todavía, así que `QUEUE_CONNECTION=sync` es suficiente, no
  se necesita un worker corriendo todo el tiempo.

## 1. Crear el subdominio en hPanel

1. hPanel → Sitios web → (tu dominio) → Subdominios → crear, p.ej.
   `sistema.tudominio.com`.
2. Configura el *document root* del subdominio apuntando directamente a
   `.../sistema.tudominio.com/public` si hPanel lo permite (más limpio y
   seguro: deja fuera del acceso web todo lo que no es `public/`). Si no te
   deja elegir una subcarpeta como raíz, usa la carpeta que te dé por
   defecto — el `.htaccess` de la raíz del repo ya hace el reenvío a
   `public/`.
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
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
```

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
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
chmod -R ug+rwX storage bootstrap/cache
```

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

Usa la ruta absoluta real del proyecto en el servidor y el binario de PHP
correcto (en Hostinger a veces es `/usr/bin/php8.2` en vez de `php` dentro
de cron — probarlo a mano primero).

## 8. Verificaciones específicas de este sistema (probar después de desplegar)

- **Extensiones PHP**: el sistema usa `barryvdh/laravel-dompdf` (PDFs de
  boletas/facturas), `phpoffice/phpspreadsheet` / `maatwebsite/excel`
  (reportes Excel), `simplesoftwareio/simple-qrcode` e
  `intervention/image`. Verificar en hPanel → PHP → Extensiones (o
  `php -m`) que estén activas: `gd`, `zip`, `xml`, `mbstring`, `bcmath`,
  `fileinfo`, `curl`, `openssl`, `pdo_mysql`. Si falta alguna, se activa
  desde el selector de PHP de hPanel, no se instala por composer.
- **Certificados digitales SUNAT (.pfx → .pem)**: `CertificadoService`
  llama al binario `openssl` del sistema con la opción `-legacy` (los
  certificados SUNAT suelen venir cifrados con RC2/3DES, que OpenSSL 3.x
  desactiva por defecto). Esto requiere que PHP pueda ejecutar procesos
  externos (`proc_open`/`exec` no deshabilitados) y que el `openssl` del
  servidor tenga el *provider* `legacy` disponible. **Probar subiendo un
  certificado real apenas se despliegue** — si falla, revisar
  `disable_functions` en el PHP de Hostinger (hPanel → PHP → Configuración)
  y si el error es sobre el provider "legacy", puede necesitarse ajustar
  `OPENSSL_CONF`/`OPENSSL_MODULES` vía variable de entorno, igual que ya se
  hace para XAMPP en Windows en este mismo archivo.
- **Emisión de comprobantes a SUNAT (Greenter)**: requiere que el servidor
  pueda hacer peticiones salientes HTTPS a los endpoints de SUNAT — esto
  normalmente no está bloqueado en Hostinger, pero probarlo con una venta
  de prueba antes de dar por cerrado el despliegue.
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
