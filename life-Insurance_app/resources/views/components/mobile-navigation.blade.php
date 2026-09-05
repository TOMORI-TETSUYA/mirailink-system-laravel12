@props(['title' => ''])

<header class="mobile-header">
    <button
        type="button"
        class="menu-button"
        aria-controls="primary-navigation"
        aria-expanded="false"
        aria-label="メニューを開く"
        data-menu-button
    >
        <span class="menu-button__bar" aria-hidden="true"></span>
    </button>

    <p class="mobile-header__brand">MiraiLink</p>

    <p class="mobile-header__title">
        {{ $title }}
    </p>
</header>
