<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Mantenimiento preventivo que genera tickets automaticamente (Fase 2). */
class TicketRecurrente extends Model
{
    use BelongsToHotel;

    protected $table = 'tickets_recurrentes';

    public const FRECUENCIAS = [
        'diaria'     => 'Diaria',
        'semanal'    => 'Semanal',
        'quincenal'  => 'Quincenal',
        'mensual'    => 'Mensual',
        'trimestral' => 'Trimestral',
        'semestral'  => 'Semestral',
        'anual'      => 'Anual',
    ];

    protected $fillable = [
        'hotel_code', 'titulo', 'descripcion', 'departamento_destino', 'prioridad',
        'asset_id', 'ubicacion_id', 'frecuencia', 'proxima_fecha', 'activo',
        'creado_por', 'ultimo_ticket_id',
    ];

    protected $casts = [
        'proxima_fecha' => 'date',
        'activo'        => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function ultimoTicket()
    {
        return $this->belongsTo(Ticket::class, 'ultimo_ticket_id');
    }

    /** Siguiente fecha a partir de $desde segun la frecuencia. */
    public function siguienteFecha(Carbon $desde): Carbon
    {
        $d = $desde->copy();
        return match ($this->frecuencia) {
            'diaria'     => $d->addDay(),
            'semanal'    => $d->addWeek(),
            'quincenal'  => $d->addWeeks(2),
            'mensual'    => $d->addMonthNoOverflow(),
            'trimestral' => $d->addMonthsNoOverflow(3),
            'semestral'  => $d->addMonthsNoOverflow(6),
            default      => $d->addYearNoOverflow(),
        };
    }
}
