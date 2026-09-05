{{--
    保険分類の選択欄。
    3 分類それぞれの説明を併記し、担当者が分類を取り違えないようにします。
    ラジオグループのため x-field ではなく fieldset / legend で組みます。
--}}
@props([
    'name' => 'category',
    'value' => null,
])

@php
    $selected = old($name, $value ?? App\Models\InsurancePlan::CATEGORY_LIFE);
@endphp

<fieldset class="form-field form-field--full category-choice{{ $errors->has($name) ? ' has-error' : '' }}">
    <legend class="form-label">
        保険分類
        <span class="required-mark">必須</span>
    </legend>

    <div class="category-choice__options">
        @foreach (App\Models\InsurancePlan::CATEGORIES as $key => $label)
            <label class="category-option" for="{{ $name }}_{{ $key }}">
                <input
                    type="radio"
                    id="{{ $name }}_{{ $key }}"
                    name="{{ $name }}"
                    value="{{ $key }}"
                    @checked($selected === $key)
                    required
                >
                <span class="category-option__body">
                    <span class="category-option__name">{{ $label }}</span>
                    <span class="category-option__description">
                        {{ App\Models\InsurancePlan::CATEGORY_DESCRIPTIONS[$key] }}
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
</fieldset>
