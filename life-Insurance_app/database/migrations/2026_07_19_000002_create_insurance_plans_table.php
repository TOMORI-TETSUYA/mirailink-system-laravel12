<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_code', 32)->unique();
            $table->string('plan_name', 150);
            $table->string('plan_type', 100)->nullable();
            $table->string('insurer_name', 150)->nullable();
            $table->string('billing_cycle', 20);
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_plans');
    }
};
