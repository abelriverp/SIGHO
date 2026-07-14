<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    // La tabla real se llama 'hoteles' (Laravel buscaría 'hotels' por defecto)
    protected $table = 'hoteles';

    protected $fillable = [
        'hotel_code',
        'nombre',
        'tipo', // 'hotel' | 'condominio' | 'mixto'
    ];

    const TIPOS = [
        'hotel'      => 'Hotel',
        'condominio' => 'Condominio',
        'mixto'      => 'Mixto',
    ];

    /**
     * Usuarios asignados a esta propiedad.
     */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'hotel_code', 'hotel_code');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
