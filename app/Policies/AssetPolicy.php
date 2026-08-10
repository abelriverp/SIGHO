<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: por ahora solo
 * cubre delete() (admin/gerente). Crear/editar activos de TI se queda
 * abierto a cualquiera con el módulo 'seguimiento_equipos_ti' activado.
 */
class AssetPolicy
{
    use RestringeEliminacionASupervisor;
}
