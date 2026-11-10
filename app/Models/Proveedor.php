<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/** Proveedores de TI (Fase 3, nov 2026). */
class Proveedor extends Model
{
    use BelongsToHotel;

    protected $table = 'proveedores';

    protected $fillable = ['hotel_code', 'nombre', 'rfc', 'contacto', 'telefono', 'email', 'soporte', 'notas', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    public function licencias()
    {
        return $this->hasMany(Licencia::class);
    }

    public function activos()
    {
        return $this->hasMany(Asset::class);
    }
}
