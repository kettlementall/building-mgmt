<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\Unit;
use Illuminate\Http\Request;

class ResidentController extends Controller
{
    public function index(Request $request)
    {
        $residents = Resident::with('unit')
            ->when($request->is_active, fn($q) => $q->where('is_active', true))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($residents);
    }

    public function byUnit(Unit $unit)
    {
        return response()->json($unit->residents()->orderBy('move_in_date', 'desc')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_id'        => 'required|exists:units,id',
            'name'           => 'required|string',
            'phone'          => 'nullable|string',
            'email'          => 'nullable|email',
            'move_in_date'   => 'required|date',
            'move_out_date'  => 'nullable|date|after:move_in_date',
            'type'           => 'in:owner,tenant',
            'note'           => 'nullable|string',
        ]);

        $resident = Resident::create($data);

        // 設定為入住，更新戶別狀態
        Unit::find($data['unit_id'])->update(['status' => 'occupied']);

        return response()->json($resident->load('unit'), 201);
    }

    public function show(Resident $resident)
    {
        return response()->json($resident->load('unit'));
    }

    public function update(Request $request, Resident $resident)
    {
        $data = $request->validate([
            'name'           => 'sometimes|string',
            'phone'          => 'nullable|string',
            'email'          => 'nullable|email',
            'move_in_date'   => 'sometimes|date',
            'move_out_date'  => 'nullable|date',
            'type'           => 'sometimes|in:owner,tenant',
            'is_active'      => 'sometimes|boolean',
            'note'           => 'nullable|string',
        ]);

        $resident->update($data);

        // 若設為退租，且該戶無其他現住住戶，更新戶別狀態
        if (isset($data['is_active']) && !$data['is_active']) {
            $hasActive = $resident->unit->residents()->where('is_active', true)->exists();
            if (!$hasActive) {
                $resident->unit->update(['status' => 'vacant']);
            }
        }

        return response()->json($resident);
    }

    public function destroy(Resident $resident)
    {
        $resident->delete();

        return response()->json(null, 204);
    }
}
