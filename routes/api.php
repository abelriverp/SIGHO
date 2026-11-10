<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

/*
| API REST v1 de SIGHO (Fase 5). Autenticación: Authorization: Bearer <token>
| (tokens en Admin → Tokens de API). Límite: 60 peticiones por minuto.
*/
Route::prefix('v1')->middleware(['api.token', 'throttle:60,1'])->group(function () {
    Route::get('/activos', [ApiController::class, 'activos']);
    Route::get('/activos/{nomenclatura}', [ApiController::class, 'activo']);
    Route::get('/tickets', [ApiController::class, 'tickets']);
    Route::post('/tickets', [ApiController::class, 'crearTicket'])->middleware('api.token:escritura');
    Route::get('/monitoreo', [ApiController::class, 'monitoreo']);
    Route::get('/kpis', [ApiController::class, 'kpis']);
});
