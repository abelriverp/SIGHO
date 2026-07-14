<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class Condomino extends Model
{
    use BelongsToHotel;

    protected $table = 'condominos';

    protected $fillable = [
        'hotel_code',
        'nombre',
        'telefono',
        'email',
        'identificacion',
        'fecha_alta',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'fecha_alta' => 'date',
    ];

    public function unidades()
    {
        return $this->hasMany(Unidad::class);
    }

    public function estancias()
    {
        return $this->hasMany(Estancia::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
