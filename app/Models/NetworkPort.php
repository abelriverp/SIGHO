<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkPort extends Model
{
    use HasFactory;

    protected $fillable = ['network_device_id', 'port_number', 'connected_to', 'speed', 'status'];
    public function device()
    {
        return $this->belongsTo(NetworkDevice::class);
    }
}