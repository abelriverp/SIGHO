<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Services\DashboardMetricsService;

class DashboardController extends Controller
{
    use ResuelveHotelActivo;

    public function index(DashboardMetricsService $dashboardMetrics)
    {
        // 1. Obtener el usuario autenticado
        $user = Auth::user();

        // Identificar la propiedad/hotel del usuario (Admin: propiedad de la instalación,
        // ver ResuelveHotelActivo). Estas consultas usan DB::table() en vez de Eloquent,
        // así que no las cubre el HotelScope automático: hay que filtrar a mano.
        $hotelActivo = $this->hotelActivo();

        // 2. Determinar si el usuario tiene rol de supervisor (Ver todo) o es usuario final (Ver solo lo suyo)
        // Adaptado según los métodos comunes de tu sistema (isAdmin, isTecnico, isGerente)
        $verTodo = false;
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            $verTodo = true;
        } elseif (method_exists($user, 'isTecnico') && $user->isTecnico()) {
            $verTodo = true;
        } elseif (method_exists($user, 'isGerente') && $user->isGerente()) {
            $verTodo = true;
        }

        // 3. Consultas adaptativas según el rol del usuario
        if ($verTodo) {
            // Un administrador o técnico ve las métricas de toda la propiedad
            $ticketsAbiertos = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('status', 'Abierto')
                ->count();

            $ticketsTotales = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->count();

            $suministrosBajos = DB::table('inventario_items')
                ->where('hotel_code', $hotelActivo)
                ->where('stock_minimo', '>', 0)
                ->whereColumn('cantidad', '<=', 'stock_minimo')
                ->count();

            $totalEquipos = DB::table('assets')
                ->where('hotel_code', $hotelActivo)
                ->count();

            $ultimosTickets = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();
        } else {
            // Un usuario regular SOLO ve la información que él mismo generó (vinculado por user_id)
            $ticketsAbiertos = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('user_id', $user->id)
                ->where('status', 'Abierto')
                ->count();

            $ticketsTotales = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('user_id', $user->id)
                ->count();

            // Suministros cargados o bajo la responsabilidad de este usuario
            $suministrosBajos = DB::table('inventario_items')
                ->where('hotel_code', $hotelActivo)
                ->where('user_id', $user->id)
                ->where('stock_minimo', '>', 0)
                ->whereColumn('cantidad', '<=', 'stock_minimo')
                ->count();

            // Equipos asignados a su nombre/área (Se asume filtro por área o encargado si aplica,
            // de momento mapeamos los de su misma área para que vea el hardware de su departamento)
            $totalEquipos = DB::table('assets')
                ->where('hotel_code', $hotelActivo)
                ->where('area', $user->area ?? 'Ninguna')
                ->count();

            // Historial de los últimos 5 tickets únicamente de este usuario
            $ultimosTickets = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();
        }

        // 4. Datos para las gráficas del panel. Usa el mismo criterio de
        // visibilidad por rol que AlertasService/TicketController (admin y
        // gerente ven todo; técnico solo su departamento; usuario solo lo
        // suyo), filtrado a la misma $hotelActivo que las tarjetas de arriba.
        $graficas = $dashboardMetrics->metricas($user, $hotelActivo);

        // 5. Retornar la vista con los datos ya filtrados de forma personalizada
        return view('dashboard', compact(
            'hotelActivo',
            'ticketsAbiertos',
            'ticketsTotales',
            'suministrosBajos',
            'totalEquipos',
            'ultimosTickets',
            'graficas'
        ));
    }
}
