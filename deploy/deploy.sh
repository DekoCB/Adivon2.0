#!/usr/bin/env bash
# Script de despliegue para el subdominio en Hostinger (vía SSH).
#
# Uso: parado en la raíz del proyecto en el servidor (donde está este
# archivo dentro de deploy/), ejecutar:
#   bash deploy/deploy.sh
#
# Asume que:
#   - El .env de producción ya existe en el servidor (no se toca aquí).
#   - El código ya fue actualizado (git pull) antes de llamar a este script,
#     o se llama después de subir los archivos nuevos.
#   - "php" en el PATH es la versión correcta (en Hostinger normalmente hay
#     que usar la ruta completa, p.ej. /usr/bin/php8.3 — ajustar PHP_BIN).
#   - Composer está disponible como "composer" en el PATH (en Hostinger
#     suele venir instalado; si no, usar "php composer.phar").

set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

echo "==> Modo mantenimiento ON"
$PHP_BIN artisan down --retry=30 || true

echo "==> Instalando dependencias PHP (sin dev, autoload optimizado, sin scripts)"
# --no-scripts: en hostings con proc_open deshabilitado (como el de este
# proyecto), el hook post-autoload-dump de Composer ("@php artisan
# package:discover --ansi") falla porque Composer necesita proc_open para
# correrlo como subproceso. Se corre manualmente después, directo con PHP
# (artisan por sí solo no necesita proc_open para esto).
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --no-scripts
$PHP_BIN artisan package:discover --ansi

echo "==> Migraciones"
$PHP_BIN artisan migrate --force

echo "==> Carpeta de almacenamiento público"
# No se usa "artisan storage:link": este hosting tiene symlink()/exec()
# deshabilitados, así que el symlink no se puede crear. El disco "public"
# en config/filesystems.php ya apunta directo a public/storage (sin
# symlink de por medio), así que solo falta que la carpeta exista.
mkdir -p public/storage

echo "==> Limpiando y regenerando cachés de configuración/rutas/vistas"
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache

echo "==> Permisos de escritura (storage y bootstrap/cache)"
chmod -R ug+rwX storage bootstrap/cache

echo "==> Modo mantenimiento OFF"
$PHP_BIN artisan up

echo "==> Listo. Verificar la app y revisar storage/logs/laravel.log si algo falla."
