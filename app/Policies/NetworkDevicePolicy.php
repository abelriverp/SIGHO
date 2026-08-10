<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: por ahora solo
 * cubre delete() (admin/gerente) para dispositivos de red (borra también
 * sus puertos, por cascade de FK).
 */
class NetworkDevicePolicy
{
    use RestringeEliminacionASupervisor;
}
