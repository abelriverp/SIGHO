<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Regla compartida por las Policies de Assets/Inventario/Red/Condominios
 * (2026-07-30): antes de esto, ninguno de esos módulos tenía UNA SOLA
 * verificación de rol — solo el middleware 'modulo:xxx' que gatea el
 * módulo completo. Cualquier usuario con el módulo activado podía crear,
 * editar Y ELIMINAR sin importar su rol (admin/gerente/tecnico/usuario).
 *
 * Alcance decidido con el usuario: por ahora solo se restringe ELIMINAR a
 * admin/gerente (mismo criterio que User::puedeVerTodos(), ya usado en todo
 * el proyecto). Crear/editar se queda abierto a quien tenga el módulo
 * activado, igual que hoy — no se inventaron reglas de negocio nuevas para
 * esas acciones.
 *
 * Un solo trait en vez de repetir el mismo método en 7 Policies: si el
 * negocio decide más adelante un criterio distinto (ej. que gerente ya no
 * pueda eliminar, o que se necesite un permiso más fino), se cambia en un
 * solo lugar.
 */
trait RestringeEliminacionASupervisor
{
    public function delete(User $user): bool
    {
        return $user->puedeVerTodos();
    }
}
