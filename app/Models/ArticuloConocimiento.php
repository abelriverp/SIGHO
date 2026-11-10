<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Base de conocimiento (Fase 2, nov 2026). */
class ArticuloConocimiento extends Model
{
    use BelongsToHotel;

    protected $table = 'articulos_conocimiento';

    protected $fillable = [
        'hotel_code', 'departamento', 'categoria', 'titulo', 'sintoma', 'solucion',
        'etiquetas', 'publicado', 'autor_id', 'ticket_origen_id', 'vistas', 'usos',
    ];

    protected $casts = ['publicado' => 'boolean'];

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function ticketOrigen()
    {
        return $this->belongsTo(Ticket::class, 'ticket_origen_id');
    }

    public function scopePublicados(Builder $q): Builder
    {
        return $q->where('publicado', true);
    }

    public function scopeBuscar(Builder $q, ?string $texto): Builder
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $q;
        }
        foreach (preg_split('/\s+/', $texto) as $palabra) {
            $like = '%' . $palabra . '%';
            $q->where(fn ($w) => $w->where('titulo', 'like', $like)
                ->orWhere('sintoma', 'like', $like)
                ->orWhere('solucion', 'like', $like)
                ->orWhere('etiquetas', 'like', $like)
                ->orWhere('categoria', 'like', $like));
        }
        return $q;
    }

    /** Sugeridos para un ticket: misma categoria primero, luego mismo departamento. */
    public static function sugeridosPara(Ticket $ticket, int $limite = 5)
    {
        $categoria = $ticket->campos_extra['categoria_label'] ?? null;

        return static::publicados()
            ->where(fn ($q) => $q->where('departamento', $ticket->departamento_destino)->orWhereNull('departamento'))
            ->orderByRaw('CASE WHEN categoria = ? THEN 0 ELSE 1 END', [$categoria ?? ''])
            ->orderByDesc('usos')
            ->orderByDesc('vistas')
            ->limit($limite)
            ->get(['id', 'titulo', 'categoria', 'usos']);
    }
}
