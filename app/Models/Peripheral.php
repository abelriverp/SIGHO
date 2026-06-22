<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peripheral extends Model
{
    use HasFactory;

    protected $fillable = ['asset_id', 'tipo', 'marca', 'modelo', 'numero_serie', 'status'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}