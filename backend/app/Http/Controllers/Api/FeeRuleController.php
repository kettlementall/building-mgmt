<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeRule;
use Illuminate\Http\Request;

class FeeRuleController extends Controller
{
    public function index()
    {
        return response()->json(FeeRule::orderBy('effective_from', 'desc')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'           => 'required|in:fixed,per_area',
            'amount'         => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to'   => 'nullable|date|after:effective_from',
            'note'           => 'nullable|string',
        ]);

        $rule = FeeRule::create($data);

        return response()->json($rule, 201);
    }

    public function show(FeeRule $feeRule)
    {
        return response()->json($feeRule);
    }

    public function update(Request $request, FeeRule $feeRule)
    {
        $data = $request->validate([
            'type'           => 'sometimes|in:fixed,per_area',
            'amount'         => 'sometimes|numeric|min:0',
            'effective_from' => 'sometimes|date',
            'effective_to'   => 'nullable|date',
            'note'           => 'nullable|string',
        ]);

        $feeRule->update($data);

        return response()->json($feeRule);
    }

    public function destroy(FeeRule $feeRule)
    {
        $feeRule->delete();

        return response()->json(null, 204);
    }
}
