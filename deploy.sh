#!/usr/bin/env bash
#
# Deploy de payroll2025 en el servidor. Lo corre el pipeline de GitHub Actions
# (.github/workflows/deploy.yml), después de copiar los assets ya compilados a
# public/build_nuevo. También se puede correr a mano:
#
#   MAINTENANCE=1 ./deploy.sh    # pone la app en mantenimiento durante el deploy
#
# OJO: en plumada payroll2025 comparte la base de datos con swnov2024. Antes de
# agregar migraciones que toquen tablas sue* compartidas, revisar que no rompan
# a swnov2024 (y viceversa).
set -euo pipefail
cd "$(dirname "$0")"

PHP="$(command -v php || echo php)"
COMPOSER="$(command -v composer || echo composer)"
MAINTENANCE="${MAINTENANCE:-0}"
export COMPOSER_ALLOW_SUPERUSER=1

say()  { printf "\n\033[1;36m==> %s\033[0m\n" "$1"; }
warn() { printf "\033[1;33m[!] %s\033[0m\n" "$1"; }
env_get() {
  local v
  v="$(grep -E "^$1=" .env 2>/dev/null | head -1 | cut -d= -f2- || true)"
  v="${v%\"}"; v="${v#\"}"; v="${v%\'}"; v="${v#\'}"
  printf '%s' "$v"
}

say "Proyecto: $(pwd)"
[ -f .env ] || { warn ".env no encontrado en $(pwd). Abortando."; exit 1; }

say "Backup de la base de datos"
DB_DATABASE="$(env_get DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"
DB_HOST="$(env_get DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
if [ -n "$DB_DATABASE" ] && command -v mysqldump >/dev/null; then
  mkdir -p "$HOME/backups"
  BACKUP="$HOME/backups/payroll_${DB_DATABASE}_$(date +%F_%H%M%S).sql"
  if MYSQL_PWD="$DB_PASSWORD" mysqldump -h "$DB_HOST" -u "$DB_USERNAME" --single-transaction --routines "$DB_DATABASE" > "$BACKUP" 2>/dev/null; then
    echo "Backup OK: $BACKUP ($(du -h "$BACKUP" | cut -f1))"
  else
    warn "No se pudo respaldar '$DB_DATABASE'. Continúo igual."
    rm -f "$BACKUP"
  fi
else
  warn "Sin base o sin mysqldump; salteo el backup."
fi

if [ "$MAINTENANCE" = "1" ]; then
  say "Modo mantenimiento ON"
  maintenance_up() { "$PHP" artisan up || true; }
  trap maintenance_up EXIT
  "$PHP" artisan down || true
fi

say "git pull"
git pull --ff-only

# zonda/hub-connector es un repositorio "path" (../hubadmin/packages/hub-connector):
# en el servidor hay una copia liviana (sparse) de ZondaSoft/hubAdmin con esa carpeta.
if [ -d ../hubadmin/.git ]; then
  say "Actualizando el conector del hub (../hubadmin)"
  git -C ../hubadmin pull --ff-only -q || warn "No se pudo actualizar ../hubadmin; uso la copia actual."
else
  warn "Falta ../hubadmin: composer no va a poder instalar zonda/hub-connector."
fi

say "composer install"
"$COMPOSER" install --no-dev --optimize-autoloader --no-interaction

# El servidor no tiene Node: los assets los compila el pipeline y los deja en public/build_nuevo.
if [ -d public/build_nuevo ]; then
  say "Assets nuevos (public/build_nuevo → public/build)"
  rm -rf public/build_viejo
  [ -d public/build ] && mv public/build public/build_viejo
  mv public/build_nuevo public/build
fi

say "Migraciones (migrate --force)"
"$PHP" artisan migrate --force

say "Limpiando caches"
"$PHP" artisan config:clear
"$PHP" artisan cache:clear || true
"$PHP" artisan view:clear
"$PHP" artisan route:clear

WEB_USER="${WEB_USER:-www-data}"
say "Permisos de storage y bootstrap/cache (dueño: $WEB_USER)"
if id "$WEB_USER" >/dev/null 2>&1; then
  chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache 2>/dev/null || sudo chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache 2>/dev/null || warn "No pude cambiar el dueño de storage/bootstrap/cache."
fi
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

say "Deploy terminado ✔"
