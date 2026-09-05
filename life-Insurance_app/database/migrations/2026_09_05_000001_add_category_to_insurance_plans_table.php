<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * プランへ保険分類（生命保険／損害保険／法人様向け保険）を追加します。
 *
 * 既存プランは生命保険のみを想定して登録されているため、既定値を life として
 * 移行します。分類は一覧の絞り込みに使うためインデックスを張ります。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_plans', function (Blueprint $table): void {
            $table->string('category', 20)
                ->default('life')
                ->after('plan_type')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('insurance_plans', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
