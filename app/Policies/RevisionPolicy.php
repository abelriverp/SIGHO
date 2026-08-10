<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: por ahora solo
 * cubre delete() (admin/gerente) para entradas de la bitácora de revisiones
 * de un activo.
 */
class RevisionPolicy
{
    use RestringeEliminacionASupervisor;
}
