<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Un campo dinámico del formulario de ticket (ej. "ID de usuario", "RFC").
 * Cuelga de UNA sola de dos relaciones posibles — nunca ambas, nunca
 * ninguna (ver TicketCategoriaController, que valida esto al guardar):
 *   - ticket_subcategoria_id: la categoría exige elegir un sistema.
 *   - ticket_categoria_id:    la categoría no tiene subcategorías (ej. "Otro").
 */
class TicketCampo extends Model
{
    use HasFactory;

    public const TIPOS = [
        'text'     => 'Texto corto',
        'textarea' => 'Texto largo',
        'email'    => 'Correo electrónico',
    ];

    protected $fillable = [
        'ticket_categoria_id',
        'ticket_subcategoria_id',
        'name',
        'label',
        'tipo_campo',
        'requerido',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'requerido' => 'boolean',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(TicketCategoria::class, 'ticket_categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(TicketSubcategoria::class, 'ticket_subcategoria_id');
    }
}
