<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class Ticket extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_code',
        'user_id',
        'resolved_by',
        'operador_id',
        'area',
        'departamento_destino',
        'veces_reasignado',
        'descripcion_ubicacion',
        'asset_id',
        'ubicacion_id',
        'csat_calificacion',
        'csat_comentario',
        'csat_at',
        'descripcion',
        'campos_extra',
        'prioridad',
        'status',
        'archivo_path',
        'archivos_evidencia',
        'fotos_resolucion',
        'notas_admin',
        'fecha_resolucion',
        'sla_escalado_at',
        'ultimo_recordatorio_at',
        'confirmado_usuario',
        'fecha_confirmacion',
        'motivo_reapertura',
        'token_resolucion',
        'token_confirmacion',
        'ip_resolucion',
        'ip_confirmacion',
        // Fase 6
        'primera_respuesta_at',
        'ticket_padre_id',
        'proveedor_id',
        'escalado_proveedor_at',
        'folio_proveedor',
    ];

    protected $casts = [
        'fotos_resolucion'   => 'array',
        'archivos_evidencia' => 'array',
        'campos_extra'       => 'array',
        'confirmado_usuario' => 'boolean',
        'fecha_confirmacion' => 'datetime',
        'fecha_resolucion'   => 'datetime',
        'sla_escalado_at'    => 'datetime',
        'ultimo_recordatorio_at' => 'datetime',
        'csat_at'            => 'datetime',
        'primera_respuesta_at'  => 'datetime',
        'escalado_proveedor_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Fase 6: al resolverse sin una respuesta previa, la resolución
        // cuenta como primera respuesta (KPI tiempo a primera respuesta).
        static::saving(function (Ticket $t) {
            if ($t->fecha_resolucion && ! $t->primera_respuesta_at) {
                $t->primera_respuesta_at = $t->fecha_resolucion;
            }
        });
    }

    // Fase 6: agrupación padre/hijo (una caída genera muchos reportes).
    public function padre()
    {
        return $this->belongsTo(Ticket::class, 'ticket_padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(Ticket::class, 'ticket_padre_id');
    }

    // Fase 6: escalado a un proveedor externo.
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function abierto(): bool
    {
        return in_array($this->status, ['Abierto', 'Reabierto'], true);
    }

    // Fase 2: activo y ubicacion afectados.
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    // Material de Inventario de Suministros usado para atender el ticket.
    public function materiales()
    {
        return $this->hasMany(InventarioMovimiento::class);
    }

    // Fase 5: problemas a los que pertenece este ticket.
    public function problemas()
    {
        return $this->belongsToMany(Problema::class, 'problema_ticket');
    }

    // Relación con el usuario que creó el ticket
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Usuario (técnico/admin) que marcó ESTE ticket como resuelto. Distinto
    // de operador(): este siempre tiene cuenta en el sistema.
    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Relación con las notas de la bitácora
    public function notas()
    {
        return $this->hasMany(TicketNota::class);
    }

    // Operador (sin cuenta en el sistema) que quedó registrado como quien
    // ejecutó el trabajo, aparte de quien lo marcó resuelto (resolved_by).
    public function operador()
    {
        return $this->belongsTo(Operador::class);
    }

    /**
     * true si se envió un recordatorio (ultimo_recordatorio_at) y desde
     * entonces NADIE del lado de soporte (técnico/admin/gerente — cualquier
     * user_id distinto al que reportó el ticket) ha dejado una nota en la
     * bitácora explicando qué pasa con el ticket. Mientras esto sea true,
     * TicketController bloquea resolver/reasignar (ver TicketPolicy y
     * TicketController::resolve()/reasignar()): antes de tocar el ticket,
     * el departamento debe explicar por qué sigue "en pausa" (ej. falta de
     * material) — no basta con resolverlo o reasignarlo en silencio.
     *
     * Se calcula a partir de datos que YA existen (ultimo_recordatorio_at +
     * timestamps de notas), sin columna nueva: en cuanto alguien de soporte
     * agrega CUALQUIER nota después del recordatorio, esto vuelve a false
     * solo. Nota: si nadie ha cargado la relación `notas`, dispara una
     * consulta ligera (exists()); llamar dentro de un loop de muchos
     * tickets sin eager-load causaría N+1 — no es el caso de uso actual
     * (se llama sobre UN ticket en tickets/show.blade.php).
     */
    public function requiereJustificacionRecordatorio(): bool
    {
        if (!$this->ultimo_recordatorio_at) {
            return false;
        }

        return !$this->notas()
            ->where('user_id', '!=', $this->user_id)
            ->where('created_at', '>=', $this->ultimo_recordatorio_at)
            ->exists();
    }
}