<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController, AssetController, RevisionController,
    InventoryController, NetworkController, PeripheralController,
    TicketController, AdminSettingsController, HabitacionActivoController,
    ActivoPlantillaController, ActivoItemController,
    ProfileController, DashboardController,
    UnidadController, CondominoController, EstanciaController
};
use App\Http\Middleware\CheckAdmin;

// ==========================================
// 🌐 RUTAS PÚBLICAS (ACCESO LIBRE)
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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

    Route::middleware(['modulo:condominios_checkin'])->prefix('condominios')->name('condominios.')->group(function () {
        // Reportes y estancias (check-in/check-out) van primero para no chocar con los resource
        Route::get('/reportes', [EstanciaController::class, 'reportes'])->name('reportes');

        Route::resource('estancias', EstanciaController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('estancias/{estancia}/checkin', [EstanciaController::class, 'checkin'])->name('estancias.checkin');
        Route::post('estancias/{estancia}/checkout', [EstanciaController::class, 'checkout'])->name('estancias.checkout');
        Route::post('estancias/{estancia}/cancelar', [EstanciaController::class, 'cancelar'])->name('estancias.cancelar');

        Route::resource('unidades', UnidadController::class)->except(['show']);
        Route::resource('condominos', CondominoController::class);
    });

    Route::middleware(['modulo:red_infraestructura'])->group(function () {
        Route::resource('network', NetworkController::class);
        Route::post('/network/port/{id}', [NetworkController::class, 'updatePort'])->name('network.port.update');
    });

    Route::middleware(['modulo:gestion_tickets'])->prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/crear', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        // Reportes va antes de /{id} para que "reportes" no se interprete como un id de ticket.
        Route::get('/reportes', [TicketController::class, 'reportes'])->name('reportes');
        Route::get('/reportes/pdf', [TicketController::class, 'reportesPdf'])->name('reportes.pdf');
        Route::get('/{id}', [TicketController::class, 'show'])->name('show');
        Route::match(['PUT', 'PATCH'], '/{id}/resolver', [TicketController::class, 'resolve'])->name('resolve');
        Route::patch('/{id}/reasignar', [TicketController::class, 'reasignar'])->name('reasignar');
        Route::get('/{id}/descargar', [TicketController::class, 'downloadPDF'])->name('download');
        Route::post('/{id}/confirmar', [TicketController::class, 'confirmar'])->name('confirmar');
        Route::post('/{id}/reabrir', [TicketController::class, 'reabrir'])->name('reabrir');
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

        // Gestión de áreas y plantillas para el módulo de activos
        Route::resource('activo-plantillas', ActivoPlantillaController::class)
            ->names('activo-plantillas');
    });
});
