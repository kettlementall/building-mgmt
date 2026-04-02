<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('floor');           // 樓層
            $table->string('number');          // 室號
            $table->decimal('area', 8, 2);     // 坪數
            $table->enum('status', ['occupied', 'vacant'])->default('vacant'); // 狀態
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['floor', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
