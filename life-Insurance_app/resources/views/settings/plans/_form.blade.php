{{-- プランマスタの編集フォーム項目（金額は価格履歴で管理するため含めません）。 --}}
<x-category-choice :value="$plan->category" />

<x-field name="plan_name" label="プラン名" :required="true">
    <input type="text" id="plan_name" name="plan_name" maxlength="150" value="{{ old('plan_name', $plan->plan_name) }}" required>
</x-field>

<x-field name="plan_type" label="プラン種類" help="医療、死亡、がん等。自由入力です。">
    <input type="text" id="plan_type" name="plan_type" maxlength="100" value="{{ old('plan_type', $plan->plan_type) }}">
</x-field>

<x-field name="insurer_name" label="保険会社名">
    <input type="text" id="insurer_name" name="insurer_name" maxlength="150" value="{{ old('insurer_name', $plan->insurer_name) }}">
</x-field>

<x-field name="billing_cycle" label="支払単位" :required="true">
    <select id="billing_cycle" name="billing_cycle" required>
        @foreach (App\Models\InsurancePlan::BILLING_CYCLES as $value => $label)
            <option value="{{ $value }}" @selected(old('billing_cycle', $plan->billing_cycle) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</x-field>

<x-field name="display_order" label="表示順">
    <input type="number" id="display_order" name="display_order" inputmode="numeric" min="0" max="9999" value="{{ old('display_order', $plan->display_order) }}">
</x-field>

<x-field name="status" label="状態" :required="true" help="有効化には適用中または将来の金額が1件以上必要です。">
    <select id="status" name="status" required>
        @foreach (['draft', 'active', 'inactive'] as $value)
            <option value="{{ $value }}" @selected(old('status', $plan->status) === $value)>{{ App\Models\InsurancePlan::STATUSES[$value] }}</option>
        @endforeach
    </select>
</x-field>

<x-field name="notes" label="備考" full help="社内向け説明。">
    <textarea id="notes" name="notes" maxlength="2000" rows="3">{{ old('notes', $plan->notes) }}</textarea>
</x-field>
