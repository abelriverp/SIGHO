<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: solo
 * admin/gerente pueden eliminar periféricos (Fase 0, nov 2026).
 */
class PeripheralPolicy
{
    use RestringeEliminacionASupervisor;
}
