<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController, AssetController, RevisionController, 
    InventoryController, NetworkController, PeripheralController, 
    TicketController, AdminSettingsController, HabitacionActivoController,
    ProfileController
};
use App\Http\Middleware\CheckAdmin;

// ==========================================
// 🌐 RUTAS PÚBLICAS (ACCESO LIBRE)
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ==========================================
// 🔒 RUTAS SISTEMA PROTEGIDAS (REQUIEREN LOGIN)
// ==========================================
Route::middleware('auth')->group(function () {
    
    // Rutas de cambio de contraseña
    Route::get('/cambiar-contrasena', [ProfileController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/cambiar-contrasena', [ProfileController::class, 'updatePassword'])->name('password.change.update');

    // Redirección inteligente en la raíz
    Route::get('/', function () {
        $user = auth()->user();

        if ($user->must_change_password && !$user->isAdmin()) {
            return redirect()->route('password.change');
        }

        if ($user->isAdmin()) {
            return redirect()->route('assets.index');
        } elseif ($user->isGerente() || $user->isTecnico()) {
            return redirect()->route('tickets.index');
        } else {
            return redirect()->route('tickets.create');
        }
    });

    // Módulo de Inventario de Habitaciones
    Route::prefix('inventario')->group(function () {
        Route::get('/', [HabitacionActivoController::class, 'index'])->name('habitaciones.index');
        Route::post('/habitaciones', [HabitacionActivoController::class, 'store'])->name('habitaciones.store');
        Route::put('/habitaciones/{id}', [HabitacionActivoController::class, 'update'])->name('habitaciones.update');
    });

    // Módulo de Activos (Assets)
    Route::get('/seguimiento', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/seguimiento/crear', [AssetController::class, 'create'])->name('assets.create');
    Route::get('/assets/perifericos', [AssetController::class, 'perifericos'])->name('assets.perifericos');
    Route::get('/assets/exportar', [AssetController::class, 'exportExcel'])->name('assets.export');
    Route::resource('assets', AssetController::class)->except(['index', 'create']);

    // Revisiones de Activos
    Route::get('assets/{asset}/revisions/create', [RevisionController::class, 'create'])->name('revisions.create');
    Route::post('assets/{asset}/revisions', [RevisionController::class, 'store'])->name('revisions.store');
    Route::resource('revisions', RevisionController::class)->only(['edit', 'update', 'destroy']);

    // Redes e Inventario General
    Route::resource('inventory', InventoryController::class);
    Route::resource('network', NetworkController::class);
    Route::post('/network/port/{id}', [NetworkController::class, 'updatePort'])->name('network.port.update');

    // Módulo de Tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/crear', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/{id}', [TicketController::class, 'show'])->name('show');
        Route::match(['PUT', 'PATCH'], '/{id}/resolver', [TicketController::class, 'resolve'])->name('resolve');
        Route::get('/{id}/descargar', [TicketController::class, 'downloadPDF'])->name('download');
        Route::post('/{id}/confirmar', [TicketController::class, 'confirmar'])->name('confirmar');
        Route::post('/{id}/reabrir', [TicketController::class, 'reabrir'])->name('reabrir');
        Route::post('/{id}/notas', [TicketController::class, 'agregarNota'])->name('notas.store');
        // v1.1 — Editar nota (ventana 15 min, solo autor)
        Route::patch('/{ticket}/notas/{nota}/edit', [TicketController::class, 'editarNota'])->name('notas.edit');
    });

    // Módulo de Administración (Solo administradores)
    Route::middleware([CheckAdmin::class])->prefix('admin')->name('admin.')->group(function () {
        
        // Rutas de Usuarios
        Route::get('/usuarios', [AdminSettingsController::class, 'usersIndex'])->name('users.index');
        Route::get('/usuarios/crear', [AdminSettingsController::class, 'usersCreate'])->name('users.create');
        Route::post('/usuarios', [AdminSettingsController::class, 'usersStore'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [AdminSettingsController::class, 'usersEdit'])->name('users.edit');
        Route::put('/usuarios/{user}', [AdminSettingsController::class, 'usersUpdate'])->name('users.update');
        Route::delete('/usuarios/{user}', [AdminSettingsController::class, 'usersDestroy'])->name('users.destroy');

        // Rutas de Áreas
        Route::get('/areas', [AdminSettingsController::class, 'areasIndex'])->name('areas.index');
        Route::get('/areas/crear', [AdminSettingsController::class, 'areasCreate'])->name('areas.create');
        Route::post('/areas', [AdminSettingsController::class, 'areasStore'])->name('areas.store');
        Route::get('/areas/{id}/editar', [AdminSettingsController::class, 'areasEdit'])->name('areas.edit');
        Route::put('/areas/{id}', [AdminSettingsController::class, 'areasUpdate'])->name('areas.update');
        Route::delete('/areas/{id}', [AdminSettingsController::class, 'areasDestroy'])->name('areas.destroy');
    });
});