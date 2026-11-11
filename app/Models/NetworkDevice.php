<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class NetworkDevice extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = ['nombre', 'tipo', 'total_ports', 'ubicacion', 'ubicacion_id', 'asset_id', 'ip_gestion', 'posicion_rack', 'uplink_id', 'uplink_puerto'];

    // Fase 6: de qué equipo cuelga (topología MDF → IDF → switch de acceso).
    public function uplink()
    {
        return $this->belongsTo(NetworkDevice::class, 'uplink_id');
    }

    public function descendientes()
    {
        return $this->hasMany(NetworkDevice::class, 'uplink_id');
    }

    /** Ids de este equipo y todos los que cuelgan de él (evita ciclos al elegir uplink). */
    public function idsRama(): array
    {
        $ids = [$this->id];
        $pendientes = [$this->id];
        while ($pendientes) {
            $hijos = static::whereIn('uplink_id', $pendientes)->pluck('id')->diff($ids)->all();
            $ids = array_merge($ids, $hijos);
            $pendientes = $hijos;
        }
        return $ids;
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function ubicacionCatalogo()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }
    public function ports()
    {
        return $this->hasMany(NetworkPort::class);
    }
}