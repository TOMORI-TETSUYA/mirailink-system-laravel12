"use strict";

/*
 * MiraiLink - responsive-navigation.js（仕様 22.1）
 * モバイルメニューの開閉状態だけをメモリ上で管理します。
 * 顧客情報やログイン状態をブラウザ保存領域へ書き込みません。
 */
document.addEventListener("DOMContentLoaded", () => {
    const menuButton = document.querySelector("[data-menu-button]");
    const sidebar = document.querySelector("[data-sidebar]");
    const overlay = document.querySelector("[data-sidebar-overlay]");

    if (!menuButton || !sidebar || !overlay) {
        return;
    }

    let lastFocusedElement = null;

    const focusableSelector = [
        "a[href]",
        "button:not([disabled])",
        "input:not([disabled])",
        "select:not([disabled])",
        "textarea:not([disabled])",
        "[tabindex]:not([tabindex='-1'])",
    ].join(",");

    const openMenu = () => {
        lastFocusedElement = document.activeElement;

        sidebar.classList.add("is-open");
        overlay.hidden = false;
        menuButton.setAttribute("aria-expanded", "true");
        menuButton.setAttribute("aria-label", "メニューを閉じる");

        const firstFocusable = sidebar.querySelector(focusableSelector);
        firstFocusable?.focus();
    };

    const closeMenu = () => {
        sidebar.classList.remove("is-open");
        overlay.hidden = true;
        menuButton.setAttribute("aria-expanded", "false");
        menuButton.setAttribute("aria-label", "メニューを開く");

        if (lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    };

    menuButton.addEventListener("click", () => {
        const isOpen = menuButton.getAttribute("aria-expanded") === "true";

        if (isOpen) {
            closeMenu();
            return;
        }

        openMenu();
    });

    overlay.addEventListener("click", closeMenu);

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && sidebar.classList.contains("is-open")) {
            closeMenu();
        }
    });

    const desktopQuery = window.matchMedia("(min-width: 1024px)");

    desktopQuery.addEventListener("change", (event) => {
        if (event.matches) {
            closeMenu();
        }
    });
});
