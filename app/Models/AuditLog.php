<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'accion',
        'descripcion',
        'auditable_type',
        'auditable_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos'     => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Registra una acción administrativa crítica.
     *
     * @param string     $accion       Clave corta, ej: 'usuario.creado', 'propiedad.eliminada'
     * @param string     $descripcion  Texto legible para mostrar en la bitácora
     * @param Model|null $modelo       Registro afectado (usuario, propiedad, etc.)
     * @param array|null $antes        Snapshot de campos relevantes antes del cambio
     * @param array|null $despues      Snapshot de campos relevantes después del cambio
     */
    public static function registrar(
        string $accion,
        string $descripcion,
        ?Model $modelo = null,
        ?array $antes = null,
        ?array $despues = null
    ): void {
        static::create([
            'user_id'          => auth()->id(),
            'accion'           => $accion,
            'descripcion'      => $descripcion,
            'auditable_type'   => $modelo ? get_class($modelo) : null,
            'auditable_id'     => $modelo?->getKey(),
            'datos_anteriores' => $antes,
            'datos_nuevos'     => $despues,
            'ip'               => request()->ip(),
        ]);
    }
}
