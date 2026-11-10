<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\NetworkDevice;
use App\Models\SegmentoRed;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Segmentos de red / VLANs y su mapa de IPs (Fase 4). */
class SegmentoRedController extends Controller
{
    private function validar(Request $request, ?SegmentoRed $s = null): array
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'vlan'        => 'nullable|integer|min:1|max:4094',
            'red'         => 'required|string|max:18',
            'gateway'     => 'nullable|ipv4',
            'dns'         => 'nullable|string|max:100',
            'dhcp_inicio' => 'nullable|ipv4|required_with:dhcp_fin',
            'dhcp_fin'    => 'nullable|ipv4|required_with:dhcp_inicio',
            'proposito'   => 'nullable|string|max:255',
            'notas'       => 'nullable|string|max:3000',
        ]);

        $red = SegmentoRed::normalizar($data['red']);
        if (! $red) {
            throw ValidationException::withMessages(['red' => 'Escribe la red en formato CIDR, por ejemplo 10.7.64.0/24 (prefijo entre /8 y /30).']);
        }
        $data['red'] = $red;

        $existe = SegmentoRed::where('red', $red)->when($s, fn ($q) => $q->whereKeyNot($s->id))->exists();
        if ($existe) {
            throw ValidationException::withMessages(['red' => 'Ya existe un segmento con esa red.']);
        }

        $tmp = new SegmentoRed(['red' => $red]);
        foreach (['gateway', 'dhcp_inicio', 'dhcp_fin'] as $campo) {
            if (! empty($data[$campo]) && ! $tmp->contiene($data[$campo])) {
                throw ValidationException::withMessages([$campo => "La IP {$data[$campo]} no pertenece a {$red}."]);
            }
        }
        if (! empty($data['dhcp_inicio']) && ip2long($data['dhcp_inicio']) > ip2long($data['dhcp_fin'])) {
            throw ValidationException::withMessages(['dhcp_fin' => 'El fin del rango DHCP debe ser mayor que el inicio.']);
        }

        return $data;
    }

    private function exigeEditor(): void
    {
        $u = auth()->user();
        if (! ($u->puedeResponderTickets() || $u->puedeVerTodos())) {
            abort(403, 'Solo técnicos, gerentes y administradores pueden editar segmentos.');
        }
    }

    public function index()
    {
        $segmentos = SegmentoRed::orderBy('vlan')->orderBy('red')->get();
        $ips = Asset::vigentes()->whereNotNull('ip_address')->pluck('ip_address');

        $uso = $segmentos->mapWithKeys(fn ($s) => [$s->id => $ips->filter(fn ($ip) => $s->contiene($ip))->count()]);

        return view('segmentos.index', compact('segmentos', 'uso'));
    }

    public function create()
    {
        $this->exigeEditor();
        return view('segmentos.form', ['segmento' => new SegmentoRed()]);
    }

    public function store(Request $request)
    {
        $this->exigeEditor();
        $s = SegmentoRed::create($this->validar($request));

        return redirect()->route('segmentos.show', $s)->with('success', 'Segmento creado.');
    }

    public function show(SegmentoRed $segmento)
    {
        $activos = Asset::vigentes()->whereNotNull('ip_address')
            ->with('ubicacion:id,tipo,codigo,nombre')
            ->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo', 'ip_address', 'mac_address', 'ubicacion_id', 'estado_monitoreo'])
            ->filter(fn ($a) => $segmento->contiene($a->ip_address));
        $porIp = $activos->groupBy('ip_address');

        $gestion = NetworkDevice::whereNotNull('ip_gestion')->get(['id', 'nombre', 'ip_gestion'])
            ->filter(fn ($d) => $segmento->contiene($d->ip_gestion))->keyBy('ip_gestion');

        $conflictos = $porIp->filter(fn ($g) => $g->count() > 1);
        $hosts = $segmento->hosts();

        return view('segmentos.show', [
            'segmento'   => $segmento,
            'hosts'      => $hosts,
            'porIp'      => $porIp,
            'gestion'    => $gestion,
            'conflictos' => $conflictos,
            'usadas'     => $porIp->count() + $gestion->keys()->diff($porIp->keys())->count(),
        ]);
    }

    public function edit(SegmentoRed $segmento)
    {
        $this->exigeEditor();
        return view('segmentos.form', compact('segmento'));
    }

    public function update(Request $request, SegmentoRed $segmento)
    {
        $this->exigeEditor();
        $segmento->update($this->validar($request, $segmento));

        return redirect()->route('segmentos.show', $segmento)->with('success', 'Segmento actualizado.');
    }

    public function destroy(SegmentoRed $segmento)
    {
        if (! auth()->user()->puedeVerTodos()) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar segmentos.');
        }
        $segmento->delete();

        return redirect()->route('segmentos.index')->with('success', 'Segmento eliminado.');
    }
}
