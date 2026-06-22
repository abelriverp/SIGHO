$data = $request->validate([
    'problema'    => 'required|string',
    'descripcion' => 'nullable|string',
    'revision'    => 'nullable|string',
    'diagnostico' => 'nullable|string',
    'solucion'    => 'nullable|string',
    'resultado'   => 'nullable|string',
]);