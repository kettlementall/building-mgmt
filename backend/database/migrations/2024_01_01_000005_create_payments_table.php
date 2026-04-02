<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'transfer'])->default('cash'); // 現金 / 匯款
            $table->date('paid_at');
            $table->string('reference')->nullable();  // 匯款單號或備註
            $table->foreignId('recorded_by')->constrained('users'); // 登錄收款的管理員
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
