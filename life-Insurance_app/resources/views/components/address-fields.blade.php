{{--
    住所の入力欄（郵便番号・都道府県・市区町村・住所1・住所2・建物名）。

    レイアウト方針:
    フォーム全体（.form-grid）は 2 カラムのため、住所を通常の項目として並べると
    他の項目と交互に配置されて関係が読み取れません。全幅の 1 ブロックとして囲み、
    この中で日本の住所の記入順に並べます。

    住所は機微情報のため、各列とも暗号化して保存します（仕様 6.8）。
--}}
@props([
    'customer' => null,
])

<fieldset class="form-field--full address-block">
    <legend class="address-block__legend">住所</legend>

    <div class="address-block__grid">
        <x-field name="postal_code" label="郵便番号" class="address-block__postal" help="ハイフンは省略できます。">
            <input
                type="text"
                id="postal_code"
                name="postal_code"
                value="{{ old('postal_code', $customer?->postal_code) }}"
                maxlength="8"
                inputmode="numeric"
                placeholder="123-4567"
                autocomplete="off"
            >
        </x-field>

        {{-- 都道府県と市区町村は続けて記入するため、同じ行へ横並びにします。 --}}
        <div class="address-block__row">
            <x-field name="prefecture" label="都道府県" class="address-block__prefecture">
                {{-- 枠幅を漢字5文字に収めるため、未選択時の表示は短くします。 --}}
                <select id="prefecture" name="prefecture">
                    <option value="">選択</option>
                    @foreach (App\Models\Customer::PREFECTURES as $prefecture)
                        <option value="{{ $prefecture }}" @selected(old('prefecture', $customer?->prefecture) === $prefecture)>
                            {{ $prefecture }}
                        </option>
                    @endforeach
                </select>
            </x-field>

            <x-field name="city" label="市区町村" class="address-block__city">
                <input
                    type="text"
                    id="city"
                    name="city"
                    value="{{ old('city', $customer?->city) }}"
                    maxlength="100"
                    autocomplete="off"
                >
            </x-field>
        </div>

        <x-field name="address_line1" label="住所1" class="address-block__line" help="町名・丁目・番地。">
            <input
                type="text"
                id="address_line1"
                name="address_line1"
                value="{{ old('address_line1', $customer?->address_line1) }}"
                maxlength="200"
                autocomplete="off"
            >
        </x-field>

        <x-field name="address_line2" label="住所2" class="address-block__line" help="号・区画など。不要な場合は空欄にします。">
            <input
                type="text"
                id="address_line2"
                name="address_line2"
                value="{{ old('address_line2', $customer?->address_line2) }}"
                maxlength="200"
                autocomplete="off"
            >
        </x-field>

        <x-field name="building" label="建物名" class="address-block__line" help="建物名・部屋番号。">
            <input
                type="text"
                id="building"
                name="building"
                value="{{ old('building', $customer?->building) }}"
                maxlength="100"
                autocomplete="off"
            >
        </x-field>
    </div>
</fieldset>
