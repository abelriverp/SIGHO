<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class NetworkDevice extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = ['nombre', 'tipo', 'total_ports', 'ubicacion'];
    public function ports()
    {
        return $this->hasMany(NetworkPort::class);
    }
}