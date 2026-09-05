{{-- 顧客登録・更新の共通フォーム。$customer が null の場合は新規登録です。 --}}
@php
    $isEdit = $customer !== null;
@endphp

<x-field name="name" label="氏名" :required="true">
    <input type="text" id="name" name="name" value="{{ old('name', $customer?->name) }}" maxlength="100" required autocomplete="off">
</x-field>

<x-field name="name_kana" label="氏名カナ">
    <input type="text" id="name_kana" name="name_kana" value="{{ old('name_kana', $customer?->name_kana) }}" maxlength="100" autocomplete="off">
</x-field>

<x-field name="birth_date" label="生年月日">
    <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $customer?->birth_date) }}" max="{{ now()->toDateString() }}">
</x-field>

<x-field name="status" label="顧客状態" :required="true">
    <select id="status" name="status" required>
        @foreach (App\Models\Customer::STATUSES as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $customer?->status ?? 'lead') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</x-field>

<x-field name="address" label="住所" full>
    <input type="text" id="address" name="address" value="{{ old('address', $customer?->address) }}" maxlength="300" autocomplete="off">
</x-field>

<x-field name="phone" label="電話番号" help="数字とハイフンで入力します。完全一致検索のみ対応します。">
    <input type="tel" id="phone" name="phone" value="{{ old('phone', $customer?->phone) }}" maxlength="20" inputmode="tel" autocomplete="off">
</x-field>

<x-field name="email" label="メールアドレス">
    <input type="email" id="email" name="email" value="{{ old('email', $customer?->email) }}" maxlength="254" inputmode="email" autocomplete="off">
</x-field>

<x-field name="occupation" label="勤務先・職業">
    <input type="text" id="occupation" name="occupation" value="{{ old('occupation', $customer?->occupation) }}" maxlength="150" autocomplete="off">
</x-field>

<x-field name="assigned_user_id" label="担当者" :required="true">
    <select id="assigned_user_id" name="assigned_user_id" required>
        <option value="">選択してください</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $customer?->assigned_user_id ?? auth()->id()) === (string) $user->id)>{{ $user->display_name }}</option>
        @endforeach
    </select>
</x-field>

<x-field name="family" label="家族構成" full help="保険募集に必要な範囲に限定して記入します。">
    <textarea id="family" name="family" maxlength="500" rows="3">{{ old('family', $customer?->family) }}</textarea>
</x-field>

@if (! $isEdit || $canViewHealth)
    <x-field name="health_information" label="健康・病歴情報（要配慮個人情報）" full help="閲覧は権限を持つ担当者に限定されます。不要な情報は記入しないでください。">
        <textarea id="health_information" name="health_information" maxlength="2000" rows="4">{{ old('health_information', $isEdit ? $customer->health_information : null) }}</textarea>
    </x-field>
@endif

@unless ($isEdit)
    <div class="form-field form-field--full consent-field @error('consented') has-error @enderror">
        <label class="checkbox-label" for="consented">
            <input type="checkbox" id="consented" name="consented" value="1" @checked(old('consented')) required>
            <span>利用目的（保険募集・契約管理・顧客対応）を説明し、本人の同意を得ました。<span class="required-mark">必須</span></span>
        </label>
        @error('consented')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>
@endunless
