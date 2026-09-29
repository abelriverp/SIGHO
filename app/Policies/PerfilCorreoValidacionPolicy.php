<?php

namespace App\Policies;

use App\Policies\Concerns\RestringeEliminacionASupervisor;

/**
 * Ver App\Policies\Concerns\RestringeEliminacionASupervisor: solo
 * admin/gerente pueden borrar un análisis de perfiles/correos de llegada
 * ya guardado — mismo criterio que EmailValidacionPolicy/ExtensionDeteccionPolicy.
 * Crear y consultar se queda abierto a cualquiera con el módulo
 * 'herramientas_recepcion' activado (lo garantiza el middleware 'modulo:...'
 * en las rutas).
 */
class PerfilCorreoValidacionPolicy
{
    use RestringeEliminacionASupervisor;
}
