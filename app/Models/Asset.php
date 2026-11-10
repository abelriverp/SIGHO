<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalogo unico de activos de TI (Fase 1, nov 2026).
 *
 * Un activo puede ser una PC, un AP, una TV, un STB, un switch, un UPS,
 * una camara... o un periferico (monitor, mouse, teclado) ligado a otro
 * activo mediante parent_id. Vive en una Ubicacion (habitacion, area,
 * oficina, site) y puede estar asignado (resguardo) a un usuario.
 */
class Asset extends Model
{
    use HasFactory, BelongsToHotel;

    /** Categorias generales del catalogo (filtro principal). */
    public const CATEGORIAS = [
        'Cómputo',
        'Periférico',
        'Impresión',
        'Red',
        'TV y entretenimiento',
        'Telefonía',
        'Seguridad',
        'Energía',
        'Punto de venta',
        'Otro',
    ];

    /** Ciclo de vida. Se conservan "Activo"/"En Reparación"/"Baja" historicos. */
    public const ESTADOS = [
        'En almacén',
        'Activo',
        'En Reparación',
        'Baja',
    ];

    /** Tipos sugeridos por categoria (lista abierta: el campo es texto). */
    public const TIPOS_SUGERIDOS = [
        'Cómputo'              => ['CPU', 'Laptop', 'All-In-One', 'Servidor', 'Tablet'],
        'Periférico'           => ['Monitor', 'Mouse', 'Teclado', 'Escáner', 'Lector de banda', 'Webcam', 'Diadema'],
        'Impresión'            => ['Impresora láser', 'Impresora de tickets', 'Multifuncional', 'Etiquetadora'],
        'Red'                  => ['Switch', 'Access Point', 'Router', 'Firewall', 'Patch Panel', 'Media converter'],
        'TV y entretenimiento' => ['TV', 'STB ENSEO', 'Control remoto', 'Pantalla de señalización', 'Raspberry Pi'],
        'Telefonía'            => ['Teléfono IP', 'Teléfono analógico', 'Conmutador'],
        'Seguridad'            => ['Cámara IP', 'NVR/DVR', 'Encoder Saflok', 'Cerradura', 'Caja fuerte'],
        'Energía'              => ['UPS', 'Regulador', 'PDU'],
        'Punto de venta'       => ['Terminal POS', 'Cajón de dinero', 'Impresora de comandas'],
        'Otro'                 => [],
    ];

    protected $fillable = [
        'parent_id', 'nombre', 'categoria', 'nomenclatura', 'area', 'ubicacion_id',
        'encargado', 'asignado_a', 'ip_address', 'mac_address', 'status', 'notas',
        'pdf_path', 'imagen_path', 'division', 'tipo_equipo', 'marca', 'modelo',
        'numero_serie', 'fecha_adquisicion', 'factura', 'proveedor', 'proveedor_id', 'costo',
        'garantia_hasta', 'procesador', 'ram', 'tipo_ram', 'almacenamiento',
        'sistema_operativo', 'paqueteria_office', 'especificaciones',
        'fecha_baja', 'motivo_baja',
    ];

    protected function casts(): array
    {
        return [
            'fecha_adquisicion' => 'date',
            'garantia_hasta'    => 'date',
            'fecha_baja'        => 'date',
            'costo'             => 'decimal:2',
            'especificaciones'  => 'array',
        ];
    }

    // ==========================================
    // RELACIONES
    // ==========================================

    public function revisions()
    {
        return $this->hasMany(Revision::class);
    }

    public function latestRevision()
    {
        return $this->hasOne(Revision::class)->latestOfMany();
    }

    public function proveedorCatalogo()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function contratos()
    {
        return $this->belongsToMany(Contrato::class, 'asset_contrato');
    }

    public function licencias()
    {
        return $this->belongsToMany(Licencia::class, 'asset_licencia');
    }

    public function bajas()
    {
        return $this->hasMany(BajaActivo::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    /** Usuario que tiene el activo bajo resguardo. */
    public function asignado()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    /** Activo al que pertenece (p. ej. el CPU de un monitor). */
    public function padre()
    {
        return $this->belongsTo(Asset::class, 'parent_id');
    }

    /** Perifericos y componentes ligados a este activo. */
    public function componentes()
    {
        return $this->hasMany(Asset::class, 'parent_id');
    }

    // ==========================================
    // SCOPES Y AYUDAS
    // ==========================================

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'Baja'));
    }

    /** Garantia vigente que vence dentro de $dias. */
    public function scopeGarantiaPorVencer(Builder $query, int $dias = 30): Builder
    {
        return $query->whereNotNull('garantia_hasta')
            ->whereDate('garantia_hasta', '>=', now()->toDateString())
            ->whereDate('garantia_hasta', '<=', now()->addDays($dias)->toDateString());
    }

    /** 'vigente' | 'por_vencer' | 'vencida' | null (sin dato). */
    public function estadoGarantia(int $diasAviso = 30): ?string
    {
        if (! $this->garantia_hasta) {
            return null;
        }
        if ($this->garantia_hasta->isPast() && ! $this->garantia_hasta->isToday()) {
            return 'vencida';
        }
        return $this->garantia_hasta->lte(now()->addDays($diasAviso)) ? 'por_vencer' : 'vigente';
    }

    /** Texto corto para listados: "1205 · Habitación" o el area libre. */
    public function getUbicacionTextoAttribute(): string
    {
        if ($this->ubicacion) {
            return $this->ubicacion->etiqueta;
        }
        return (string) ($this->area ?: '—');
    }
}
