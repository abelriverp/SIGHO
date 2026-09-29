<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: solo
 * admin/gerente pueden borrar un análisis de correos ya guardado (mismo
 * criterio que Assets/Inventario/Red/Condominios). Crear y consultar se
 * queda abierto a cualquiera con el módulo 'herramientas_recepcion'
 * activado — eso ya lo garantiza el middleware 'modulo:...' en las rutas.
 */
class EmailValidacionPolicy
{
    use RestringeEliminacionASupervisor;
}
