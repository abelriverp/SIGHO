<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/** Solo admin/gerente eliminan ubicaciones (Fase 1, nov 2026). */
class UbicacionPolicy
{
    use RestringeEliminacionASupervisor;
}
