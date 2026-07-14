<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * Centraliza "quién puede ver qué ticket" y "quién puede pedir el corte
 * de reportes". Antes esta misma regla vivía copiada de forma idéntica en
 * TicketController::show(), downloadPDF() y el buscador de token dentro de
 * index() — justo el tipo de duplicación que ya causó una inconsistencia
 * real en el Dashboard (ver memoria del proyecto sobre visibilidad por rol).
 * Con el tiempo se van a agregar más módulos; que la regla viva en un solo
 * lugar evita que un módulo nuevo copie una versión desactualizada.
 *
 * Se deja fuera de esta Policy, a propósito, la lógica de resolve/reasignar/
 * confirmar/reabrir/deshacerResolucion/agregarNota: esas mezclan el permiso
 * con la máquina de estados del ticket (status actual, ventanas de tiempo,
 * límite de reasignaciones) y cada una tiene un mensaje de error propio
 * pensado para ese caso puntual. Moverlas sin poder correr los tests en
 * este entorno es un riesgo que no vale la pena tomar en este pase; puede
 * hacerse en un siguiente paso, una por una, con su propia verificación.
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
}
