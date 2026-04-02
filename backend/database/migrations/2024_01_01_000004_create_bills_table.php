<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained();
            $table->foreignId('fee_rule_id')->constrained();
            $table->integer('year');
            $table->integer('month');
            $table->decimal('amount', 10, 2);              // 應繳金額
            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid');
            $table->date('due_date');                       // 繳費截止日
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'year', 'month']); // 每戶每月只有一張帳單
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
