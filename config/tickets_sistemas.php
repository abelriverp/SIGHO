<?php

// NOTA (2026-07-16): esto YA NO es config viva. El catálogo de tipo de
// solicitud/sistema para tickets ahora vive en base de datos (ver modelos
// App\Models\TicketCategoria/TicketSubcategoria/TicketCampo), administrable
// por admin/gerente desde Tickets -> Categorías de solicitud, por propiedad
// y para cualquier departamento — no solo Sistemas.
//
// Este archivo se conserva únicamente como:
//   1. El dato semilla de la migración
//      database/migrations/2026_07_16_130003_seed_ticket_categorias_sistemas.php,
//      que lo usó para poblar ticket_categorias/subcategorias/campos para
//      cada propiedad ya existente en ese momento.
//   2. Referencia/plantilla de partida si se necesita recrear a mano este
//      mismo catálogo para una propiedad nueva.
//
// El formulario de creación de tickets, el controlador y el detalle del
// ticket YA NO leen este archivo — consultan la base de datos. Editar este
// array no tiene ningún efecto en producción.
//
// Se navegaba Tipo de solicitud -> Sistema porque así es como dos o más
// sistemas que necesitan exactamente los mismos datos (ej. GestHotel y
// WinTB en "Alta de usuario") pueden compartir una sola plantilla de
// campos en vez de repetirla — ver $plantillas abajo. Un tipo sin
// 'sistemas' (ej. "Otra falla / Otro") no pide elegir sistema: sus
// 'campos' se usan directo.

$plantillas = [
    // Compartida por sistemas que piden exactamente lo mismo para dar de
    // alta a un colaborador (hoy: GestHotel y WinTB).
    'alta_simple' => [
        ['name' => 'nombre_colaborador', 'label' => 'Nombre completo del colaborador', 'type' => 'text', 'required' => true],
        ['name' => 'no_colaborador',     'label' => 'No. Colaborador',                  'type' => 'text', 'required' => true],
        ['name' => 'puesto',             'label' => 'Puesto',                           'type' => 'text', 'required' => true],
    ],
];

$tipos = [

    'cambio_contrasena' => [
        'label'    => 'Cambio de contraseña',
        'sistemas' => [
            'avance' => [
                'label'  => 'Avance',
                'campos' => [
                    ['name' => 'id_usuario',         'label' => 'ID de usuario',                     'type' => 'text', 'required' => true],
                    ['name' => 'nombre_colaborador', 'label' => 'Nombre completo del colaborador',   'type' => 'text', 'required' => true],
                ],
            ],
        ],
    ],

    'alta' => [
        'label'    => 'Alta de usuario',
        'sistemas' => [
            'facto' => [
                'label'  => 'Facto',
                'nota'   => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'campos' => [
                    ['name' => 'nombre_colaborador', 'label' => 'Nombre completo del usuario a dar de alta', 'type' => 'text', 'required' => true],
                    ['name' => 'rfc',                'label' => 'RFC',                                       'type' => 'text', 'required' => true],
                    ['name' => 'razon_social',       'label' => 'Nombre de la razón social',                 'type' => 'text', 'required' => true],
                    ['name' => 'centros_consumo',    'label' => 'Centros de consumo',                        'type' => 'text', 'required' => true],
                    ['name' => 'rol',                'label' => 'Rol',                                       'type' => 'text', 'required' => true],
                ],
            ],
            'simphony' => [
                'label'  => 'Simphony',
                'nota'   => 'Un ticket por usuario. Si necesitas copiar los permisos de un usuario que ya existe, indícalo en "Perfil/ID a replicar".',
                'campos' => [
                    ['name' => 'nombre_colaborador', 'label' => 'Nombre completo del colaborador a dar de alta', 'type' => 'text', 'required' => true],
                    ['name' => 'numero_empleado',    'label' => 'Número de empleado del colaborador',            'type' => 'text', 'required' => true],
                    ['name' => 'perfil_requerido',   'label' => 'Perfil de Simphony requerido',                  'type' => 'text', 'required' => true],
                    ['name' => 'perfil_replicar',    'label' => 'Perfil/ID de Simphony a replicar (opcional)',   'type' => 'text', 'required' => false],
                ],
            ],
            'opera' => [
                'label'  => 'Opera',
                'nota'   => 'Un ticket por usuario. Si requieres copiar permisos de un usuario existente, indica su ID en "Perfil/ID a replicar".',
                'campos' => [
                    ['name' => 'nombre_colaborador', 'label' => 'Nombre del colaborador',                       'type' => 'text', 'required' => true],
                    ['name' => 'puesto',             'label' => 'Puesto',                                       'type' => 'text', 'required' => true],
                    ['name' => 'numero_colaborador', 'label' => 'Número de colaborador',                        'type' => 'text', 'required' => true],
                    ['name' => 'perfil',             'label' => 'Perfil',                                       'type' => 'text', 'required' => true],
                    ['name' => 'perfil_replicar',    'label' => 'Perfil/ID de usuario a replicar (opcional)',   'type' => 'text', 'required' => false],
                ],
            ],
            'gesthotel' => [
                'label'     => 'GestHotel',
                'nota'      => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'plantilla' => 'alta_simple',
            ],
            'wintb' => [
                'label'     => 'WinTB',
                'nota'      => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'plantilla' => 'alta_simple',
            ],
        ],
    ],

    'alta_reporteador' => [
        'label'    => 'Alta de reporteador',
        'sistemas' => [
            'simphony' => [
                'label'  => 'Simphony',
                'nota'   => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'campos' => [
                    ['name' => 'nombre_colaborador', 'label' => 'Nombre completo del colaborador a dar de alta', 'type' => 'text', 'required' => true],
                    ['name' => 'numero_colaborador', 'label' => 'Número de colaborador',                         'type' => 'text', 'required' => true],
                    ['name' => 'propiedad',          'label' => 'Nombre de la propiedad',                        'type' => 'text', 'required' => true],
                ],
            ],
        ],
    ],

    'cambio_permisos' => [
        'label'    => 'Cambio de permisos o rol / Modificación',
        'sistemas' => [
            'facto' => [
                'label'  => 'Facto',
                'campos' => [
                    ['name' => 'id_usuario',   'label' => 'ID de usuario',                       'type' => 'text', 'required' => true],
                    ['name' => 'rol_o_centro', 'label' => 'Rol o centro de consumo a modificar', 'type' => 'text', 'required' => true],
                ],
            ],
            'simphony' => [
                'label'  => 'Simphony',
                'nota'   => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'campos' => [
                    ['name' => 'id_usuario_simphony', 'label' => 'ID de usuario de Simphony (Employee)',        'type' => 'text', 'required' => true],
                    ['name' => 'nombre_colaborador',   'label' => 'Nombre completo del colaborador a modificar', 'type' => 'text', 'required' => true],
                ],
            ],
            'opera' => [
                'label'  => 'Opera',
                'nota'   => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'campos' => [
                    ['name' => 'nombre_colaborador', 'label' => 'Nombre completo del colaborador',                            'type' => 'text', 'required' => true],
                    ['name' => 'id_opera',           'label' => 'ID Opera',                                                   'type' => 'text', 'required' => true],
                    ['name' => 'rol_o_id_replicar',  'label' => 'Rol a modificar, o ID de usuario del cual copiar permisos', 'type' => 'text', 'required' => true],
                ],
            ],
        ],
    ],

    'baja' => [
        'label'    => 'Baja de usuario',
        'sistemas' => [
            'facto' => [
                'label'  => 'Facto',
                'nota'   => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'campos' => [
                    ['name' => 'id_usuario',         'label' => 'ID de usuario',                            'type' => 'text', 'required' => true],
                    ['name' => 'nombre_colaborador', 'label' => 'Nombre completo del usuario a dar de baja', 'type' => 'text', 'required' => true],
                ],
            ],
            'simphony' => [
                'label'  => 'Simphony',
                'nota'   => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'campos' => [
                    ['name' => 'id_usuario_simphony', 'label' => 'ID de usuario de Simphony (Employee)',           'type' => 'text', 'required' => true],
                    ['name' => 'nombre_colaborador',   'label' => 'Nombre completo del colaborador a dar de baja',  'type' => 'text', 'required' => true],
                ],
            ],
            'opera' => [
                'label'  => 'Opera',
                'nota'   => 'Un ticket por usuario: no incluyas la información de más de un colaborador en la misma solicitud.',
                'campos' => [
                    ['name' => 'nombre_colaborador', 'label' => 'Nombre completo del colaborador', 'type' => 'text', 'required' => true],
                    ['name' => 'id_opera',           'label' => 'ID Opera',                        'type' => 'text', 'required' => true],
                ],
            ],
        ],
    ],

    'otro' => [
        'label'  => 'Otra falla / Otro',
        // Sin 'sistemas': no pide elegir sistema, va directo a estos campos.
        'campos' => [
            ['name' => 'detalle', 'label' => 'Especifica el problema', 'type' => 'textarea', 'required' => true],
        ],
    ],

];

// Resuelve referencias a plantillas: cualquier sistema que declare
// 'plantilla' => 'nombre' recibe los campos de esa plantilla en vez de
// listarlos de nuevo. Así, agregar un sistema que comparte forma con otro
// (como WinTB con GestHotel) es una sola línea, no una copia del arreglo.
foreach ($tipos as &$tipo) {
    if (!isset($tipo['sistemas'])) {
        continue;
    }
    foreach ($tipo['sistemas'] as &$sistema) {
        if (isset($sistema['plantilla'])) {
            $sistema['campos'] = $plantillas[$sistema['plantilla']] ?? [];
            unset($sistema['plantilla']);
        }
    }
    unset($sistema);
}
unset($tipo);

return ['tipos' => $tipos];
