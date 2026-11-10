<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/** Textos reutilizables para notas y soluciones de tickets (Fase 2). */
class RespuestaPredefinida extends Model
{
    use BelongsToHotel;

    protected $table = 'respuestas_predefinidas';

    protected $fillable = ['hotel_code', 'departamento', 'titulo', 'texto', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    /** Las del departamento del usuario + las generales. */
    public static function paraDepartamento(?string $departamento)
    {
        return static::where('activo', true)
            ->where(fn ($q) => $q->whereNull('departamento')->when($departamento, fn ($w) => $w->orWhere('departamento', $departamento)))
            ->orderBy('titulo')
            ->get(['id', 'titulo', 'texto']);
    }
}
