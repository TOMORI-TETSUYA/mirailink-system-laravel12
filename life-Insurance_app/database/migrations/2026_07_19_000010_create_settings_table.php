<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 保存期間などの運用設定値。固定年数をソースコードへ埋め込まないためのテーブルです（仕様 6.11）。
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('value', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
