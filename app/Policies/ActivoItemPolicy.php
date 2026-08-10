<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: por ahora solo
 * cubre delete() (admin/gerente) para activos multi-área con plantillas
 * dinámicas (módulo 'inventario_habitaciones').
 */
class ActivoItemPolicy
{
    use RestringeEliminacionASupervisor;
}
