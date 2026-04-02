<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::with(['category', 'attachments', 'recorder'])
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->year,  fn($q) => $q->whereYear('expense_date', $request->year))
            ->when($request->month, fn($q) => $q->whereMonth('expense_date', $request->month))
            ->orderBy('expense_date', 'desc')
            ->paginate(20);

        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'  => 'required|exists:expense_categories,id',
            'title'        => 'required|string',
            'amount'       => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'vendor'       => 'nullable|string',
            'description'  => 'nullable|string',
        ]);

        $data['recorded_by'] = $request->user()->id;

        $expense = Expense::create($data);

        return response()->json($expense->load(['category', 'recorder']), 201);
    }

    public function show(Expense $expense)
    {
        return response()->json($expense->load(['category', 'attachments', 'recorder']));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'category_id'  => 'sometimes|exists:expense_categories,id',
            'title'        => 'sometimes|string',
            'amount'       => 'sometimes|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'vendor'       => 'nullable|string',
            'description'  => 'nullable|string',
        ]);

        $expense->update($data);

        return response()->json($expense->load(['category', 'attachments']));
    }

    public function destroy(Expense $expense)
    {
        // 刪除附件實體檔案
        foreach ($expense->attachments as $attachment) {
            Storage::delete($attachment->path);
        }

        $expense->delete();

        return response()->json(null, 204);
    }

    public function uploadAttachment(Request $request, Expense $expense)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 最大 5MB
        ]);

        $file = $request->file('file');
        $path = $file->store("expenses/{$expense->id}", 'public');

        $attachment = $expense->attachments()->create([
            'filename'  => $file->getClientOriginalName(),
            'path'      => $path,
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json($attachment, 201);
    }

    public function deleteAttachment(Expense $expense, ExpenseAttachment $attachment)
    {
        abort_if($attachment->expense_id !== $expense->id, 404);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(null, 204);
    }
}
