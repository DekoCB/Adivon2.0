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

echo "==> Instalando dependencias PHP (sin dev, autoload optimizado)"
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction

echo "==> Migraciones"
$PHP_BIN artisan migrate --force

echo "==> Enlace de almacenamiento público (storage:link)"
$PHP_BIN artisan storage:link || true

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
