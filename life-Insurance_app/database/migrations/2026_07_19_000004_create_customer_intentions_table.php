<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_intentions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('initial_intention');
            $table->text('final_intention')->nullable();
            $table->text('protection_purpose')->nullable();
            $table->text('budget')->nullable();
            $table->text('desired_period')->nullable();
            $table->text('proposed_reason')->nullable();
            $table->text('differences')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirmation_method', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_intentions');
    }
};
