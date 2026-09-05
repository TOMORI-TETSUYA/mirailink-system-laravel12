{{-- 契約登録画面のプラン選択（仕様 20.3）。価格は入力補助であり、保存時はサーバー側で再判定します。 --}}
@props(['plans'])

<section class="plan-selector" data-plan-selector>
    <div class="form-field @error('insurance_plan_id') has-error @enderror">
        <label class="form-label" for="insurance_plan_id">
            プラン <span class="required-mark">必須</span>
        </label>
        <select
            id="insurance_plan_id"
            name="insurance_plan_id"
            required
            data-plan-select
            aria-describedby="plan-price-output @error('insurance_plan_id') insurance_plan_id-error @enderror"
        >
            <option value="">選択してください</option>

            @foreach ($plans as $plan)
                <option
                    value="{{ $plan->id }}"
                    data-price="{{ $plan->resolved_price?->amount_yen }}"
                    data-cycle="{{ $plan->billing_cycle }}"
                    data-cycle-label="{{ $plan->billing_cycle_label }}"
                    data-price-id="{{ $plan->resolved_price?->id }}"
                    data-effective-from="{{ $plan->resolved_price?->effective_from?->format('Y年n月j日') }}"
                    @selected((string) old('insurance_plan_id') === (string) $plan->id)
                >
                    {{ $plan->insurer_name }}
                    {{ $plan->plan_name }}
                </option>
            @endforeach
        </select>
        @error('insurance_plan_id')
            <p class="field-error" id="insurance_plan_id-error">
                @foreach (explode("\n", $message) as $line)
                    {{ $line }}@if (! $loop->last)<br>@endif
                @endforeach
            </p>
        @enderror
    </div>

    <input
        type="hidden"
        name="insurance_plan_price_id"
        data-plan-price-id
    >

    <output class="selected-plan-price" id="plan-price-output" data-plan-price for="insurance_plan_id">
        プランを選択すると金額が表示されます。
    </output>
</section>
