# SIGHO — Instalación limpia en una propiedad nueva

Guía para montar SIGHO en otra propiedad (ej. Hotel Emporio Cancún) **sin ningún
dato de la instalación anterior**. El repositorio no contiene datos operativos:
tickets, inventario, usuarios, fotos y PDFs viven solo en la base MySQL y en
`storage/app` del servidor donde corría. Por eso "limpiar" significa instalar
desde el código y **no copiar** ni la base de datos ni `storage/`.

## Qué NO se copia de la instalación anterior

- La base de datos MySQL (`inventario` en Mazatlán).
- `storage/app/*` (evidencias de tickets, fotos de resolución, PDFs de activos).
- `public/storage` (es un enlace simbólico; se vuelve a crear).
- El archivo `.env` (trae la `APP_KEY` y credenciales de la otra propiedad).
- `vendor/` y `node_modules/` (se reinstalan).

## Pasos (XAMPP en Windows)

1. Copiar el proyecto a `C:\xampp\htdocs\SIGHO` (o clonar el repo).
2. En una terminal dentro de esa carpeta:
   ```
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
3. Editar `.env`:
   - `HOTEL_CODE` → código definitivo de la propiedad (máx. 10 caracteres).
   - `HOTEL_NOMBRE`, `HOTEL_CIUDAD`, `HOTEL_UBICACION` → textos de login, menú y PDFs.
   - `APP_TIMEZONE=America/Cancun` (Cancún es UTC-5 todo el año).
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` → crear antes la base vacía en phpMyAdmin
     con cotejamiento `utf8mb4_unicode_ci`.
   - `MAIL_*` → servidor de correo de la propiedad (notificaciones y resumen quincenal).
4. Crear tablas y catálogos base (propiedad, módulos, departamentos, categorías de tickets):
   ```
   php artisan migrate --seed
   ```
5. Crear el primer Administrador (pide la contraseña oculta):
   ```
   php artisan sigho:crear-admin
   ```
6. Enlace para archivos subidos y compilación de estilos:
   ```
   php artisan storage:link
   npm install
   npm run build
   ```
7. Programar el scheduler (cierre automático, escalamiento SLA, resumen quincenal):
   en el Programador de tareas de Windows, cada minuto:
   `C:\xampp\php\php.exe C:\xampp\htdocs\SIGHO\artisan schedule:run`
8. Entrar con el Administrador y dar de alta: usuarios, áreas, módulos por usuario,
   políticas de SLA y puntos QR. Revisar `PRODUCTION_CHECKLIST.md` antes de abrirlo
   al personal.

## Verificación rápida

- `php artisan about` → Timezone y entorno correctos.
- La pantalla de login debe mostrar la ciudad de la nueva propiedad.
- Crear un ticket de prueba y descargar su PDF: el encabezado debe decir el nombre
  de la nueva propiedad y el token debe empezar con el nuevo `HOTEL_CODE`.
- Borrar el ticket de prueba.

## Logo

`public/img/logo.jpg` es el logo genérico EMPORIO (sin ciudad) y sirve para
cualquier propiedad. `public/img/logoc.jpg` dice "MAZATLÁN" y no se usa en
ninguna vista; se puede reemplazar por la versión de la nueva propiedad.
