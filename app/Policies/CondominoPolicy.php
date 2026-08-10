<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: por ahora solo
 * cubre delete() (admin/gerente) para propietarios/condóminos.
 */
class CondominoPolicy
{
    use RestringeEliminacionASupervisor;
}
