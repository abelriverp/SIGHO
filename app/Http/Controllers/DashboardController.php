<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Obtener el usuario autenticado
        $user = Auth::user();
        
        // Identificar la propiedad/hotel o pasar un texto representativo
        $hotelActivo = session('active_hotel_code', env('HOTEL_CODE', 'HEMZT'));

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
                ->where('status', 'Abierto')
                ->count();

            $ticketsTotales = DB::table('tickets')
                ->count();

            $suministrosBajos = DB::table('inventario_items')
                ->whereRaw('cantidad_actual <= stock_minimo')
                ->count();

            $totalEquipos = DB::table('assets')
                ->count();

            $ultimosTickets = DB::table('tickets')
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();
        } else {
            // Un usuario regular SOLO ve la información que él mismo generó (vinculado por user_id)
            $ticketsAbiertos = DB::table('tickets')
                ->where('user_id', $user->id)
                ->where('status', 'Abierto')
                ->count();

            $ticketsTotales = DB::table('tickets')
                ->where('user_id', $user->id)
                ->count();

            // Suministros cargados o bajo la responsabilidad de este usuario
            $suministrosBajos = DB::table('inventario_items')
                ->where('user_id', $user->id)
                ->whereRaw('cantidad_actual <= stock_minimo')
                ->count();

            // Equipos asignados a su nombre/área (Se asume filtro por área o encargado si aplica, 
            // de momento mapeamos los de su misma área para que vea el hardware de su departamento)
            $totalEquipos = DB::table('assets')
                ->where('area', $user->area ?? 'Ninguna') 
                ->count();

            // Historial de los últimos 5 tickets únicamente de este usuario
            $ultimosTickets = DB::table('tickets')
                ->where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();
        }

        // 4. Retornar la vista con los datos ya filtrados de forma personalizada
        return view('dashboard', compact(
            'hotelActivo',
            'ticketsAbiertos',
            'ticketsTotales',
            'suministrosBajos',
            'totalEquipos',
            'ultimosTickets'
        ));
    }
}