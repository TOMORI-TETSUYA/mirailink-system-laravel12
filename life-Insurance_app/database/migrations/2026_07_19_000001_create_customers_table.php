<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 暗号化対象列は TEXT 型（暗号文は元データより長くなるため短い VARCHAR に固定しない）。
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_code', 32)->unique();
            $table->text('name');
            $table->text('name_kana')->nullable();
            $table->text('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->text('phone')->nullable();
            $table->char('phone_hmac', 64)->nullable()->index();
            $table->text('email')->nullable();
            $table->char('email_hmac', 64)->nullable()->index();
            $table->text('occupation')->nullable();
            $table->text('family')->nullable();
            $table->longText('health_information')->nullable();
            $table->foreignId('assigned_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('lead')->index();
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
