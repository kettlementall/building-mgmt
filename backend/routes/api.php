<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FeeRuleController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;

// 登入
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // 登出 / 當前使用者
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // 戶別管理
    Route::apiResource('units', UnitController::class);

    // 住戶管理
    Route::apiResource('residents', ResidentController::class);
    Route::get('units/{unit}/residents', [ResidentController::class, 'byUnit']);

    // 管理費規則
    Route::apiResource('fee-rules', FeeRuleController::class);

    // 帳單管理
    Route::apiResource('bills', BillController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::post('bills/generate', [BillController::class, 'generate']);         // 手動批量產帳單
    Route::get('units/{unit}/bills', [BillController::class, 'byUnit']);        // 查某戶帳單
    Route::post('bills/{bill}/send-notice', [BillController::class, 'sendNotice']); // 寄繳費通知

    // 繳費管理
    Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::get('bills/{bill}/payment', [PaymentController::class, 'byBill']);

    // 支出分類
    Route::apiResource('expense-categories', ExpenseCategoryController::class);

    // 支出明細
    Route::apiResource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/attachments', [ExpenseController::class, 'uploadAttachment']);
    Route::delete('expenses/{expense}/attachments/{attachment}', [ExpenseController::class, 'deleteAttachment']);

    // 報表
    Route::prefix('reports')->group(function () {
        Route::get('income',  [ReportController::class, 'income']);   // 收入統計
        Route::get('expense', [ReportController::class, 'expense']);  // 支出統計
        Route::get('balance', [ReportController::class, 'balance']);  // 結餘報表
        Route::get('overdue', [ReportController::class, 'overdue']);  // 欠繳清單
    });
});
