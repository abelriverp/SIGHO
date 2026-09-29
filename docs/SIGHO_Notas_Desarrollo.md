# SIGHO — Notas de desarrollo

> Contexto técnico, decisiones de diseño y pendientes del proyecto SIGHO. Complementa el código: aquí está el **porqué** de cada decisión. Léelo antes de tocar cualquier módulo mencionado.
>
> Autor/arquitecto: Abel Rivera Pérez. Última consolidación: septiembre 2026.
> Los ejemplos con nombres o correos son ilustrativos; no contienen datos reales de huéspedes.

---

## 0. Convenciones generales

- Stack: Laravel 11, Tailwind CSS, Alpine.js, MySQL (XAMPP). `composer.json` requiere `"php": "^8.2"`.
- Ruta local: `C:\xampp\htdocs\Proyecto_Estadias`. Repo: `github.com/abelriverp/SIGHO`.
- Entregas de código: **archivos completos de reemplazo con ruta exacta de Windows, nunca diffs parciales.**
- Multipropiedad: `hotel_code` por propiedad (variable de entorno `HOTEL_CODE`), trait `BelongsToHotel` + `HotelScope`. En consola no hay usuario autenticado, así que `HotelScope` no filtra: los comandos programados deben filtrar `hotel_code` a mano.
- Roles: `admin` / `gerente` / `tecnico` / `usuario`. `User::puedeVerTodos()` = admin o gerente. **No se crean roles nuevos**: agregar uno obliga a auditar cada `isAdmin/isTecnico/isGerente/isUsuario/puedeVerTodos/tieneModulo` del proyecto.
- Acceso a módulos: middleware `modulo:<clave>`, asignado por usuario en Admin > Usuarios > Módulos. Rutas de supervisión: middleware `supervisor` / `CheckAdmin`.
- Policies regresan `Illuminate\Auth\Access\Response` (no bool) y el controlador las lee con `Gate::inspect(...)->denied()` / `->message()` para mostrar el motivo específico.
- `AuditLog::registrar()`: la historia nunca se sobreescribe.
- Tests corren contra **SQLite** (`phpunit.xml`): no usar SQL exclusivo de MySQL en `orderByRaw`/`whereRaw` (ej. `FIELD()`, `UPDATE...JOIN`). Usar `CASE WHEN` y query builder.
- MySQL limita nombres de índice/FK a 64 caracteres: en tablas con nombres largos (`recepcion_*_items`) poner nombres explícitos cortos.
- Carbon 3: `diffInDays()` regresa float; castear con `(int)`.
- Correos: para tablas usar `@component('mail::table')` en una vista markdown propia; las tablas markdown crudas dentro de `->line()` no se renderizan.
- dompdf no renderiza bien emojis: no usarlos en vistas PDF.

## 1. Entorno de desarrollo con Claude (sandbox)

- El sandbox de Claude no trae PHP ni permite `sudo`/`apt-get install`. Sí se puede obtener un PHP 8.3 real en espacio de usuario para linting (`php -l`):

```
mkdir -p /tmp/phplocal && cd /tmp/phplocal
apt-get update
apt-get download php8.3-cli php8.3-common libargon2-1   # + php8.3-mbstring si se ejecuta lógica de strings
mkdir extracted
for f in *.deb; do dpkg-deb -x "$f" extracted; done
extracted/usr/bin/php8.3 -v
```

- Hay que repetir la receta en cada sesión. Solo sirve para sintaxis y scripts standalone; **no** puede correr `artisan migrate`/`test` contra la base real, porque MySQL corre en el XAMPP local (127.0.0.1:3306).
- Siempre indicar a Abel qué comandos correr localmente (`php artisan migrate`, `php artisan test`, seeders). Nunca afirmar que las pruebas pasan si solo se verificó sintaxis.
- Si `git diff` no refleja un cambio recién hecho, verificar con Read y reescribir con heredoc antes de hacer commit. Revisar siempre el `git diff` antes de commit.
- Los hallazgos de una revisión de código deben verificarse contra el disco real del usuario. Una copia subida al sandbox puede estar desactualizada (ya hubo falsos positivos por esto).
- Cualquier servicio que dependa de cómo una librería de terceros extrae texto de un PDF necesita al menos una prueba de integración contra un PDF real con esa misma librería. Los fixtures de texto ya extraído no bastan.

## 2. Motor de SLA (2026-07-30)

- `App\Services\SlaService`: única fuente de verdad de horas límite y estado (ok / por_vencer / vencido / cumplido / incumplido). Configurable en `/tickets/sla` (admin y gerente) vía tabla `sla_politicas`, con cascada: hotel+depto > hotel > depto global > default.
- Defaults de fábrica (si no hay política): Alta 24 h / Media 72 h / Baja 168 h. Antes vivían hardcodeados en `AlertasService::HORAS_VENCIDO`.
- **No tocar**: `DashboardMetricsService::SLA_HORAS = 24` (gauge ejecutivo de cumplimiento) es una regla de negocio distinta y plana, con ~6 tests que la asumen. Unificarlo requiere renegociar la regla con Abel y actualizar tests.
- Comando `tickets:escalar-sla` (cada hora): **solo notifica** a admin/gerente, no sube la prioridad (decisión explícita de Abel). Columna `tickets.sla_escalado_at`.
- Tests: `tests/Feature/SlaServiceTest.php`.

## 3. Visibilidad por rol en el Dashboard (RESUELTO 2026-07-30)

- `DashboardController` separa `$verTodo` (admin/gerente) de `$esTecnico`. Las tarjetas de tickets (`ticketsAbiertos`, `ticketsTotales`, `ultimosTickets`) respetan el mismo modelo de 3 niveles que `AlertasService`, `DashboardMetricsService` y `TicketController`.
- `suministrosBajos` y `totalEquipos` muestran toda la propiedad al técnico a propósito, porque `InventarioItem` y `Asset` no tienen `departamento_destino`.

## 4. Migración a Policies (2026-07-30)

- Hallazgo: `AssetController`, `InventoryController`, `NetworkController`, `UnidadController`, `CondominoController`, `RevisionController` y `ActivoItemController` no tenían ninguna verificación de rol; solo el middleware del módulo.
- Alcance acordado: solo **eliminar** quedó restringido a admin/gerente. Crear y editar siguen abiertos a quien tenga el módulo; no se inventaron reglas sin preguntar.
- Trait `App\Policies\Concerns\RestringeEliminacionASupervisor` (`delete() => $user->puedeVerTodos()`), usado por `AssetPolicy`, `RevisionPolicy`, `InventarioItemPolicy`, `UnidadPolicy`, `CondominoPolicy`, `NetworkDevicePolicy`, `ActivoItemPolicy` y, después, por las policies de Recepción. Se descubren automáticamente por convención de nombres.
- `TicketPolicy` ampliada: `resolve`, `reasignar`, `confirmar`, `reabrir`, `deshacerResolucion`, `agregarNota`. Solo cubre identidad, rol y departamento. Las reglas de **estado** (status actual, ventana de 30 min para deshacer, límite de reasignaciones) siguen en `TicketController` porque usan `back()->with('error')` en vez de 403.
- Tests: `tests/Feature/TicketPolicyTest.php`, `tests/Feature/DeleteAuthorizationTest.php`.
- Pendiente: reglas más finas para crear/editar o para distinguir gerente de admin requieren una nueva conversación de alcance.

## 5. Portal de huésped por QR (2026-07-30)

- `/qr/{token}` sin login. El huésped puede reportar un problema o pedir amenidades. Un QR por habitación/unidad con token aleatorio (`puntos_qr.token`).
- **Todo termina en un `Ticket` normal** (reutiliza notificaciones, departamentos y SLA). Las amenidades se guardan también en `campos_extra.articulos`.
- El QR **no descuenta stock**: el personal registra `InventarioMovimiento` (`tipo_salida='solicitud_huesped'`) al surtir, para evitar inventario incorrecto si algo no se entrega.
- Dueño de los tickets: `User::cuentaHuespedParaPropiedad()`, una cuenta de sistema con `role='usuario'` por `hotel_code`, creada al primer uso. No existe un rol `huesped`.
- `PuntoQr` (tabla `puntos_qr`) es independiente de `HabitacionActivo` (legacy) y de `ActivoItem`. No mezclarlos.
- Amenidades pedibles: solo `InventarioItem` con `disponible_huesped=true` (default false). Tope: `GuestPortalController::MAX_CANTIDAD_POR_ARTICULO = 5`.
- Enrutamiento fijo: problemas → "Mantenimiento", amenidades → "Ama de Llaves", con fallback al primer departamento activo.
- Administración en `/puntos-qr` (supervisor). El QR se genera en el navegador con `qrcode.js` desde cdnjs; no se agregó paquete de Composer.
- De paso: `SlaPoliticaController::index()` usaba `FIELD()`; se cambió a `CASE WHEN`.
- Tests: `tests/Feature/GuestPortalTest.php`.

## 6. Módulo Operadores (2026-08-10)

- Personas que trabajan en campo **sin cuenta** en el sistema. Sirve para dejar constancia de quién ejecutó el trabajo y contar tickets por operador. Pantalla: `/tickets/operadores`.
- Pivote el mismo día: el catálogo es **compartido por departamento** (`operadores.departamento`, string como `users.departamento`, no FK). `tecnico_id` solo registra quién lo dio de alta.
- Piezas: tabla `operadores` (hotel_code, departamento, tecnico_id, nombre, activo), `tickets.operador_id` (nullable, distinto de `resolved_by`), modelo `Operador` con `scopeDeDepartamento()`, `OperadorPolicy`, `OperadorController`, vistas `resources/views/operadores/*`.
- Decisiones que no deben revertirse:
  - Catálogo, no texto libre, para que el conteo no se rompa por variaciones de escritura.
  - Sin borrado físico en la UI, solo desactivar, para no perder el conteo histórico.
  - Admin puede crear a nombre de cualquier técnico; `hotel_code` y `departamento` se toman del técnico elegido.
  - Al resolver, el `operador_id` se revalida contra `Operador::activos()->deDepartamento($user->departamento)`. Nunca se confía en el id del request.
  - `deshacerResolucion()` limpia `operador_id` igual que `resolved_by`.
  - El backfill usa `chunkById` en vez de `UPDATE...JOIN` (compatibilidad con SQLite).
- De paso: se agregó `Ticket::resolvedBy()` y el detalle del ticket muestra quién lo resolvió.
- Métricas: `DashboardMetricsService::miDesempeno()` incluye `porOperador`; la lista usa `withCount('tickets')`.
- Roadmap acordado:
  1. Hecho: jerarquía/operadores.
  2. Pendiente: calidad (encuesta CSAT al cerrar + tasa de reapertura por técnico usando `veces_reasignado`/`motivo_reapertura`).
  3. Pendiente: organización (base de conocimiento por categoría, vista de carga por técnico/propiedad, escalamiento automático a gerente si un ticket se reabre más de N veces).

## 7. Recordatorio obliga a comentar (2026-08-10)

- `Ticket::requiereJustificacionRecordatorio()`: es true si hay `ultimo_recordatorio_at` y ninguna `TicketNota` de un usuario distinto al reportante es posterior. Se calcula sin columna nueva.
- `TicketController::resolve()` queda bloqueado mientras aplique (admin puede saltarlo). `reasignar()` **no** se bloquea, porque ya exige `motivo_reasignacion` de al menos 10 caracteres.
- `tickets/show.blade.php`: banner ámbar, se oculta "Registrar solución" para técnicos y hay un ancla `#agregar-nota`.
- Cualquier voz de soporte cuenta como respuesta. `resolve()` y `reasignar()` crean nota, así que la bandera se limpia sola.

## 8. Resumen quincenal de pendientes para Gerente (2026-08-11)

- Comando `tickets:resumen-pendientes`, en `routes/console.php` con `->cron('0 8 15,30 * *')`. Se usa cron directo porque `monthlyOn()` solo acepta un día. En febrero solo corre el día 15.
- Itera por propiedad con tickets Abierto/Reabierto y manda **un correo** a (gerentes de esa propiedad ∪ todos los admins) vía `ResumenTicketsPendientesNotification`, con la vista markdown `resources/views/emails/tickets/resumen-pendientes.blade.php`.
- Marca los tickets con `DIAS_SIN_SEGUIMIENTO_UMBRAL = 2` días sin nota (mismo umbral que `TicketController::UMBRAL_RECORDATORIO_DIAS`).
- Sin pendientes no se manda correo.
- `--solo-admin` sirve para probar sin avisar a gerentes.
- Verificar localmente con `php artisan schedule:list`.

## 9. Herramientas de Recepción (2026-08-21 en adelante)

Módulo `herramientas_recepcion` (ruta base `recepcion.index`, orden 8 en `ModulosSeeder`). Es un port a Laravel del programa de escritorio en Python de Abel. Depende de `smalot/pdfparser ^2.12`.

### 9.1 Estructura

- Migraciones: `recepcion_email_validaciones(_items)` y `recepcion_extension_detecciones(_items)`, con índices nombrados cortos (`rec_email_items_val_estado_idx`, `rec_ext_det_items_det_id_fk/idx`).
- Modelos: `EmailValidacion(Item)` y `ExtensionDeteccion(Item)`. `BelongsToHotel` solo en los encabezados.
- Servicios puros sin Eloquent: `app/Services/Recepcion/EmailValidatorService.php` y `ExtensionDetectorService.php`.
- Controladores `RecepcionController`, `EmailValidacionController`, `ExtensionDeteccionController`. Las policies usan `RestringeEliminacionASupervisor`.
- **Los PDFs subidos nunca se guardan en disco** (se leen desde `getRealPath()`). Solo se persiste el resultado, por privacidad de los huéspedes.
- Pendiente de operación: activar el módulo a cada usuario de Recepción y revisar `upload_max_filesize`/`post_max_size` en XAMPP.

### 9.2 Validador de Emails

- `tieneMx()` acepta MX o registro A (RFC 5321). Tiene un tope de `MAX_DOMINIOS_A_VERIFICAR = 60` y un presupuesto de `TIEMPO_MAXIMO_DNS_SEGUNDOS = 8.0`; al excederlos asume el dominio válido. El resolver DNS se puede inyectar por constructor para pruebas.
- `EmailValidacionItem::GRUPOS_FILTRO` agrupa los estados crudos para que **tarjetas y filtros usen el mismo mapa**. La clave `FALSO` agrupa FALSO, DOMINIO INVALIDO y FORMATO INVALIDO. Hubo un bug real por agrupar distinto en cada lugar.
- `badgeClass()` y la constante `COLORES` viven en el modelo, no en la vista.
- Etiquetas: "Errores tipográficos" y "Genéricos (OTA)". En `email/index.blade.php` aún dicen "Typos"/"OTA" (pendiente solo si se pide).
- Acentos y ñ: `limpiarEmail()` (ahora `public`) permite `\x{00C0}-\x{00FF}` y usa `mb_strtolower`. El regex de extracción también incluye ese rango con el modificador `/u`. Sin ambos cambios, un correo con Ñ se mutilaba.
- Correos partidos en dos renglones: `unirCorreosPartidosEntreRenglones()` y `esFragmentoDeContinuacion()` corren **antes** de aplanar el texto. Un renglón con "@" sin espacio al final se une con el siguiente si este es corto (≤20), sin espacios y solo contiene letras/puntos.

### 9.3 Detector de Extensiones

- Nombres con `\p{L}`. Se corrigió el bug del script Python que cortaba vocales acentuadas minúsculas.
- `similar_text()` sustituye a `difflib.SequenceMatcher`. Constantes: `UMBRAL_PALABRA = 0.70`, `UMBRAL_MATCH_COMPLETO = 0.55`. `ExtensionDeteccionItem::UMBRAL_CONFIANZA_ALTA = 75.0` con el método `confianzaAlta()`.
- `MAX_HUESPEDES_POR_LISTA = 500`: la comparación es O(n×m), así que el controlador valida el tamaño antes de comparar.
- Estado actual de `scoreNombres($n1, $n2, $pesos = [])`:
  1. **Suma la similitud real** de cada palabra que hace match, no +1 binario.
  2. **Divisor = máximo** (ponderado) de las dos bolsas de palabras, no el promedio.
  3. **Peso por frecuencia**: `calcularPesosPalabras()` da 1/frecuencia según en cuántos **huéspedes distintos** aparece cada palabra (no reservaciones). Se calcula una vez por comparación. Sin `$pesos`, todas las palabras valen 1.0 (compatible hacia atrás).
- Casos de referencia: un nombre de pila distinto pero con letras muy parecidas (tipo "Alonso" vs "Alfonso") más un apellido común ya no pasa el umbral. Un typo real del mismo nombre ("Joan" vs "Juan") con apellido coincidente sí pasa. Las coincidencias exactas siempre dan 100% (invariante matemático).
- **Riesgo conocido a vigilar:** un nombre corto que coincide exacto con uno largo de doble apellido o nombre compuesto (tipo "Perez, Juan" vs "Perez Gomez, Juan Carlos") puede caer bajo el umbral si esas palabras son comunes en el cruce. Si Abel reporta "es la misma persona y ya no sale", mirar aquí primero. La solución probable es no penalizar tanto la segunda palabra de apellido cuando la primera coincide exacto. **No revertir** los fixes anteriores.
- Nunca confiar en el nombre de archivo de un PDF; leer el encabezado del reporte.
- Tests: `tests/Unit/Services/Recepcion/ExtensionDetectorServiceTest.php` (13 casos, incluidas las regresiones sin pesos y con pesos).

### 9.4 Correos de Llegada (2026-08-26)

- Valida el reporte PMS "Profiles with e-mails" **reserva por reserva** (Conf Number | Full Name | Arrival | Departure | Email). Nace porque Calidad descubre los correos inválidos solo cuando rebotan.
- Piezas: migraciones `recepcion_perfil_correo_validaciones(_items)`, modelos `PerfilCorreoValidacion(Item)`, `PerfilesCorreoValidatorService` (compone `EmailValidatorService`), `PerfilCorreoValidacionController`, `PerfilCorreoValidacionPolicy`, vistas `resources/views/recepcion/perfiles/`, ruta `perfiles-correo`.
- Usa `limpiarEmail()` pero **no** `corregirEmail()`: aquí se quiere exhibir el typo, no corregirlo en silencio.
- Clasificación: `SUFIJO_INVALIDO` (mismo nombre de dominio, otra extensión, ej. `.con`) vs `DOMINIO_INVALIDO` (nombre distinto, ej. `gmial`), comparando cada mitad del dominio separada por el último punto. `SIN_CORREO` es una categoría propia. Tarjetas y filtro comparten `PerfilCorreoValidacionItem::GRUPOS_FILTRO`.
- SMTP best-effort (`RCPT TO` al MX) solo en dominios corporativos. **Nunca** en proveedores masivos (`DOMINIOS_SIN_VERIFICACION_SMTP`: gmail, hotmail/outlook, yahoo, icloud, live, msn, aol, prodigy.net.mx). En esos casos `verificacion_smtp = null` (no se intentó), distinto de `NO_CONCLUYENTE` (se intentó sin resultado).
- **Caveat:** muchos ISP bloquean el puerto 25 saliente. Si en producción el SMTP nunca da Aceptado/Rechazado, esa es la primera hipótesis; no necesariamente es un bug.
- Los mensajes de "OK" siempre aclaran qué se sabe del buzón: confirmado / intento no concluyente / no se intentó por política del proveedor.
- Parseo: si el siguiente renglón no abre reserva, no tiene espacios ni comas y es corto, se concatena (caso "…@HOTMAIL.CO" + "M").
- **BUG CRÍTICO ya corregido, léelo antes de tocar `procesarPdf()`:** `$pdf->getText()` de smalot **no** reconstruye el orden visual; sigue el orden literal del content-stream, y este reporte está escrito por columnas. Resultado: 0 reservas. El fix es `extraerTextoPorPosicion(Page $pagina)` con `Page::getDataTm()`: agrupa fragmentos por Y (tolerancia 2.5), ordena por X y procesa página por página. Se verificó de punta a punta contra el PDF real (36/36 reservas).
- Tests: `tests/Unit/Services/Recepcion/PerfilesCorreoValidatorServiceTest.php` (17 casos, pero prueban texto ya extraído; ver la lección de la sección 1).

### 9.5 Exportación a PDF (Correos de Llegada y Validador de Emails)

- Método `pdf()` en ambos controladores: aplica el **mismo filtro** que `show()` (`estado`, `buscar`) **sin paginar**. El nombre de la propiedad se resuelve con `Hotel::where('hotel_code', ...)->value('nombre')`, sin hardcodear.
- `barryvdh/laravel-dompdf`: landscape para Correos de Llegada, portrait para el Validador.
- Vistas `recepcion/perfiles/pdf.blade.php` y `recepcion/email/pdf.blade.php`, con el estilo de `tickets/reportes_pdf.blade.php` (header `#041b32`).
- Rutas `recepcion.perfiles.pdf` y `recepcion.email.pdf`.
- En el link se usa `array_filter($filtros, fn ($v) => $v !== null && $v !== '')` para no descartar un `buscar=0` legítimo.
- Pendiente: primera prueba real de descarga en XAMPP.
- Nota: `tickets/reportes_pdf.blade.php` todavía tiene el nombre del hotel hardcodeado; conviene aplicar el mismo patrón multipropiedad.

## 10. Navegación (`layouts/app.blade.php`)

- Franja de escritorio con `overflow-x-auto` + `min-w-0`. El cluster derecho lleva `shrink-0`.
- Cajón móvil con Alpine (`mobileMenuOpen`) y botón hamburguesa `lg:hidden`.
- **Cada módulo nuevo va en DOS lugares** (franja desktop y cajón móvil), cada uno con su ícono SVG. Todavía no hay una sola fuente de verdad.
- Íconos SVG hechos solo con primitivas (`rect`/`line`/`circle`/`polyline`/`polygon`). Estilo `.nav-pill` / `.nav-pill-active`.
- El texto junto al ícono **siempre** se muestra. El scroll aparece solo cuando los módulos de ese usuario no caben, con `.nav-scroll` (barra de 4px translúcida).
- Lecciones:
  - Nunca ocultar por completo un scrollbar en un contenedor cuyo contenido depende de permisos.
  - No usar un breakpoint de pantalla para decidir cuánto mostrar cuando el contenido varía por usuario.

## 11. Evidencia PDF/TXT en tickets (2026-08-26)

- `archivos[]` al **reportar** un ticket acepta jpg/png/mp4/mov/**pdf/txt** (máximo 6 archivos de 6 MB).
- `fotos_resolucion` sigue siendo solo imágenes, a propósito.
- `show.blade.php` tiene un `@else` para descargar o abrir archivos no gráficos. `create.blade.php` usa `iconoPara()` en JS. `tickets/pdf.blade.php` lista los adjuntos no gráficos como texto (sin emoji).
- No se guarda el nombre original del archivo (`archivos_evidencia` es una lista de rutas). Mostrarlo requiere cambiar el esquema a una lista de objetos y tocar `show`, `index`, `pdf` y `destroy()`.

## 12. Responsividad móvil (hallazgo 2026-08-22, PENDIENTE)

- De 37 vistas con `<table>`, solo 8 usan `overflow-x-auto`. Las demás recortan la tabla en móvil.
- Vistas afectadas: `tickets/index` (línea ~446, usa `overflow-hidden`), `admin/users`, `admin/hoteles`, `admin/departamentos`, `admin/areas`, `admin/anuncios`, `admin/auditoria`, `assets/index`, `inventory/index`, `condominios/*`, `operadores/_tabla`, `puntos-qr/index`, `recepcion/email/*`, `recepcion/extensiones/*`, `tickets/categorias/*`, `tickets/reportes`, `tickets/sla/index`.
- Siguiente paso: agregar `overflow-x-auto` o convertir `tickets/index` a tarjetas en móvil.

## 13. Módulo de Contabilidad para condominios (SOLO DISEÑO, 2026-07-30)

- Documento completo: `docs/Diseno_Modulo_Contabilidad.docx`. No hay migraciones ni código todavía.
- Alcance confirmado:
  - Doble entrada formal (catálogo de cuentas, pólizas, balance, estado de resultados).
  - Firma electrónica (usuario + rol + timestamp).
  - Almacén nuevo, separado de Inventario de Suministros, con 3 controles anti robo hormiga: doble validación de salidas, conteos cíclicos con alertas y alertas por patrón sospechoso. La evidencia fotográfica se descartó.
- Arquitectura:
  - Sin roles nuevos ("Dirección" = admin, "Gerente" = gerente).
  - Modelo nuevo `ActivoFijo` con referencia polimórfica opcional a Asset/ActivoItem.
  - Pólizas inmutables una vez aplicadas; se corrigen con contra-póliza.
  - Baja de activos con 3 firmas: Pendiente_Gerente → Pendiente_Direccion → Aprobada → Aplicada (o Rechazada). Aprobar está separado de Aplicar.
- Fases: A) motor contable → B) almacén → C) alta de activos → D) baja con 3 firmas.
- Por confirmar antes de construir: catálogo de cuentas base, umbrales de alertas y estado de cuenta por condómino.
- Releer el diseño y confirmarlo con Abel antes de empezar.

## 14. Pendientes consolidados

- Correr migraciones y pruebas locales de lo construido sin PHP (SLA, Policies, QR, Operadores, recordatorio, resumen quincenal, Correos de Llegada, PDFs).
- Roadmap de calidad (CSAT, reapertura) y de organización (base de conocimiento, carga de trabajo, escalamiento por reaperturas).
- Responsividad de tablas en móvil.
- Contabilidad para condominios (fases A–D).
- Posibles mejoras de Recepción: exportar CSV, botón de reanalizar, tests de controlador, filtro de palabras-ruido en nombres, unificar etiquetas en `email/index`.
- Quitar el nombre de hotel hardcodeado en `tickets/reportes_pdf.blade.php`.
- Para Cancún: definir el nuevo `HOTEL_CODE` y dar de alta la propiedad.
- Limpieza: borrar `storage/app/_to_delete_tmp_debug` si todavía existe.
