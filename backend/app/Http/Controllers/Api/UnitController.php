<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::with('activeResident')->orderBy('floor')->orderBy('number')->get();

        return response()->json($units);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'floor'  => 'required|string',
            'number' => 'required|string',
            'area'   => 'required|numeric|min:0',
            'status' => 'in:occupied,vacant',
            'note'   => 'nullable|string',
        ]);

        $unit = Unit::create($data);

        return response()->json($unit, 201);
    }

    public function show(Unit $unit)
    {
        return response()->json($unit->load('activeResident'));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'floor'  => 'sometimes|string',
            'number' => 'sometimes|string',
            'area'   => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:occupied,vacant',
            'note'   => 'nullable|string',
        ]);

        $unit->update($data);

        return response()->json($unit);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json(null, 204);
    }
}
