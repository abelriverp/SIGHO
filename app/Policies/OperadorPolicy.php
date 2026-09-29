<?php

namespace App\Policies;

use App\Models\Operador;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * "Operadores" es un catálogo COMPARTIDO por departamento, no una lista
 * personal del técnico que lo creó: cualquier técnico del mismo
 * departamento (ej. Ana y Beto, ambos en el mismo departamento)
 * puede ver, usar, renombrar y desactivar cualquier operador de ese
 * departamento — no solo los que él mismo dio de alta. Admin puede
 * administrar cualquiera (soporte/corrección), igual que en el resto del
 * proyecto. Gerente puede CONSULTAR (para reportes) pero no crear/editar
 * — no ejecuta trabajo de campo.
 */
class OperadorPolicy
{
    // Puede entrar a la pantalla de operadores (la de su departamento).
    public function viewAny(User $user): bool
    {
        return $user->isTecnico() || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isTecnico() || $user->isAdmin();
    }

    public function update(User $user, Operador $operador): Response
    {
        return $this->delMismoDepartamentoOAdmin($user, $operador);
    }

    // "Eliminar" en la UI es desactivar (ver OperadorController); misma
    // regla de permiso que editar.
    public function delete(User $user, Operador $operador): Response
    {
        return $this->delMismoDepartamentoOAdmin($user, $operador);
    }

    private function delMismoDepartamentoOAdmin(User $user, Operador $operador): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isTecnico() && $operador->departamento !== null && $operador->departamento === $user->departamento) {
            return Response::allow();
        }

        return Response::deny('Solo un técnico del mismo departamento o un Administrador puede modificar este operador.');
    }
}
