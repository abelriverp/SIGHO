<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/** Contratos de soporte, mantenimiento, enlaces, polizas (Fase 3). */
class Contrato extends Model
{
    use BelongsToHotel;

    public const TIPOS = [
        'soporte'        => 'Soporte técnico',
        'mantenimiento'  => 'Mantenimiento',
        'enlace'         => 'Enlace de internet / telecom',
        'licenciamiento' => 'Licenciamiento',
        'arrendamiento'  => 'Arrendamiento de equipo',
        'poliza'         => 'Póliza / garantía extendida',
        'otro'           => 'Otro',
    ];

    public const PERIODICIDAD = ['mensual' => 'Mensual', 'trimestral' => 'Trimestral', 'anual' => 'Anual', 'unico' => 'Pago único'];

    protected $fillable = [
        'hotel_code', 'proveedor_id', 'nombre', 'tipo', 'ancho_banda', 'ip_publica', 'numero', 'fecha_inicio', 'fecha_fin',
        'costo', 'moneda', 'periodicidad_pago', 'renovacion_automatica', 'dias_aviso', 'sla',
        'contacto_soporte', 'notas', 'documento_path', 'activo',
    ];

    protected $casts = [
        'fecha_inicio'          => 'date',
        'fecha_fin'             => 'date',
        'costo'                 => 'decimal:2',
        'renovacion_automatica' => 'boolean',
        'activo'                => 'boolean',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function activos()
    {
        return $this->belongsToMany(Asset::class, 'asset_contrato');
    }

    public function diasRestantes(): ?int
    {
        return $this->fecha_fin ? (int) now()->startOfDay()->diffInDays($this->fecha_fin, false) : null;
    }

    /** 'vigente' | 'por_vencer' | 'vencido' | null */
    public function estadoVigencia(): ?string
    {
        $d = $this->diasRestantes();
        if ($d === null) return null;
        if ($d < 0) return 'vencido';
        return $d <= $this->dias_aviso ? 'por_vencer' : 'vigente';
    }
}
