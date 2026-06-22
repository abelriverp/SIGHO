<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    // Esto le da permiso a Laravel para guardar el nombre desde el formulario
    protected $fillable = [
        'nombre',
    ];
}