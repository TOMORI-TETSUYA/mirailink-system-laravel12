<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 金額は浮動小数点を使わず円単位の整数で保存します。期間重複はサービス層（行ロック）で検査します。
        Schema::create('insurance_plan_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('insurance_plan_id')->constrained('insurance_plans')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_yen');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['insurance_plan_id', 'effective_from']);
            $table->index(['insurance_plan_id', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_plan_prices');
    }
};
