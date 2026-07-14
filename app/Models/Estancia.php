<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class Estancia extends Model
{
    use BelongsToHotel;

    protected $table = 'estancias';

    protected $fillable = [
        'hotel_code',
        'unidad_id',
        'condomino_id',
        'tipo_visitante',
        'nombre_visitante',
        'identificacion_placas',
        'num_acompanantes',
        'fecha_llegada_prevista',
        'fecha_salida_prevista',
        'checkin_real',
        'checkout_real',
        'estado',
        'tarifa_noche',
        'notas',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_llegada_prevista' => 'date',
        'fecha_salida_prevista'  => 'date',
        'checkin_real'           => 'datetime',
        'checkout_real'          => 'datetime',
        'num_acompanantes'       => 'integer',
        'tarifa_noche'           => 'float',
    ];

    const TIPOS_VISITANTE = [
        'propietario'     => 'Propietario',
        'familiar'        => 'Familiar',
        'inquilino_renta' => 'Inquilino / Renta',
        'invitado'        => 'Invitado',
        'servicio'        => 'Servicio',
    ];

    const ESTADOS = [
        'reservada'  => 'Reservada',
        'confirmada' => 'Confirmada',
        'checkin'    => 'En estancia',
        'checkout'   => 'Finalizada',
        'cancelada'  => 'Cancelada',
        'no_show'    => 'No se presentó',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function condomino()
    {
        return $this->belongsTo(Condomino::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function tipoVisitanteLabel(): string
    {
        return self::TIPOS_VISITANTE[$this->tipo_visitante] ?? $this->tipo_visitante;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function puedeHacerCheckin(): bool
    {
        return in_array($this->estado, ['reservada', 'confirmada']);
    }

    public function puedeHacerCheckout(): bool
    {
        return $this->estado === 'checkin';
    }

    public function nochesEstancia(): ?int
    {
        if (!$this->checkin_real) return null;
        $fin = $this->checkout_real ?? now();
        return max(1, (int) $this->checkin_real->diffInDays($fin));
    }
}
