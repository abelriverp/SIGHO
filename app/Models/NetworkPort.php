<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkPort extends Model
{
    use HasFactory;

    protected $fillable = ['network_device_id', 'port_number', 'connected_to', 'asset_id', 'vlan', 'notas', 'speed', 'status'];

    public const VELOCIDADES = ['FastEthernet' => 'Fast Ethernet (10/100)', 'Gigabit' => 'Gigabit (10/100/1000)', '10-Gigabit' => '10 Gigabit', 'PoE' => 'Gigabit PoE'];

    // Antes: belongsTo(NetworkDevice::class) sin llave, que Laravel buscaba
    // como device_id (no existe). La columna real es network_device_id.
    public function device()
    {
        return $this->belongsTo(NetworkDevice::class, 'network_device_id');
    }

    /** Activo conectado a este puerto (Fase 4). */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
