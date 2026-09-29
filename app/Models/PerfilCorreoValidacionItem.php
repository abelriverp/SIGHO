<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una reserva individual dentro de un análisis de PerfilCorreoValidacion:
 * número de reserva, huésped, fechas y el resultado de validar su correo
 * (ver App\Services\Recepcion\PerfilesCorreoValidatorService::clasificarFila()).
 */
class PerfilCorreoValidacionItem extends Model
{
    protected $table = 'recepcion_perfil_correo_validacion_items';

    public $timestamps = false;

    protected $fillable = [
        'perfil_correo_validacion_id',
        'numero_reserva',
        'huesped',
        'fecha_llegada',
        'fecha_salida',
        'email',
        'estado',
        'motivo',
        'sugerencia',
        'verificacion_smtp',
    ];

    // Etiquetas legibles — mismos estados que produce
    // PerfilesCorreoValidatorService::clasificarFila().
    const ESTADOS = [
        'OK'                => 'OK',
        'SIN_CORREO'        => 'Sin correo',
        'SUFIJO_INVALIDO'   => 'Sufijo/extensión inválida',
        'DOMINIO_INVALIDO'  => 'Dominio inválido',
        'FORMATO_INVALIDO'  => 'Formato inválido',
        'RECHAZADO_SMTP'    => 'Rechazado por el servidor',
        'GENERICO'          => 'Genérico (OTA)',
        'FALSO'             => 'Falso/desechable',
    ];

    /**
     * Agrupa los 8 estados crudos en las 6 categorías que muestran las
     * tarjetas resumen y el filtro de recepcion.perfiles.show — el MISMO
     * mapa alimenta ambos (ver PerfilCorreoValidacionController::store()),
     * a propósito, para no repetir el bug real del validador de emails
     * (2026-08-22): ahí las tarjetas contaban de un modo y el filtro
     * comparaba contra el estado crudo exacto, así que filtrar por una
     * tarjeta podía traer cero resultados aunque la tarjeta mostrara
     * varios.
     */
    const GRUPOS_FILTRO = [
        'OK'               => ['label' => 'OK', 'estados' => ['OK']],
        'SIN_CORREO'       => ['label' => 'Sin correo', 'estados' => ['SIN_CORREO']],
        'SUFIJO_INVALIDO'  => ['label' => 'Sufijo inválido', 'estados' => ['SUFIJO_INVALIDO']],
        'DOMINIO_INVALIDO' => [
            'label'   => 'Dominio/formato inválido',
            'estados' => ['DOMINIO_INVALIDO', 'FORMATO_INVALIDO'],
        ],
        'RECHAZADO_SMTP'   => ['label' => 'Rechazado por el servidor', 'estados' => ['RECHAZADO_SMTP']],
        'OTROS'            => ['label' => 'Genérico / falso', 'estados' => ['GENERICO', 'FALSO']],
    ];

    // Clases Tailwind para la insignia de cada estado.
    private const COLORES = [
        'OK'               => 'bg-green-100 text-green-700',
        'SIN_CORREO'       => 'bg-amber-100 text-amber-700',
        'SUFIJO_INVALIDO'  => 'bg-yellow-100 text-yellow-700',
        'DOMINIO_INVALIDO' => 'bg-red-100 text-red-700',
        'FORMATO_INVALIDO' => 'bg-red-100 text-red-700',
        'RECHAZADO_SMTP'   => 'bg-red-200 text-red-800',
        'GENERICO'         => 'bg-blue-100 text-blue-700',
        'FALSO'            => 'bg-slate-200 text-slate-600',
    ];

    private const VERIFICACION_SMTP_LABELS = [
        'ACEPTADO'       => 'Aceptado por el servidor',
        'RECHAZADO'      => 'Rechazado por el servidor',
        'NO_CONCLUYENTE' => 'No concluyente',
    ];

    public function validacion()
    {
        return $this->belongsTo(PerfilCorreoValidacion::class, 'perfil_correo_validacion_id');
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function badgeClass(): string
    {
        return self::COLORES[$this->estado] ?? 'bg-slate-100 text-slate-600';
    }

    public function verificacionSmtpLabel(): string
    {
        if ($this->verificacion_smtp === null) {
            return '—';
        }

        return self::VERIFICACION_SMTP_LABELS[$this->verificacion_smtp] ?? $this->verificacion_smtp;
    }
}
