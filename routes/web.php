<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController, AssetController, RevisionController,
    InventoryController, NetworkController,
    TicketController, AdminSettingsController, UbicacionController,
    ProfileController, DashboardController,
    TicketCategoriaController, SlaPoliticaController,
    GuestPortalController, PuntoQrController, AnuncioController,
    OperadorController, RecepcionController, EmailValidacionController,
    ExtensionDeteccionController, PerfilCorreoValidacionController,
    ConocimientoController, RespuestaPredefinidaController, TicketRecurrenteController,
    ProveedorController, ContratoController, LicenciaController, VencimientoController, BajaActivoController
};
use App\Http\Middleware\CheckAdmin;

// ==========================================
// 🌐 RUTAS PÚBLICAS (ACCESO LIBRE)
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Portal de huésped por QR: sin login, cada habitación/unidad tiene su
// propio enlace impredecible (ver App\Models\PuntoQr). Termina creando un
// Ticket normal — ver GuestPortalController.
// Apagado por defecto (Fase 2, nov 2026): ver PORTAL_HUESPED en config/app.php.
Route::middleware('portal_huesped')->group(function () {
    Route::get('/qr/{token}', [GuestPortalController::class, 'show'])->name('guest.show');
    Route::post('/qr/{token}', [GuestPortalController::class, 'store'])->middleware('throttle:guest-request')->name('guest.store');
    Route::get('/qr/{token}/gracias', [GuestPortalController::class, 'gracias'])->name('guest.gracias');
});

// ==========================================
// 🔒 RUTAS SISTEMA PROTEGIDAS (REQUIEREN LOGIN)
// ==========================================
Route::middleware('auth')->group(function () {

    // 🏠 Pantalla de Inicio
    Route::get('/', function () {
        $user = auth()->user();
        if ($user->must_change_password && !$user->isAdmin()) {
            return redirect()->route('password.change');
        }
        return redirect()->route('dashboard');
    });

    // 📊 Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 🆕 Novedades: anuncios de nuevas funciones/cambios, filtrados a los
    // módulos a los que cada quien tiene acceso (ver AnuncioController).
    Route::get('/novedades', [AnuncioController::class, 'novedades'])->name('novedades.index');

    // Perfil y Seguridad
    Route::get('/cambiar-contrasena', [ProfileController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/cambiar-contrasena', [ProfileController::class, 'updatePassword'])->name('password.change.update');

    // ==========================================
    // 🛡️ MÓDULOS DEL SISTEMA
    // ==========================================

    // Activos y Ubicaciones (Fase 1, nov 2026): catálogo único de activos
    // (antes Seguimiento de Equipos + Periféricos + Inventario de
    // Habitaciones). Ver App\Models\Asset y App\Models\Ubicacion.
    Route::middleware(['modulo:seguimiento_equipos_ti'])->group(function () {
        Route::get('/activos', [AssetController::class, 'index'])->name('assets.index');
        Route::get('/activos/crear', [AssetController::class, 'create'])->name('assets.create');
        Route::get('/activos/exportar', [AssetController::class, 'exportCsv'])->name('assets.export');
        Route::get('/activos/plantilla', [AssetController::class, 'plantillaCsv'])->name('assets.plantilla');
        Route::post('/activos/importar', [AssetController::class, 'importCsv'])->name('assets.import');
        Route::post('/activos/mover-lote', [AssetController::class, 'moverLote'])->name('assets.moverLote');
        Route::resource('assets', AssetController::class)->except(['index', 'create']);

        Route::get('assets/{asset}/revisions/create', [RevisionController::class, 'create'])->name('revisions.create');
        Route::post('assets/{asset}/revisions', [RevisionController::class, 'store'])->name('revisions.store');
        Route::resource('revisions', RevisionController::class)->only(['edit', 'update', 'destroy']);

        Route::post('/ubicaciones/generar-habitaciones', [UbicacionController::class, 'generarHabitaciones'])->name('ubicaciones.generar');
        Route::resource('ubicaciones', UbicacionController::class)->parameters(['ubicaciones' => 'ubicacion']);

        // Fase 3 (nov 2026): proveedores, contratos, licencias, vencimientos,
        // cartas responsivas y bajas con aprobación.
        Route::get('/assets/{id}/responsiva', [AssetController::class, 'responsiva'])->name('assets.responsiva');
        Route::post('/assets/{id}/baja', [BajaActivoController::class, 'store'])->name('bajas.store');
        Route::get('/bajas', [BajaActivoController::class, 'index'])->name('bajas.index');
        Route::post('/bajas/{baja}/aprobar', [BajaActivoController::class, 'aprobar'])->name('bajas.aprobar');
        Route::post('/bajas/{baja}/rechazar', [BajaActivoController::class, 'rechazar'])->name('bajas.rechazar');
        Route::get('/bajas/{baja}/acta', [BajaActivoController::class, 'acta'])->name('bajas.acta');

        Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');

        Route::resource('contratos', ContratoController::class);
        Route::resource('licencias', LicenciaController::class)->except(['show']);
        Route::get('/vencimientos', [VencimientoController::class, 'index'])->name('vencimientos.index');
    });

    Route::middleware(['modulo:inventario_suministros'])->group(function () {
        Route::get('inventory/reportes', [InventoryController::class, 'reportes'])->name('inventory.reportes');
        Route::resource('inventory', InventoryController::class);
        Route::post('inventory/{inventory}/movimiento', [InventoryController::class, 'registrarMovimiento'])->name('inventory.movimiento');
    });

    // Herramientas de Recepción: validador de correos de huéspedes y
    // detector de extensiones de estancia, a partir de los PDF de reservas
    // que exporta el sistema (ver App\Services\Recepcion\*). Port del
    // programa de escritorio que antes usaba Recepción de forma aislada.
    Route::middleware(['modulo:herramientas_recepcion'])->prefix('recepcion')->name('recepcion.')->group(function () {
        Route::get('/', [RecepcionController::class, 'index'])->name('index');

        Route::prefix('emails')->name('email.')->group(function () {
            Route::get('/', [EmailValidacionController::class, 'index'])->name('index');
            Route::get('/nuevo', [EmailValidacionController::class, 'create'])->name('create');
            Route::post('/', [EmailValidacionController::class, 'store'])->name('store');
            Route::get('/{validacion}', [EmailValidacionController::class, 'show'])->name('show');
            Route::get('/{validacion}/pdf', [EmailValidacionController::class, 'pdf'])->name('pdf');
            Route::delete('/{validacion}', [EmailValidacionController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('extensiones')->name('extensiones.')->group(function () {
            Route::get('/', [ExtensionDeteccionController::class, 'index'])->name('index');
            Route::get('/nueva', [ExtensionDeteccionController::class, 'create'])->name('create');
            Route::post('/', [ExtensionDeteccionController::class, 'store'])->name('store');
            Route::get('/{deteccion}', [ExtensionDeteccionController::class, 'show'])->name('show');
            Route::delete('/{deteccion}', [ExtensionDeteccionController::class, 'destroy'])->name('destroy');
        });

        // Reporte "Profiles with e-mails": valida el correo de cada
        // reserva (no solo un conjunto de correos únicos, como el grupo
        // "emails" de arriba) y, cuando el dominio lo permite, intenta
        // confirmar por SMTP si el buzón exacto existe.
        Route::prefix('perfiles-correo')->name('perfiles.')->group(function () {
            Route::get('/', [PerfilCorreoValidacionController::class, 'index'])->name('index');
            Route::get('/nuevo', [PerfilCorreoValidacionController::class, 'create'])->name('create');
            Route::post('/', [PerfilCorreoValidacionController::class, 'store'])->name('store');
            Route::get('/{validacion}', [PerfilCorreoValidacionController::class, 'show'])->name('show');
            Route::get('/{validacion}/pdf', [PerfilCorreoValidacionController::class, 'pdf'])->name('pdf');
            Route::delete('/{validacion}', [PerfilCorreoValidacionController::class, 'destroy'])->name('destroy');
        });
    });

    Route::middleware(['modulo:red_infraestructura'])->group(function () {
        Route::resource('network', NetworkController::class);
        Route::post('/network/port/{id}', [NetworkController::class, 'updatePort'])->name('network.port.update');
    });

    // Catálogo de categorías de tickets (Tipo de solicitud -> Sistema ->
    // Campos), administrable por Admin y Gerente — por eso vive fuera del
    // prefijo /admin (que es solo Admin) y usa el middleware 'supervisor'.
    // IMPORTANTE: este grupo debe registrarse ANTES del grupo `tickets.` de
    // abajo, porque comparte el prefijo /tickets/... y ese otro grupo tiene
    // una ruta comodín GET /tickets/{id} que, si se registrara primero,
    // interceptaría /tickets/categorias tratando "categorias" como {id}.
    Route::middleware(['modulo:gestion_tickets', 'supervisor'])->prefix('tickets/categorias')->name('tickets.categorias.')->group(function () {
        Route::get('/', [TicketCategoriaController::class, 'index'])->name('index');
        Route::get('/crear', [TicketCategoriaController::class, 'create'])->name('create');
        Route::post('/', [TicketCategoriaController::class, 'store'])->name('store');

        Route::get('/subcategorias/crear', [TicketCategoriaController::class, 'subcategoriasCreate'])->name('subcategorias.create');
        Route::post('/subcategorias', [TicketCategoriaController::class, 'subcategoriasStore'])->name('subcategorias.store');
        Route::get('/subcategorias/{subcategoria}/editar', [TicketCategoriaController::class, 'subcategoriasEdit'])->name('subcategorias.edit');
        Route::put('/subcategorias/{subcategoria}', [TicketCategoriaController::class, 'subcategoriasUpdate'])->name('subcategorias.update');
        Route::delete('/subcategorias/{subcategoria}', [TicketCategoriaController::class, 'subcategoriasDestroy'])->name('subcategorias.destroy');
        Route::post('/subcategorias/{subcategoria}/duplicar', [TicketCategoriaController::class, 'subcategoriasDuplicar'])->name('subcategorias.duplicar');

        Route::get('/{categoria}/editar', [TicketCategoriaController::class, 'edit'])->name('edit');
        Route::put('/{categoria}', [TicketCategoriaController::class, 'update'])->name('update');
        Route::delete('/{categoria}', [TicketCategoriaController::class, 'destroy'])->name('destroy');
        Route::get('/{categoria}', [TicketCategoriaController::class, 'show'])->name('show');
    });

    // Política de SLA (horas de resolución/alerta por departamento y
    // prioridad), administrable por Admin y Gerente — igual que categorías
    // de tickets, y por la misma razón debe registrarse ANTES del grupo
    // `tickets.` de abajo (comparte el prefijo /tickets/... y ese grupo
    // tiene una ruta comodín GET /tickets/{id}).
    Route::middleware(['modulo:gestion_tickets', 'supervisor'])->prefix('tickets/sla')->name('tickets.sla.')->group(function () {
        Route::get('/', [SlaPoliticaController::class, 'index'])->name('index');
        Route::get('/crear', [SlaPoliticaController::class, 'create'])->name('create');
        Route::post('/', [SlaPoliticaController::class, 'store'])->name('store');
        Route::get('/{politica}/editar', [SlaPoliticaController::class, 'edit'])->name('edit');
        Route::put('/{politica}', [SlaPoliticaController::class, 'update'])->name('update');
        Route::delete('/{politica}', [SlaPoliticaController::class, 'destroy'])->name('destroy');
    });

    // Administración de puntos QR (habitaciones/unidades) para el portal
    // de huésped. Admin y Gerente, igual que categorías de tickets y SLA.
    Route::middleware(['supervisor', 'portal_huesped'])->prefix('puntos-qr')->name('puntos-qr.')->group(function () {
        Route::get('/', [PuntoQrController::class, 'index'])->name('index');
        Route::get('/crear', [PuntoQrController::class, 'create'])->name('create');
        Route::post('/', [PuntoQrController::class, 'store'])->name('store');
        Route::get('/{puntoQr}/imprimir', [PuntoQrController::class, 'imprimir'])->name('imprimir');
        Route::patch('/{puntoQr}/estado', [PuntoQrController::class, 'toggleActivo'])->name('estado');
        Route::patch('/{puntoQr}/regenerar-token', [PuntoQrController::class, 'regenerarToken'])->name('regenerar-token');
        Route::delete('/{puntoQr}', [PuntoQrController::class, 'destroy'])->name('destroy');
    });

    // Fase 2 (nov 2026): respuestas predefinidas y mantenimiento preventivo.
    // Antes del grupo `tickets.` por la ruta comodín /tickets/{id}.
    Route::middleware(['modulo:gestion_tickets', 'supervisor'])->prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/respuestas', [RespuestaPredefinidaController::class, 'index'])->name('respuestas.index');
        Route::post('/respuestas', [RespuestaPredefinidaController::class, 'store'])->name('respuestas.store');
        Route::put('/respuestas/{respuesta}', [RespuestaPredefinidaController::class, 'update'])->name('respuestas.update');
        Route::delete('/respuestas/{respuesta}', [RespuestaPredefinidaController::class, 'destroy'])->name('respuestas.destroy');

        Route::get('/recurrentes', [TicketRecurrenteController::class, 'index'])->name('recurrentes.index');
        Route::get('/recurrentes/crear', [TicketRecurrenteController::class, 'create'])->name('recurrentes.create');
        Route::post('/recurrentes', [TicketRecurrenteController::class, 'store'])->name('recurrentes.store');
        Route::get('/recurrentes/{recurrente}/editar', [TicketRecurrenteController::class, 'edit'])->name('recurrentes.edit');
        Route::put('/recurrentes/{recurrente}', [TicketRecurrenteController::class, 'update'])->name('recurrentes.update');
        Route::delete('/recurrentes/{recurrente}', [TicketRecurrenteController::class, 'destroy'])->name('recurrentes.destroy');
    });

    // Fase 2: base de conocimiento (lectura para todos con Tickets).
    Route::middleware(['modulo:gestion_tickets'])->prefix('conocimiento')->name('conocimiento.')->group(function () {
        Route::get('/', [ConocimientoController::class, 'index'])->name('index');
        Route::get('/crear', [ConocimientoController::class, 'create'])->name('create');
        Route::post('/', [ConocimientoController::class, 'store'])->name('store');
        Route::get('/{articulo}', [ConocimientoController::class, 'show'])->name('show');
        Route::get('/{articulo}/editar', [ConocimientoController::class, 'edit'])->name('edit');
        Route::put('/{articulo}', [ConocimientoController::class, 'update'])->name('update');
        Route::delete('/{articulo}', [ConocimientoController::class, 'destroy'])->name('destroy');
    });

    Route::middleware(['modulo:gestion_tickets'])->prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/crear', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        // Reportes va antes de /{id} para que "reportes" no se interprete como un id de ticket.
        Route::get('/reportes', [TicketController::class, 'reportes'])->name('reportes');
        Route::get('/reportes/pdf', [TicketController::class, 'reportesPdf'])->name('reportes.pdf');
        // Igual que /reportes: "operadores" también debe ir antes de /{id}
        // para que no se interprete como un id de ticket.
        Route::get('/operadores', [OperadorController::class, 'index'])->name('operadores.index');
        Route::post('/operadores', [OperadorController::class, 'store'])->name('operadores.store');
        Route::patch('/operadores/{operador}', [OperadorController::class, 'update'])->name('operadores.update');
        Route::patch('/operadores/{operador}/estado', [OperadorController::class, 'toggleActivo'])->name('operadores.estado');
        Route::get('/{id}', [TicketController::class, 'show'])->name('show');
        Route::match(['PUT', 'PATCH'], '/{id}/resolver', [TicketController::class, 'resolve'])->name('resolve');
        Route::patch('/{id}/reasignar', [TicketController::class, 'reasignar'])->name('reasignar');
        Route::get('/{id}/descargar', [TicketController::class, 'downloadPDF'])->name('download');
        Route::post('/{id}/confirmar', [TicketController::class, 'confirmar'])->name('confirmar');
        Route::post('/{id}/reabrir', [TicketController::class, 'reabrir'])->name('reabrir');
        Route::post('/{id}/recordatorio', [TicketController::class, 'recordatorio'])->name('recordatorio');
        Route::patch('/{id}/deshacer-resolucion', [TicketController::class, 'deshacerResolucion'])->name('deshacer-resolucion');
        Route::post('/{id}/notas', [TicketController::class, 'agregarNota'])->name('notas.store');
        Route::post('/{id}/material', [TicketController::class, 'registrarMaterial'])->name('material.store');
        Route::patch('/{ticket}/notas/{nota}/edit', [TicketController::class, 'editarNota'])->name('notas.edit');
        Route::delete('/{id}', [TicketController::class, 'destroy'])->name('destroy');
    });

    // ==========================================
    // ⚙️ ADMINISTRACIÓN
    // ==========================================
    Route::middleware([CheckAdmin::class])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', [AdminSettingsController::class, 'usersIndex'])->name('users.index');
        Route::get('/usuarios/crear', [AdminSettingsController::class, 'usersCreate'])->name('users.create');
        Route::post('/usuarios', [AdminSettingsController::class, 'usersStore'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [AdminSettingsController::class, 'usersEdit'])->name('users.edit');
        Route::put('/usuarios/{user}', [AdminSettingsController::class, 'usersUpdate'])->name('users.update');
        Route::delete('/usuarios/{user}', [AdminSettingsController::class, 'usersDestroy'])->name('users.destroy');
        Route::get('/usuarios/{user}/modulos', [AdminSettingsController::class, 'usersModulos'])->name('users.modulos');
        Route::post('/usuarios/{user}/modulos', [AdminSettingsController::class, 'usersModulosUpdate'])->name('users.modulos.update');

        Route::get('/areas', [AdminSettingsController::class, 'areasIndex'])->name('areas.index');
        Route::get('/areas/crear', [AdminSettingsController::class, 'areasCreate'])->name('areas.create');
        Route::post('/areas', [AdminSettingsController::class, 'areasStore'])->name('areas.store');
        Route::get('/areas/{id}/editar', [AdminSettingsController::class, 'areasEdit'])->name('areas.edit');
        Route::put('/areas/{id}', [AdminSettingsController::class, 'areasUpdate'])->name('areas.update');
        Route::delete('/areas/{id}', [AdminSettingsController::class, 'areasDestroy'])->name('areas.destroy');

        Route::get('/departamentos', [AdminSettingsController::class, 'departamentosIndex'])->name('departamentos.index');
        Route::get('/departamentos/crear', [AdminSettingsController::class, 'departamentosCreate'])->name('departamentos.create');
        Route::post('/departamentos', [AdminSettingsController::class, 'departamentosStore'])->name('departamentos.store');
        Route::get('/departamentos/{id}/editar', [AdminSettingsController::class, 'departamentosEdit'])->name('departamentos.edit');
        Route::put('/departamentos/{id}', [AdminSettingsController::class, 'departamentosUpdate'])->name('departamentos.update');
        Route::delete('/departamentos/{id}', [AdminSettingsController::class, 'departamentosDestroy'])->name('departamentos.destroy');

        Route::get('/propiedades', [AdminSettingsController::class, 'hotelesIndex'])->name('hoteles.index');
        Route::get('/propiedades/crear', [AdminSettingsController::class, 'hotelesCreate'])->name('hoteles.create');
        Route::post('/propiedades', [AdminSettingsController::class, 'hotelesStore'])->name('hoteles.store');
        Route::get('/propiedades/{id}/editar', [AdminSettingsController::class, 'hotelesEdit'])->name('hoteles.edit');
        Route::put('/propiedades/{id}', [AdminSettingsController::class, 'hotelesUpdate'])->name('hoteles.update');
        Route::delete('/propiedades/{id}', [AdminSettingsController::class, 'hotelesDestroy'])->name('hoteles.destroy');

        Route::get('/bitacora', [AdminSettingsController::class, 'auditoriaIndex'])->name('auditoria.index');

        // 📢 Anuncios de nuevas funciones/cambios (ver AnuncioController).
        Route::get('/anuncios', [AnuncioController::class, 'index'])->name('anuncios.index');
        Route::post('/anuncios', [AnuncioController::class, 'store'])->name('anuncios.store');

        // Selector de propiedad activa (solo Admin: es el único rol que
        // supervisa más de una propiedad). Ver AdminSettingsController::
        // propiedadActivaUpdate y Hotel::activoParaUsuario.
        Route::post('/propiedad-activa', [AdminSettingsController::class, 'propiedadActivaUpdate'])->name('propiedad-activa.update');

    });
});
