<?php

namespace App\Http\Controllers;

use App\Models\Peripheral;
use App\Models\Asset;
use Illuminate\Http\Request;

class PeripheralController extends Controller
{
    public function index()
    {
        $peripherals = Peripheral::with('asset')->latest()->get();
        return view('peripherals.index', compact('peripherals'));
    }

    public function create()
    {
        $assets = Asset::orderBy('nomenclatura')->get();
        return view('peripherals.create', compact('assets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id'     => 'nullable|exists:assets,id',
            'tipo'         => 'required|string',
            'marca'        => 'nullable|string',
            'modelo'       => 'nullable|string',
            'numero_serie' => 'nullable|string',
            'status'       => 'required|string',
        ]);

        Peripheral::create($data);

        return redirect()->route('peripherals.index');
    }

    public function edit($id)
    {
        $peripheral = Peripheral::findOrFail($id);
        $assets = Asset::orderBy('nomenclatura')->get();
        return view('peripherals.edit', compact('peripheral', 'assets'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'asset_id'     => 'nullable|exists:assets,id',
            'tipo'         => 'required|string',
            'marca'        => 'nullable|string',
            'modelo'       => 'nullable|string',
            'numero_serie' => 'nullable|string',
            'status'       => 'required|string',
        ]);

        Peripheral::findOrFail($id)->update($data);

        return redirect()->route('peripherals.index');
    }

    public function destroy($id)
    {
        $peripheral = Peripheral::findOrFail($id);

        if (! auth()->user()->can('delete', $peripheral)) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar periféricos.');
        }

        $peripheral->delete();
        return redirect()->route('peripherals.index');
    }
}