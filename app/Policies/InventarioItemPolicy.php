<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: por ahora solo
 * cubre delete() (admin/gerente). Crear/editar/registrar movimientos se
 * queda abierto a cualquiera con el módulo 'inventario_suministros' activado.
 */
class InventarioItemPolicy
{
    use RestringeEliminacionASupervisor;
}
