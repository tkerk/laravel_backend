<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Multa;
use Illuminate\Support\Facades\Validator;

class MultaControlador extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|numeric|min:0',
            'razon' => 'required|string|max:255',
            'emitido_a_las' => 'required|date',
            'estatus' => 'required|in:pendiente,pagada',
            'id_huesped' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $multa = Multa::create([
            'cantidad' => $request->cantidad,
            'razon' => $request->razon,
            'emitido_a_las' => $request->emitido_a_las,
            'estatus' => $request->estatus,
            'id_huesped' => $request->id_huesped,
            'notificado_en' => now(),
            'visualizado' => false,
        ]);

        return response()->json(['ok' => true, 'multa' => $multa]);
    }

    public function nuevasmultas()
{
    $id = '12345'; // Puedes recibirlo por parámetro si lo deseas

    $multas = Multa::where('id_huesped', $id)
        ->orderBy('notificado_en', 'desc')
        ->get();

    if ($multas->isEmpty()) {
        return response()->json([]);
    }

    return response()->json($multas);
}
}