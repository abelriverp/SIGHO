<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController, AssetController, RevisionController,
    InventoryController, NetworkController, PeripheralController,
    TicketController, AdminSettingsController, HabitacionActivoController,
    ActivoPlantillaController, ActivoItemController,
    ProfileController, DashboardController,
    TicketCategoriaController, SlaPoliticaController,
    GuestPortalController, PuntoQrController, AnuncioController,
    OperadorController, RecepcionController, EmailValidacionController,
    ExtensionDeteccionController, PerfilCorreoValidacionController
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
Route::get('/qr/{token}', [GuestPortalController::class, 'show'])->name('guest.show');
Route::post('/qr/{token}', [GuestPortalController::class, 'store'])->middleware('throttle:guest-request')->name('guest.store');
Route::get('/qr/{token}/gracias', [GuestPortalController::class, 'gracias'])->name('guest.gracias');

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

    // Módulo legacy (habitaciones fijas) — se mantiene para no romper datos existentes
    Route::middleware(['modulo:inventario_habitaciones'])->prefix('inventario')->name('habitaciones.')->group(function () {
        Route::get('/habitaciones-legacy', [HabitacionActivoController::class, 'index'])->name('index');
        Route::post('/habitaciones', [HabitacionActivoController::class, 'store'])->name('store');
        Route::put('/habitaciones/{id}', [HabitacionActivoController::class, 'update'])->name('update');
    });

    // Módulo nuevo: Activos multi-área con plantillas dinámicas
    Route::middleware(['modulo:inventario_habitaciones'])->prefix('inventario')->name('activos.')->group(function () {
        Route::get('/', [ActivoItemController::class, 'index'])->name('index');
        Route::post('/activos', [ActivoItemController::class, 'store'])->name('store');
        Route::put('/activos/{id}', [ActivoItemController::class, 'update'])->name('update');
        Route::delete('/activos/{id}', [ActivoItemController::class, 'destroy'])->name('destroy');
        // Endpoint AJAX para obtener campos de una plantilla
        Route::get('/activos/plantilla/{id}', [ActivoItemController::class, 'plantillaJson'])->name('plantilla.json');
    });

    Route::middleware(['modulo:seguimiento_equipos_ti'])->group(function () {
        Route::get('/seguimiento', [AssetController::class, 'index'])->name('assets.index');
        Route::get('/seguimiento/crear', [AssetController::class, 'create'])->name('assets.create');
        Route::get('/assets/perifericos', [AssetController::class, 'perifericos'])->name('assets.perifericos');
        Route::get('/assets/exportar', [AssetController::class, 'exportExcel'])->name('assets.export');
        Route::post('/assets/importar-excel', [AssetController::class, 'importExcel'])->name('assets.import');
        Route::patch('/assets/{id}/division', [AssetController::class, 'cambiarDivision'])->name('assets.division');
        Route::post('/assets/mover-lote', [AssetController::class, 'moverLote'])->name('assets.moverLote');
        Route::resource('assets', AssetController::class)->except(['index', 'create']);

        Route::get('assets/{asset}/revisions/create', [RevisionController::class, 'create'])->name('revisions.create');
        Route::post('assets/{asset}/revisions', [RevisionController::class, 'store'])->name('revisions.store');
        Route::resource('revisions', RevisionController::class)->only(['edit', 'update', 'destroy']);
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
    Route::middleware(['supervisor'])->prefix('puntos-qr')->name('puntos-qr.')->group(function () {
        Route::get('/', [PuntoQrController::class, 'index'])->name('index');
        Route::get('/crear', [PuntoQrController::class, 'create'])->name('create');
        Route::post('/', [PuntoQrController::class, 'store'])->name('store');
        Route::get('/{puntoQr}/imprimir', [PuntoQrController::class, 'imprimir'])->name('imprimir');
        Route::patch('/{puntoQr}/estado', [PuntoQrController::class, 'toggleActivo'])->name('estado');
        Route::patch('/{puntoQr}/regenerar-token', [PuntoQrController::class, 'regenerarToken'])->name('regenerar-token');
        Route::delete('/{puntoQr}', [PuntoQrController::class, 'destroy'])->name('destroy');
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

        // Gestión de áreas y plantillas para el módulo de activos
        Route::resource('activo-plantillas', ActivoPlantillaController::class)
            ->names('activo-plantillas');
    });
});
