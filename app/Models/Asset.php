<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'nomenclatura', 'area', 'encargado', 'ip_address', 'status', 'notas', 'pdf_path', 'imagen_path',
        'division', 'tipo_equipo', 'marca', 'modelo', 'numero_serie', 'fecha_adquisicion', 'factura',
        'procesador', 'ram', 'tipo_ram', 'almacenamiento', 'sistema_operativo', 'paqueteria_office',
        'monitor_marca', 'monitor_modelo', 'monitor_ns',
        'mouse_marca', 'mouse_modelo', 'mouse_ns',
        'teclado_marca', 'teclado_modelo', 'teclado_ns'
    ];

    /**
     * Casts de atributos.
     * Convierte la fecha de adquisición en un objeto Carbon automáticamente.
     */
    protected function casts(): array
    {
        return [
            'fecha_adquisicion' => 'date', // Te permite usar: $asset->fecha_adquisicion->format('d/m/Y')
        ];
    }

    // ==========================================
    // RELACIONES
    // ==========================================

    /**
     * Relación: Un activo tiene muchas revisiones (Historial completo)
     */
    public function revisions()
    {
        return $this->hasMany(Revision::class);
    }

    /**
     * Relación Optimizada: Obtiene únicamente la ÚLTIMA revisión del activo.
     * Súper útil para el index o tableros de control.
     */
    public function latestRevision()
    {
        return $this->hasOne(Revision::class)->latestOfMany();
    }
}