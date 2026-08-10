<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: por ahora solo
 * cubre delete() (admin/gerente) para unidades de condominio.
 */
class UnidadPolicy
{
    use RestringeEliminacionASupervisor;
}
