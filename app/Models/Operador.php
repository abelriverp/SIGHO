<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Persona que ejecuta trabajo de campo pero no tiene cuenta en el sistema
 * (sin login, sin rol, sin permisos). Es un catálogo COMPARTIDO por
 * departamento (ej. Ana y Beto, dos técnicos del mismo
 * departamento, ven y usan el mismo operador "Juan Pérez" — no listas
 * privadas por técnico), para poder dejar constancia de quién resolvió un
 * ticket y medir cuántos tickets sacó cada quien (ver
 * DashboardMetricsService::miDesempeno/porOperador).
 *
 * `tecnico_id` es SOLO quién lo registró (auditoría/soporte) — la
 * visibilidad y el permiso de edición se deciden por `departamento`
 * (mismo campo string que ya usan users.departamento y
 * tickets.departamento_destino), ver scopeDeDepartamento() y
 * OperadorPolicy.
 *
 * No se borra físicamente desde la UI normal: se desactiva (activo=false)
 * para no romper el conteo histórico de tickets ya resueltos con este
 * operador. Ver migración create_operadores_table.
 */
class Operador extends Model
{
    use HasFactory, BelongsToHotel;

    // Explícito: el pluralizador de Eloquent convierte "Operador" al inglés
    // ("operadors"), no al español ("operadores") que es el nombre real de
    // la tabla (ver migración create_operadores_table).
    protected $table = 'operadores';

    protected $fillable = [
        'hotel_code',
        'departamento',
        'tecnico_id',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Técnico que dio de alta a este operador. Ya NO decide quién puede
    // verlo/usarlo (eso es scopeDeDepartamento) — solo queda como dato de
    // auditoría/soporte, igual que "creado_por" en otros catálogos.
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    // Tickets en los que quedó registrado que este operador hizo el trabajo.
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Los del departamento (para el selector al resolver un ticket y para
    // la pantalla "Operadores de mi departamento"): visibilidad REAL de
    // hoy — cualquier técnico del mismo departamento ve y puede usar el
    // mismo catálogo, sin importar quién lo dio de alta.
    public function scopeDeDepartamento($query, ?string $departamento)
    {
        return $query->where('departamento', $departamento);
    }
}
