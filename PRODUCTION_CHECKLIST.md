# Checklist antes de pasar este sistema a producción real

Hoy este proyecto corre solo en este XAMPP, sin un servidor de producción
separado. Antes de que empleados del hotel dependan de esto para su trabajo
diario, revisa esta lista. Ninguno de estos cambios afecta tu desarrollo
actual — son para el día que muevas esto a un servidor "real" (o si decides
que este mismo equipo pasa a ser producción).

## 1. Variables de entorno (`.env`)

- [ ] `APP_ENV=production` (hoy: `local`)
- [ ] `APP_DEBUG=false` (hoy: `true`). Con `true`, cualquier error de PHP
      muestra rutas de archivos, nombres de variables y a veces datos de
      conexión a la base de datos directamente en pantalla a quien sea.
- [ ] `APP_KEY` regenerado para producción (`php artisan key:generate`) —
      no reutilices la misma clave de desarrollo.
- [ ] `LOG_LEVEL` en `error` o `warning`, no `debug`.

## 2. Base de datos

- [ ] El usuario de MySQL que usa la app en producción **no debe ser
      `root`** ni tener contraseña en blanco (hoy `DB_USERNAME=root` y
      `DB_PASSWORD` vacío). Crea un usuario dedicado con permisos solo
      sobre la base `inventario`.
- [ ] Respaldos automáticos programados (diarios como mínimo).

## 3. Cuentas y contraseñas

- [ ] Cambiar la contraseña del usuario sembrado por `DatabaseSeeder`
      (`admin@emporio.com` / `admin123`) antes de ir a producción, o
      eliminarlo si ya tienes tu propio usuario Administrador.
- [ ] Confirmar que todo usuario nuevo entra con `must_change_password = true`
      (ya es el comportamiento actual — solo verificarlo).

## 4. Sesión y transporte

- [ ] Servir el sitio por HTTPS (certificado válido).
- [ ] `SESSION_SECURE_COOKIE=true` una vez que haya HTTPS.
- [ ] `APP_URL` apuntando al dominio real, no a `localhost`.

## 5. Rendimiento / despliegue

- [ ] `php artisan config:cache`, `route:cache`, `view:cache` después de
      cada despliegue.
- [ ] `composer install --no-dev --optimize-autoloader`.
- [ ] Permisos de escritura correctos en `storage/` y
      `bootstrap/cache/` para el usuario del servidor web.

## 6. Ya resuelto (no repetir)

- [x] Límite de intentos de login (`throttle:login`, 5 por minuto por
      correo+IP).
- [x] Cada usuario ve solo su propiedad asignada (multipropiedad).
- [x] Bitácora de acciones administrativas críticas (usuarios y propiedades).

Cuando decidas dar el salto a producción, dime y repasamos esta lista juntos
antes de publicarlo.
