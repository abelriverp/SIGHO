<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/** Historial de caidas y recuperaciones del monitoreo por ping (Fase 4). */
class MonitoreoEvento extends Model
{
    use BelongsToHotel;

    protected $table = 'monitoreo_eventos';

    protected $fillable = ['hotel_code', 'asset_id', 'tipo', 'ocurrido_at', 'duracion_min', 'ticket_id'];

    protected $casts = ['ocurrido_at' => 'datetime'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
