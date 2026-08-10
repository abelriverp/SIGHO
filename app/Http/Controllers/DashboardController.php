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

        // 2. Nivel de visibilidad del usuario, con el MISMO criterio de 3
        // niveles que ya usan AlertasService/DashboardMetricsService/
        // TicketController para tickets:
        //   - admin/gerente ($verTodo): toda la propiedad.
        //   - técnico ($esTecnico): solo su propio departamento_destino.
        //   - usuario regular: solo lo que él mismo reportó.
        // Antes, las tarjetas KPI de aquí arriba agrupaban a técnico junto
        // con admin/gerente como "ve todo", mientras que las gráficas de
        // abajo (Analítica) y la campana de alertas ya restringían al
        // técnico a su departamento — un técnico veía cifras de tickets
        // distintas entre la parte de arriba y la de abajo del mismo
        // Dashboard. Corregido aquí para que todo el panel cuente la misma
        // historia.
        $verTodo   = $user->puedeVerTodos();
        $esTecnico = $user->isTecnico();

        // 3. Consultas de TICKETS, adaptativas según el nivel de visibilidad.
        if ($verTodo) {
            $ticketsAbiertos = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('status', 'Abierto')
                ->count();

            $ticketsTotales = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->count();

            $ultimosTickets = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();
        } elseif ($esTecnico) {
            // Técnico: solo tickets de su propio departamento_destino,
            // igual que AlertasService::scopeTicketsVisibles,
            // DashboardMetricsService::ticketsVisibles y TicketController::index.
            $ticketsAbiertos = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('departamento_destino', $user->departamento)
                ->where('status', 'Abierto')
                ->count();

            $ticketsTotales = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('departamento_destino', $user->departamento)
                ->count();

            $ultimosTickets = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('departamento_destino', $user->departamento)
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

            $ultimosTickets = DB::table('tickets')
                ->where('hotel_code', $hotelActivo)
                ->where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();
        }

        // 4. Suministros y equipos: a diferencia de tickets, ni InventarioItem
        // ni Asset tienen una dimensión de "departamento_destino" -- se
        // organizan por área/propiedad, no por el departamento que atiende
        // al técnico. Por eso el técnico conserva aquí el mismo panorama de
        // propiedad completa que admin/gerente (no es la inconsistencia que
        // se corrigió arriba).
        if ($verTodo || $esTecnico) {
            $suministrosBajos = DB::table('inventario_items')
                ->where('hotel_code', $hotelActivo)
                ->where('stock_minimo', '>', 0)
                ->whereColumn('cantidad', '<=', 'stock_minimo')
                ->count();

            $totalEquipos = DB::table('assets')
                ->where('hotel_code', $hotelActivo)
                ->count();
        } else {
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
        }

        // 5. Datos para las gráficas del panel. Usa el mismo criterio de
        // visibilidad por rol que AlertasService/TicketController (admin y
        // gerente ven todo; técnico solo su departamento; usuario solo lo
        // suyo), filtrado a la misma $hotelActivo que las tarjetas de arriba.
        $graficas = $dashboardMetrics->metricas($user, $hotelActivo);

        // 6. Retornar la vista con los datos ya filtrados de forma personalizada
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
