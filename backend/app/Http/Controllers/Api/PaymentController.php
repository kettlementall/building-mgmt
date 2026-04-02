<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with(['bill.unit', 'recorder'])
            ->orderBy('paid_at', 'desc')
            ->paginate(20);

        return response()->json($payments);
    }

    public function byBill(Bill $bill)
    {
        return response()->json($bill->payment?->load('recorder'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bill_id'   => 'required|exists:bills,id',
            'amount'    => 'required|numeric|min:0',
            'method'    => 'required|in:cash,transfer',
            'paid_at'   => 'required|date',
            'reference' => 'nullable|string',
            'note'      => 'nullable|string',
        ]);

        $bill = Bill::findOrFail($data['bill_id']);

        if ($bill->isPaid()) {
            return response()->json(['message' => '此帳單已繳費'], 422);
        }

        $data['recorded_by'] = $request->user()->id;

        $payment = Payment::create($data);

        return response()->json($payment->load(['bill.unit', 'recorder']), 201);
    }

    public function show(Payment $payment)
    {
        return response()->json($payment->load(['bill.unit', 'recorder']));
    }

    public function destroy(Payment $payment)
    {
        $payment->delete(); // Model 的 booted 會自動將帳單狀態改回 unpaid

        return response()->json(null, 204);
    }
}
