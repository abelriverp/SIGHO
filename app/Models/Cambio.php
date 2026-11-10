<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Cambio planeado con riesgo (Fase 5): plan, ventana, rollback y
 * aprobacion de gerente/admin antes de ejecutarlo.
 */
class Cambio extends Model
{
    use BelongsToHotel;

    public const TIPOS = [
        'normal'     => 'Normal (requiere aprobación)',
        'estandar'   => 'Estándar (preaprobado, bajo riesgo)',
        'emergencia' => 'Emergencia (se aprueba después)',
    ];

    public const ESTADOS = [
        'borrador'              => 'Borrador',
        'pendiente_aprobacion'  => 'Pendiente de aprobación',
        'aprobado'              => 'Aprobado',
        'rechazado'             => 'Rechazado',
        'en_ejecucion'          => 'En ejecución',
        'completado'            => 'Completado',
        'fallido'               => 'Fallido (se aplicó rollback)',
        'cancelado'             => 'Cancelado',
    ];

    /** Transiciones permitidas: estado actual => estados siguientes. */
    public const TRANSICIONES = [
        'borrador'             => ['pendiente_aprobacion', 'cancelado'],
        'pendiente_aprobacion' => ['aprobado', 'rechazado', 'cancelado'],
        'aprobado'             => ['en_ejecucion', 'cancelado'],
        'rechazado'            => ['borrador'],
        'en_ejecucion'         => ['completado', 'fallido'],
        'completado'           => [],
        'fallido'              => ['borrador'],
        'cancelado'            => [],
    ];

    protected $fillable = [
        'hotel_code', 'titulo', 'descripcion', 'tipo', 'riesgo', 'impacto', 'plan_implementacion',
        'plan_rollback', 'ventana_inicio', 'ventana_fin', 'estado', 'problema_id', 'solicitado_por',
        'aprobado_por', 'aprobado_at', 'comentario_aprobacion', 'resultado', 'completado_at',
    ];

    protected $casts = [
        'ventana_inicio' => 'datetime',
        'ventana_fin'    => 'datetime',
        'aprobado_at'    => 'datetime',
        'completado_at'  => 'datetime',
    ];

    public function activos()
    {
        return $this->belongsToMany(Asset::class, 'asset_cambio');
    }

    public function problema()
    {
        return $this->belongsTo(Problema::class);
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function puedePasarA(string $estado): bool
    {
        return in_array($estado, self::TRANSICIONES[$this->estado] ?? [], true);
    }

    public function editable(): bool
    {
        return in_array($this->estado, ['borrador', 'rechazado', 'fallido'], true);
    }
}
