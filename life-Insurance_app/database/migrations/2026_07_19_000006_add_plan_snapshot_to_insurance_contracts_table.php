<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 契約時スナップショット（仕様 14.5）。プラン名や価格を後から変更しても既存契約の表示は変わりません。
        Schema::table('insurance_contracts', function (Blueprint $table): void {
            $table->foreignId('insurance_plan_id')->nullable()->after('created_by')
                ->constrained('insurance_plans')->restrictOnDelete();
            $table->foreignId('insurance_plan_price_id')->nullable()->after('insurance_plan_id')
                ->constrained('insurance_plan_prices')->restrictOnDelete();
            $table->text('insurer_name_snapshot')->nullable()->after('insurance_plan_price_id');
            $table->text('plan_name_snapshot')->nullable()->after('insurer_name_snapshot');
            $table->text('plan_type_snapshot')->nullable()->after('plan_name_snapshot');
            $table->text('premium_amount_snapshot')->nullable()->after('plan_type_snapshot');
            $table->string('billing_cycle_snapshot', 20)->default('monthly')->after('premium_amount_snapshot');
            $table->boolean('is_price_overridden')->default(false)->after('billing_cycle_snapshot');
            $table->text('price_override_reason')->nullable()->after('is_price_overridden');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('insurance_plan_price_id');
            $table->dropConstrainedForeignId('insurance_plan_id');
            $table->dropColumn([
                'insurer_name_snapshot',
                'plan_name_snapshot',
                'plan_type_snapshot',
                'premium_amount_snapshot',
                'billing_cycle_snapshot',
                'is_price_overridden',
                'price_override_reason',
            ]);
        });
    }
};
