# PlantCore Service Desk — instalación inicial controlada

Estado: código preparado para validación; **NO se ha publicado en GitHub ni probado con MySQL en este entorno**. Este ZIP no corresponde a un commit remoto.

## Hostinger (solo Service Desk)

1. En hPanel verifica el directorio raíz real de `service-desk.devalejandre.com`; el respaldo contiene `public_html/service-desk/default.php`, pero no demuestra la configuración del subdominio.
2. Si el document root es `/domains/devalejandre.com/public_html/service-desk`, coloca exclusivamente los archivos de `public_html/service-desk/` allí. Conserva `default.php` como copia de respaldo o elimínalo **solo tras validar** que el subdominio apunta a esa ruta.
3. Coloca la carpeta `apps/service-desk/` en `/domains/devalejandre.com/apps/service-desk/`, fuera de la raíz pública. Comprueba que la ruta absoluta del `public_html/service-desk/index.php` coincide con la del servidor.
4. Crea una base y un usuario MySQL nuevos, con permisos solo sobre esa base. No uses bases de Traceability o LPA.
5. Crea `apps/service-desk/.env` a partir de `deploy/hostinger.env.example` y ajusta APP_URL, APP_KEY, DB_* y las sesiones. Nunca subas o reemplaces el `.env` de otra instalación.
6. Requiere PHP >=8.3, Composer y SSH o una compilación externa con `vendor/` compatible. Este ZIP **no contiene vendor**. Instala dependencias de Composer, genera `APP_KEY` **una sola vez** y ejecuta migraciones tras respaldo. Para una instalación nueva sin datos: `bash deploy/deploy-hostinger.sh`.
7. Desde SSH crea la cuenta propietaria con `php artisan service-desk:create-admin tu_correo@dominio.com --owner --force`; el comando solicita la contraseña de forma privada.
8. Verifica HTTPS, `/login`, creación/asignación de ticket, notas internas, transferencia TI→RH y acceso por departamento. Comprueba que URLs `/.env`, `/vendor/autoload.php` y `/storage/logs` no son accesibles. No introducir datos reales en la demo.

## Precauciones

- Nunca ejecutar `migrate:fresh`, `db:wipe`, `db:seed` ni `composer install` desde una carpeta distinta al Service Desk.
- No sobrescribir `.env`, `storage/`, archivos de usuarios, ni respaldos de producción; este ZIP no incluye ninguno de esos datos.
- Cada actualización debe tener su SHA verificado en GitHub antes de etiquetarse como versión sincronizada.
- Reversión: restaurar archivos de la versión previa y respaldos MySQL. **Las migraciones no siempre son reversibles sin pérdida de datos.** No hacer rollback automático en producción.
- Las notas internas están ligadas al departamento de origen: cuando se transfiere un ticket solo viajan las respuestas públicas. El administrador global conserva acceso a auditoría.
