@if (session('status'))
    <div class="flash flash--success" role="status" data-flash>
        <span class="flash__icon" aria-hidden="true">✓</span>
        <p class="flash__text">{{ session('status') }}</p>
        <button type="button" class="flash__close" aria-label="通知を閉じる" data-flash-close>×</button>
    </div>
@endif

@if ($errors->has('status') && ! request()->routeIs('settings.plans.*'))
    <div class="flash flash--error" role="alert">
        <span class="flash__icon" aria-hidden="true">!</span>
        <p class="flash__text">{{ $errors->first('status') }}</p>
    </div>
@endif
