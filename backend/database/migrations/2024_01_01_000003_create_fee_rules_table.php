<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['fixed', 'per_area']); // fixed=固定金額, per_area=按坪數
            $table->decimal('amount', 10, 2);            // 金額 or 每坪單價
            $table->date('effective_from');              // 生效日期
            $table->date('effective_to')->nullable();    // 結束日期（null=持續有效）
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rules');
    }
};
