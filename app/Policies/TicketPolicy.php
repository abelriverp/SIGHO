<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Centraliza "quién puede ver qué ticket" y "quién puede pedir el corte
 * de reportes". Antes esta misma regla vivía copiada de forma idéntica en
 * TicketController::show(), downloadPDF() y el buscador de token dentro de
 * index() — justo el tipo de duplicación que ya causó una inconsistencia
 * real en el Dashboard (ver memoria del proyecto sobre visibilidad por rol).
 * Con el tiempo se van a agregar más módulos; que la regla viva en un solo
 * lugar evita que un módulo nuevo copie una versión desactualizada.
 *
 * resolve/reasignar/confirmar/reabrir/deshacerResolucion/agregarNota ya se
 * migraron aquí (2026-07-30), pero SOLO la parte de "quién tiene permiso
 * sobre este ticket" (identidad/rol/departamento). La máquina de estados
 * (status actual del ticket, ventana de 30 min para deshacer, límite de
 * reasignaciones) sigue viviendo en TicketController a propósito: esas no
 * son preguntas de "¿tienes permiso?" sino de "¿se puede hacer esto ahora
 * mismo?", y ya usan su propio flujo de vuelta con mensaje (back()->with
 * ('error', ...)) en vez de un 403 — mezclarlas aquí cambiaría ese
 * comportamiento. Cada método regresa un Response (allow/deny con mensaje)
 * en vez de bool para poder seguir mostrando el mensaje específico de cada
 * motivo de rechazo, tal como existía antes de la migración; el controller
 * lo lee vía Gate::inspect().
 */
class TicketPolicy
{
    /**
     * Puede ver el detalle o descargar el PDF de este ticket puntual.
     * admin/gerente: cualquier ticket de la propiedad.
     * técnico: tickets de su departamento o que él mismo reportó.
     * usuario: solo los tickets que él mismo reportó.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->puedeVerTodos()) {
            return true;
        }

        if ($user->isTecnico()) {
            return $ticket->departamento_destino === $user->departamento
                || $ticket->user_id === $user->id;
        }

        return $ticket->user_id === $user->id;
    }

    /**
     * Puede generar el corte de reportes/KPIs (pantalla y descarga PDF).
     * Un usuario normal solo reporta tickets; no le corresponde este
     * nivel de información agregada.
     */
    public function viewReportes(User $user): bool
    {
        return $user->puedeVerTodos() || $user->isTecnico();
    }

    /**
     * Solo un Administrador puede eliminar tickets. No depende del ticket
     * en particular (por eso no recibe un segundo parámetro Ticket): se
     * consulta antes de buscar el ticket en TicketController::destroy(),
     * igual que antes, para que un usuario sin permiso reciba 403 en vez
     * de un 404 si el id no existe.
     */
    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Puede marcar ESTE ticket como resuelto. El chequeo de rol general
     * (puedeResponderTickets) se queda inline en el controller, ANTES de
     * cargar el ticket, para no cambiar el orden actual (403 antes que
     * 404 cuando el usuario no tiene el rol correcto). Aquí solo viven las
     * reglas que sí dependen del ticket puntual.
     */
    public function resolve(User $user, Ticket $ticket): Response
    {
        if ($ticket->user_id === $user->id) {
            return Response::deny('No puedes resolver un ticket que tú mismo creaste.');
        }

        if ($user->isTecnico() && $ticket->departamento_destino !== $user->departamento) {
            return Response::deny('Este ticket no pertenece a tu departamento.');
        }

        return Response::allow();
    }

    /**
     * Puede reasignar ESTE ticket a otro departamento. Igual que resolve():
     * el chequeo de rol general se queda inline en el controller antes de
     * cargar el ticket; el límite de reasignaciones y el status válido
     * ('Abierto'/'Reabierto') son reglas de estado, no de permiso, y se
     * quedan en el controller con su propio back()->with('error', ...).
     */
    public function reasignar(User $user, Ticket $ticket): Response
    {
        if ($ticket->user_id === $user->id) {
            return Response::deny('No puedes reasignar un ticket que tú mismo reportaste.');
        }

        if ($user->isTecnico() && $ticket->departamento_destino !== $user->departamento) {
            return Response::deny('Este ticket no pertenece a tu departamento.');
        }

        return Response::allow();
    }

    /**
     * Puede confirmar la resolución de ESTE ticket: solo quien lo reportó.
     */
    public function confirmar(User $user, Ticket $ticket): Response
    {
        if ($ticket->user_id !== $user->id) {
            return Response::deny('Solo el usuario que reportó puede confirmar este ticket.');
        }

        return Response::allow();
    }

    /**
     * Puede reabrir ESTE ticket: solo quien lo reportó.
     */
    public function reabrir(User $user, Ticket $ticket): Response
    {
        if ($ticket->user_id !== $user->id) {
            return Response::deny('Solo el usuario que reportó puede reabrir este ticket.');
        }

        return Response::allow();
    }

    /**
     * Puede deshacer la resolución de ESTE ticket: quien lo resolvió, o un
     * Administrador. El chequeo de rol general (puedeResponderTickets) se
     * queda inline en el controller antes de cargar el ticket; la ventana
     * de 30 minutos es una regla de tiempo, no de permiso, y se queda en
     * el controller.
     */
    public function deshacerResolucion(User $user, Ticket $ticket): Response
    {
        $esQuienResolvio = $ticket->resolved_by === $user->id;

        if (!$esQuienResolvio && !$user->isAdmin()) {
            return Response::deny('Solo quien resolvió este ticket o un Administrador puede deshacer la resolución.');
        }

        return Response::allow();
    }

    /**
     * Puede enviar un recordatorio manual de ESTE ticket: solo quien lo
     * reportó. El umbral de 2 días desde la creación y el cooldown de 24h
     * entre recordatorios son reglas de tiempo/estado, no de permiso — se
     * quedan en el controller con su propio back()->with('error', ...),
     * igual que el resto de la máquina de estados.
     */
    public function recordatorio(User $user, Ticket $ticket): Response
    {
        if ($ticket->user_id !== $user->id) {
            return Response::deny('Solo el usuario que reportó puede enviar un recordatorio de este ticket.');
        }

        return Response::allow();
    }

    /**
     * Puede agregar una nota a la bitácora de un ticket: cualquiera excepto
     * un usuario regular (que solo reporta tickets, no da seguimiento).
     * Recibe $ticket únicamente para que Gate::inspect() sepa resolver esta
     * Policy (no depende de datos del ticket en sí, es un chequeo de rol).
     */
    public function agregarNota(User $user, Ticket $ticket): Response
    {
        if ($user->isUsuario()) {
            return Response::deny('No tienes permisos para agregar notas.');
        }

        return Response::allow();
    }
}
