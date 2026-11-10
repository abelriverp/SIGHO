<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Solicitud de baja de un activo (Fase 3): un tecnico la pide con motivo y
 * dictamen; un gerente o admin la aprueba o rechaza. Al aprobarse el activo
 * pasa a estado "Baja" y se puede imprimir el acta.
 */
class BajaActivo extends Model
{
    use BelongsToHotel;

    protected $table = 'bajas_activos';

    public const DESTINOS = [
        'reciclaje'   => 'Reciclaje / residuo electrónico',
        'donacion'    => 'Donación',
        'destruccion' => 'Destrucción',
        'devolucion'  => 'Devolución a proveedor / arrendadora',
        'otro'        => 'Otro',
    ];

    protected $fillable = [
        'hotel_code', 'asset_id', 'solicitado_por', 'motivo', 'dictamen_path', 'destino', 'estado',
        'revisado_por', 'revisado_at', 'comentario_revision', 'estado_anterior',
    ];

    protected $casts = ['revisado_at' => 'datetime'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
