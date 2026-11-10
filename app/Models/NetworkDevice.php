<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class NetworkDevice extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = ['nombre', 'tipo', 'total_ports', 'ubicacion', 'ubicacion_id', 'asset_id', 'ip_gestion'];

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