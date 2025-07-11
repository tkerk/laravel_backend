<?php

namespace App\Http\Controllers;

use App\Models\Multa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MultaControlador extends Controller
{
   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id_huesped' => 'required|exists:usuarios,id_huesped',
        'cantidad' => 'required|numeric',
        'razon' => 'required|string',
        'emitido_a_las' => 'required|date',
        'estatus' => 'required|in:pendiente,pagada',
        'notificado_en' => 'nullable|date',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    
    $request->merge([
        'notificado_en' => now()
    ]);

    $multa = Multa::create($request->all());
    return response()->json($multa, 201);
}

public function multaRecientePorHuesped($id)
{
    $multa = Multa::where('id_huesped', $id)->orderBy('notificado_en', 'desc')->first();
    return response()->json($multa);
}

public function multasPorHuesped($id)
{
    $multas = Multa::where('id_huesped', $id)
        ->orderBy('notificado_en', 'desc')
        ->get();
    return response()->json($multas);
}

public function marcarComoVisualizada($id)
{
    $multa = Multa::findOrFail($id);
    $multa->visualizado = true;
    $multa->save();
    return response()->json(['success' => true]);
}
}