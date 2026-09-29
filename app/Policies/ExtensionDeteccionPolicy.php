<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: mismo criterio
 * que EmailValidacionPolicy — solo admin/gerente pueden borrar una
 * comparación ya guardada.
 */
class ExtensionDeteccionPolicy
{
    use RestringeEliminacionASupervisor;
}
