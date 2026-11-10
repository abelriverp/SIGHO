<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PermisosActivos;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\BajaActivo;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Flujo de baja de equipo (Fase 3): solicitud con motivo y dictamen ->
 * aprobacion o rechazo de gerente/admin -> activo en "Baja" + acta en PDF.
 */
class BajaActivoController extends Controller
{
    use PermisosActivos;

    public function index(Request $request)
    {
        $estado = in_array($request->query('estado'), ['pendiente', 'aprobada', 'rechazada'], true) ? $request->query('estado') : 'pendiente';

        $bajas = BajaActivo::with(['asset:id,nomenclatura,nombre,tipo_equipo,marca,modelo,numero_serie', 'solicitante:id,name', 'revisor:id,name'])
            ->where('estado', $estado)->latest()->paginate(30)->withQueryString();

        $conteo = BajaActivo::selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');

        return view('bajas.index', compact('bajas', 'estado', 'conteo'));
    }

    public function store(Request $request, $assetId)
    {
        $this->exigeEditorActivos();
        $asset = Asset::findOrFail($assetId);

        if ($asset->status === 'Baja') {
            return back()->with('error', 'Este activo ya está dado de baja.');
        }
        if ($asset->bajas()->where('estado', 'pendiente')->exists()) {
            return back()->with('error', 'Ya hay una solicitud de baja pendiente para este activo.');
        }

        $data = $request->validate([
            'motivo'   => 'required|string|min:10|max:3000',
            'destino'  => ['nullable', Rule::in(array_keys(BajaActivo::DESTINOS))],
            'dictamen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], ['motivo.min' => 'Describe el motivo de la baja (mínimo 10 caracteres).']);

        $baja = BajaActivo::create([
            'hotel_code'     => $asset->hotel_code,
            'asset_id'       => $asset->id,
            'solicitado_por' => auth()->id(),
            'motivo'         => $data['motivo'],
            'destino'        => $data['destino'] ?? null,
            'dictamen_path'  => $request->hasFile('dictamen') ? $request->file('dictamen')->store('bajas', 'public') : null,
            'estado'         => 'pendiente',
        ]);

        AuditLog::registrar('baja.solicitada', "Solicitó la baja de \"{$asset->nomenclatura}\".", $baja);

        return redirect()->route('assets.show', $asset->id)->with('success', 'Solicitud de baja enviada. Un gerente o administrador debe aprobarla.');
    }

    public function aprobar(Request $request, BajaActivo $baja)
    {
        $this->exigeSupervisor('Solo un Administrador o Gerente puede aprobar bajas.');
        if ($baja->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud ya fue revisada.');
        }
        $data = $request->validate(['comentario_revision' => 'nullable|string|max:2000']);

        DB::transaction(function () use ($baja, $data) {
            $asset = $baja->asset;
            $baja->update([
                'estado'              => 'aprobada',
                'revisado_por'        => auth()->id(),
                'revisado_at'         => now(),
                'comentario_revision' => $data['comentario_revision'] ?? null,
                'estado_anterior'     => $asset->status,
            ]);
            $asset->update([
                'status'      => 'Baja',
                'fecha_baja'  => now()->toDateString(),
                'motivo_baja' => $baja->motivo,
                'asignado_a'  => null,
            ]);
        });

        AuditLog::registrar('baja.aprobada', "Aprobó la baja de \"{$baja->asset->nomenclatura}\".", $baja);

        return back()->with('success', 'Baja aprobada. El activo quedó en estado Baja; ya puedes imprimir el acta.');
    }

    public function rechazar(Request $request, BajaActivo $baja)
    {
        $this->exigeSupervisor('Solo un Administrador o Gerente puede rechazar bajas.');
        if ($baja->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud ya fue revisada.');
        }
        $data = $request->validate(['comentario_revision' => 'required|string|min:5|max:2000'], [
            'comentario_revision.required' => 'Explica por qué se rechaza la baja.',
        ]);

        $baja->update([
            'estado'              => 'rechazada',
            'revisado_por'        => auth()->id(),
            'revisado_at'         => now(),
            'comentario_revision' => $data['comentario_revision'],
        ]);
        AuditLog::registrar('baja.rechazada', "Rechazó la baja de \"{$baja->asset->nomenclatura}\".", $baja);

        return back()->with('success', 'Solicitud de baja rechazada.');
    }

    public function acta(BajaActivo $baja)
    {
        if ($baja->estado !== 'aprobada') {
            abort(404);
        }
        $baja->load(['asset.componentes', 'asset.ubicacion', 'solicitante', 'revisor']);

        return Pdf::loadView('bajas.acta_pdf', ['baja' => $baja])
            ->setPaper('letter')
            ->download('Acta_Baja_' . $baja->asset->nomenclatura . '.pdf');
    }
}
