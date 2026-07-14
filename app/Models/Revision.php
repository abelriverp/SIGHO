<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'problema',
        'descripcion',
        'revision',
        'diagnostico',
        'solucion',
        'resultado',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
