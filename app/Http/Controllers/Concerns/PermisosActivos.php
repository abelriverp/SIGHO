<?php

namespace App\Http\Controllers\Concerns;

/**
 * Permisos de la Fase 3 dentro del modulo Activos y Ubicaciones:
 * todos con el modulo consultan; tecnicos, gerentes y admins capturan;
 * solo gerentes y admins eliminan o aprueban bajas.
 */
trait PermisosActivos
{
    protected function exigeEditorActivos(): void
    {
        $u = auth()->user();
        if (! ($u->puedeResponderTickets() || $u->puedeVerTodos())) {
            abort(403, 'Solo técnicos, gerentes y administradores pueden capturar en este módulo.');
        }
    }

    protected function exigeSupervisor(string $mensaje = 'Solo un Administrador o Gerente puede hacer esto.'): void
    {
        if (! auth()->user()->puedeVerTodos()) {
            abort(403, $mensaje);
        }
    }

    /** IDs de activos de esta propiedad (exists: no aplica HotelScope). */
    protected function idsActivosValidos(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return $ids ? \App\Models\Asset::whereIn('id', $ids)->pluck('id')->all() : [];
    }

    protected function proveedorValido(?int $id): ?int
    {
        return $id && \App\Models\Proveedor::whereKey($id)->exists() ? $id : null;
    }
}
