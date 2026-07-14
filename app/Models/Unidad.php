<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class Unidad extends Model
{
    use BelongsToHotel;

    protected $table = 'unidades';

    protected $fillable = [
        'hotel_code',
        'identificador',
        'torre',
        'piso',
        'tipo_unidad',
        'condomino_id',
        'estatus',
        'notas',
    ];

    const ESTATUS = ['Vacia', 'Ocupada', 'Rentada', 'Mantenimiento'];

    public function condomino()
    {
        return $this->belongsTo(Condomino::class);
    }

    public function estancias()
    {
        return $this->hasMany(Estancia::class);
    }

    // Estancia actualmente "en curso" (ya hizo check-in, aún no checkout)
    public function estanciaActiva()
    {
        return $this->hasOne(Estancia::class)->where('estado', 'checkin')->latestOfMany();
    }

    public function proximaReserva()
    {
        return $this->hasOne(Estancia::class)
            ->whereIn('estado', ['reservada', 'confirmada'])
            ->where('fecha_llegada_prevista', '>=', now()->toDateString())
            ->oldestOfMany('fecha_llegada_prevista');
    }

    public function nombreCompleto(): string
    {
        return $this->torre ? "{$this->torre} - {$this->identificador}" : $this->identificador;
    }
}
