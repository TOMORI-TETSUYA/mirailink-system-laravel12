<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 監査ログは追記専用。updated_at を持たず、アプリケーションからの更新・削除処理を提供しません。
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('request_id');
            $table->string('action', 100)->index();
            $table->string('target_type', 150)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('changed_fields')->nullable();
            $table->text('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('succeeded')->default(true);
            $table->timestamp('created_at')->index();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
