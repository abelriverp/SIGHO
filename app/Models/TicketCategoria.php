<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * "Tipo de solicitud" del catálogo dinámico de tickets (Alta de usuario,
 * Cambio de contraseña, Baja, Otro, ...). Reemplaza lo que antes vivía
 * hardcodeado en config/tickets_sistemas.php, para que admin/gerente lo
 * administren desde el panel sin tocar código.
 *
 * Por propiedad (BelongsToHotel): cada hotel_code tiene su propio catálogo,
 * a diferencia de Departamento/Area que son globales — no todas las
 * propiedades usan los mismos sistemas.
 *
 * Generalizado por departamento_id: no asume "Sistemas", cualquier área
 * puede tener sus propias categorías.
 */
class TicketCategoria extends Model
{
    use HasFactory;
    use BelongsToHotel;

    protected $fillable = [
        'hotel_code',
        'departamento_id',
        'label',
        'requiere_subcategoria',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_subcategoria' => 'boolean',
            'activo'                => 'boolean',
        ];
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function subcategorias()
    {
        return $this->hasMany(TicketSubcategoria::class)->orderBy('orden');
    }

    /**
     * Campos propios de esta categoría, solo relevantes cuando
     * requiere_subcategoria = false (ej. "Otro" -> un campo de texto).
     */
    public function campos()
    {
        return $this->hasMany(TicketCampo::class)->orderBy('orden');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Todas las categorías activas de un departamento (por nombre, igual
     * que Ticket::departamento_destino) para la propiedad activa, con sus
     * subcategorías/campos ya cargados — pensado para armar de un solo tiro
     * el catálogo que necesita el formulario de creación de tickets.
     */
    public static function catalogoParaDepartamento(string $departamentoNombre, string $hotelCode)
    {
        $departamento = Departamento::where('nombre', $departamentoNombre)->first();

        if (!$departamento) {
            return collect();
        }

        return static::deHotel($hotelCode)
            ->where('departamento_id', $departamento->id)
            ->activos()
            ->orderBy('orden')
            ->with([
                'subcategorias' => fn ($q) => $q->activos(),
                'subcategorias.campos',
                'campos',
            ])
            ->get();
    }

    /**
     * Todo el catálogo activo de la propiedad, agrupado por nombre de
     * departamento y en arreglos planos (no Eloquent), listo para
     * @json() en create.blade.php: el JS del formulario decide qué
     * categorías mostrar según el departamento_destino elegido, sin volver
     * a pedirle nada al servidor.
     *
     * Forma: ['Sistemas' => ['12' => ['label'=>..., 'sistemas'=>['45'=>[...]]]], ...]
     * Una categoría con requiere_subcategoria=false trae 'campos' directo
     * en vez de 'sistemas' (igual que antes vivía en config/tickets_sistemas.php).
     */
    public static function catalogoAgrupadoPorDepartamento(string $hotelCode): array
    {
        $categorias = static::deHotel($hotelCode)
            ->activos()
            ->with([
                'departamento',
                'subcategorias' => fn ($q) => $q->activos(),
                'subcategorias.campos',
                'campos',
            ])
            ->orderBy('orden')
            ->get();

        $mapCampos = fn ($campos) => $campos->map(fn ($c) => [
            'name'     => $c->name,
            'label'    => $c->label,
            'type'     => $c->tipo_campo,
            'required' => $c->requerido,
        ])->values()->all();

        $resultado = [];

        foreach ($categorias as $categoria) {
            $deptoNombre = $categoria->departamento?->nombre;
            if (!$deptoNombre) {
                continue;
            }

            $entry = ['label' => $categoria->label];

            if ($categoria->requiere_subcategoria) {
                $entry['sistemas'] = [];
                foreach ($categoria->subcategorias as $sub) {
                    $entry['sistemas'][(string) $sub->id] = [
                        'label'  => $sub->label,
                        'nota'   => $sub->nota,
                        'campos' => $mapCampos($sub->campos),
                    ];
                }
            } else {
                $entry['campos'] = $mapCampos($categoria->campos);
            }

            $resultado[$deptoNombre][(string) $categoria->id] = $entry;
        }

        return $resultado;
    }
}
