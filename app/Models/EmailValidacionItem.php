<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un correo individual dentro de un análisis (EmailValidacion). No usa
 * BelongsToHotel a propósito: siempre se consulta a través de la relación
 * con el encabezado, que ya está filtrado por propiedad.
 */
class EmailValidacionItem extends Model
{
    protected $table = 'recepcion_email_validacion_items';

    public $timestamps = false;

    protected $fillable = [
        'email_validacion_id',
        'email',
        'estado',
        'score',
        'sugerencia',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    // Etiquetas legibles para la UI — mismos estados que produce
    // EmailValidatorService::analizarEmail().
    const ESTADOS = [
        'OK'                => 'OK',
        'ERROR TIPOGRAFICO' => 'Error tipográfico',
        'GENERICO'          => 'Genérico (OTA)',
        'FALSO'             => 'Falso / desechable',
        'DOMINIO INVALIDO'  => 'Dominio inválido',
        'FORMATO INVALIDO'  => 'Formato inválido',
    ];

    // Agrupa los 6 estados crudos en las mismas 4 categorías que usan las
    // tarjetas resumen de email/show.blade.php (ver EmailValicacionController::store(),
    // donde FALSO/DOMINIO INVALIDO/FORMATO INVALIDO se suman juntos dentro
    // de "falsos"). El filtro "Estado" del listado usa este mapa en lugar
    // de ESTADOS directamente para que, al filtrar por "Falso / inválido",
    // siempre aparezcan los mismos correos que cuenta la tarjeta
    // "Falsos/Inválidos" — antes se podía filtrar por el estado exacto
    // "FALSO" y quedar en cero aunque la tarjeta mostrara varios, si esos
    // correos en realidad eran DOMINIO INVALIDO o FORMATO INVALIDO.
    const GRUPOS_FILTRO = [
        'OK'                => ['label' => 'OK', 'estados' => ['OK']],
        'ERROR TIPOGRAFICO' => ['label' => 'Error tipográfico', 'estados' => ['ERROR TIPOGRAFICO']],
        'GENERICO'          => ['label' => 'Genérico (OTA)', 'estados' => ['GENERICO']],
        'FALSO'             => [
            'label'   => 'Falso / inválido',
            'estados' => ['FALSO', 'DOMINIO INVALIDO', 'FORMATO INVALIDO'],
        ],
    ];

    // Clases Tailwind para la insignia de cada estado — un solo lugar en
    // vez de repetir este mapeo en cada vista que liste correos.
    private const COLORES = [
        'OK'                => 'bg-green-100 text-green-700',
        'ERROR TIPOGRAFICO' => 'bg-yellow-100 text-yellow-700',
        'GENERICO'          => 'bg-blue-100 text-blue-700',
        'FALSO'             => 'bg-red-100 text-red-700',
        'DOMINIO INVALIDO'  => 'bg-slate-200 text-slate-600',
        'FORMATO INVALIDO'  => 'bg-red-100 text-red-700',
    ];

    public function validacion()
    {
        return $this->belongsTo(EmailValidacion::class, 'email_validacion_id');
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function badgeClass(): string
    {
        return self::COLORES[$this->estado] ?? 'bg-slate-100 text-slate-600';
    }
}
