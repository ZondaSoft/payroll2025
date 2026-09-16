# Deploy de payroll2025 (con el Motor de Cálculo)

Deploy **manual**: el repo no tiene CI/CD ni scripts de despliegue. Esta guía es para llevar a producción el estado actual de `master`, que incluye el módulo Motor (41 tablas nuevas `motor_*`, 138 rutas, 27 pantallas).

## ⚠️ Lo que hay que tener presente antes de empezar

1. **La base es compartida con el portal RRHH Drakma.** `swnov` la escriben las dos aplicaciones y la tabla `migrations` también es común. `php artisan migrate` desde payroll solo aplica las migraciones de *este* repo, pero el respaldo previo es obligatorio.
2. **Nunca** `migrate:rollback`, `migrate:fresh`, `migrate:refresh`, `migrate:reset` ni `db:wipe`. El plan de vuelta atrás está al final de este documento y no pasa por revertir migraciones.
3. **Nunca correr `php artisan test` en producción.** Los tests exigen la base `payroll2025_test`; el guardarraíl aborta si apuntan a otra, pero el lugar de los tests es desarrollo.
4. **El motor no va a liquidar apenas se despliegue, y es correcto.** Ningún dato normativo del Excel está `CONFIRMADO`, así que con `MOTOR_ENTORNO=PRODUCCION` bloquea todo por diseño (fail-closed). Es un módulo que se despliega ahora y se habilita cuando la normativa esté validada. Nada de esto afecta a los módulos existentes (Legajos, Liquidación, LSD, SICOSS), que siguen funcionando igual.

## 1. Respaldo (no negociable)

```bash
mysqldump --single-transaction --routines --triggers -u USUARIO -p swnov \
  | gzip > ~/backups/swnov_$(date +%F_%H%M).sql.gz
ls -lh ~/backups/swnov_*.sql.gz | tail -1     # verificar que pesa lo que debe
```

## 2. Poner el sitio en mantenimiento

```bash
cd /ruta/a/payroll2025
php artisan down --retry=60
```

## 3. Traer el código

```bash
git fetch origin
git log --oneline HEAD..origin/master     # revisar qué entra
git pull origin master
```

> Si producción no usa git, subir los archivos por rsync/FTP **excluyendo** `.env`, `storage/`, `public/build/` (se regenera), `node_modules/` y `vendor/` si se instala en el servidor.

## 4. Dependencias y assets

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build          # regenera public/build; el módulo tiene 27 páginas Vue nuevas
```

## 5. Variables de entorno

Agregar al `.env` de producción (es nueva, ver `.env.example`):

```env
MOTOR_ENTORNO=PRUEBA
```

Dejarla en `PRUEBA` hasta que la normativa esté validada: en `PRODUCCION` el motor exige `CONFIRMADO` en cada alícuota, tope, régimen, básico e incidencia que intervenga, y hoy nada lo está.

## 6. Migraciones

```bash
php artisan config:clear                  # que migrate no lea una config cacheada vieja
php artisan migrate:status | grep -i pending
```

Deben aparecer **exactamente 7 pendientes**, todas `2026_09_15_0000xx_*_motor_*`. Si aparece alguna otra, parar y revisar antes de seguir.

```bash
php artisan migrate --force
```

Crea las 41 tablas `motor_*` y siembra la normativa del Excel (todo `SIN_CONFIRMAR`). Es aditivo: no toca ninguna tabla existente ni nada de Drakma.

> **Caso especial:** si en producción la migración `2025_12_11_173741_create_permission_tables` figurara como pendiente (no debería: está aplicada desde el batch 15), revisá primero si `roles` y `permissions` ya existen. La migración ahora es idempotente y saltea las tablas que encuentre, pero conviene verificarlo antes de correrla.

## 7. Datos iniciales del motor (idempotentes, se pueden repetir)

```bash
php artisan db:seed --class=MotorCatalogosSicossSeeder --force   # catálogos ARCA desde sicoss01s/05s/08s/12s/zonas
php artisan db:seed --class=MotorVersionesInicialesSeeder --force # publica la v1 de los 37 conceptos
```

## 8. Cachés y permisos

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

> Recordá que `config:cache` deja `bootstrap/cache/config.php`, y esa config cacheada **pisa** cualquier `<env>` de phpunit.xml. Es otra razón por la que no se corren tests en producción.

## 9. Levantar y verificar

```bash
php artisan up
```

Verificación mínima:

```bash
php artisan route:list --name=motor | wc -l    # 138
php artisan about | grep -i environment
```

En el navegador, con sesión iniciada: el menú lateral muestra el bloque **Motor de cálculo**. Abrir *Integridad* — debe responder y mostrar el estado (216 incidencias PENDIENTE, códigos sin mapear, etc.). Y comprobar que Legajos, Liquidación y LSD siguen funcionando.

## 10. Puesta en marcha del motor (después del deploy, por pantalla)

El módulo queda desplegado pero inerte hasta cargar los datos que solo puede decidir una persona. La secuencia completa está en [`operacion.md`](operacion.md):

1. *Relaciones laborales* → **Sincronizar desde legajos** (o `php artisan motor:sincronizar-relaciones <empresa>`).
2. *Encuadramiento empresas* → cargar A/B por empresa. Sin esto el motor bloquea todas las contribuciones.
3. *Escalas y básicos* → cargar el básico vigente de cada categoría. El motor no lo toma de `sue001s.bruto`.
4. *Mapeo de novedades* → mapear los códigos de `sue031s` usados en el período.
5. `php artisan motor:normalizar-novedades AAAA-MM`.
6. *Normativa ARCA* → confirmar alícuotas y topes contra ARCA. Recién cuando todo lo que interviene esté `CONFIRMADO` tiene sentido pasar `MOTOR_ENTORNO=PRODUCCION`.

## 11. Volver atrás (sin tocar migraciones)

Las tablas `motor_*` son **aditivas**: ninguna aplicación anterior las lee y dejarlas no rompe nada. Por eso el rollback es solo de código:

```bash
php artisan down
git log --oneline -3                 # identificar el commit previo al deploy
git checkout <commit-anterior>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Las tablas del motor quedan en la base sin consecuencias. **No** se revierten con `migrate:rollback`.

Si hiciera falta deshacer datos (no esquema), restaurar el dump del paso 1 — pero teniendo en cuenta que perdería también lo que Drakma haya escrito desde entonces, así que es la última opción y se coordina con el portal.
