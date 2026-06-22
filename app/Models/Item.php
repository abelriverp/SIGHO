<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'categoria',
        'cantidad',
        'status',   
        'descripcion',
        'imagen_path',
        'pdf_path',   
    ];
}