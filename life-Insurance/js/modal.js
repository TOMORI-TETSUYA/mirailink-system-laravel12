"use strict";

/*
 * MiraiLink - modal.js
 * カスタムモーダル（ブラウザ標準の alert/confirm/prompt は使用しません）。
 *
 * - [data-modal-open="id"] ボタンで対象モーダルを開きます。
 * - [data-modal-close] / 背景クリック / Escape で閉じ、開いたボタンへフォーカスを戻します。
 * - Tab キーのフォーカスをモーダル内に閉じ込めます。
 * - form[data-confirm] の送信を捕捉し、確認モーダルで承認された場合だけ送信します。
 *   JavaScript が無効な場合はフォームがそのまま送信されます。
 * - 入力エラー（.has-error）を含むモーダルは、再表示時に自動で開きます。
 */
document.addEventListener("DOMContentLoaded", () => {
    const focusableSelector = [
        "a[href]",
        "button:not([disabled])",
        "input:not([disabled]):not([type='hidden'])",
        "select:not([disabled])",
        "textarea:not([disabled])",
        "[tabindex]:not([tabindex='-1'])",
    ].join(",");

    let activeModal = null;
    let lastOpener = null;

    const focusableElements = (modal) =>
        [...modal.querySelectorAll(focusableSelector)].filter(
            (element) => element.offsetParent !== null || element === document.activeElement
        );

    const openModal = (modal, opener = null) => {
        if (!modal || activeModal === modal) {
            return;
        }

        if (activeModal) {
            closeModal(false);
        }

        lastOpener = opener instanceof HTMLElement ? opener : document.activeElement;
        activeModal = modal;

        modal.hidden = false;
        document.body.classList.add("has-open-modal");
        opener?.setAttribute?.("aria-expanded", "true");

        const first = focusableElements(modal).find(
            (element) => !element.matches("[data-modal-close]")
        ) ?? focusableElements(modal)[0];

        (first ?? modal.querySelector("[data-modal-panel]"))?.focus();
    };

    const closeModal = (restoreFocus = true) => {
        if (!activeModal) {
            return;
        }

        const modal = activeModal;
        activeModal = null;

        modal.hidden = true;
        document.body.classList.remove("has-open-modal");
        lastOpener?.setAttribute?.("aria-expanded", "false");

        if (restoreFocus && lastOpener instanceof HTMLElement) {
            lastOpener.focus();
        }

        lastOpener = null;
    };

    document.querySelectorAll("[data-modal-open]").forEach((button) => {
        const modal = document.getElementById(button.dataset.modalOpen);

        if (!modal) {
            return;
        }

        button.setAttribute("aria-haspopup", "dialog");
        button.setAttribute("aria-expanded", "false");

        button.addEventListener("click", () => openModal(modal, button));
    });

    document.addEventListener("click", (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        if (target.closest("[data-modal-close]") && activeModal?.contains(target)) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (!activeModal) {
            return;
        }

        if (event.key === "Escape") {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key !== "Tab") {
            return;
        }

        const elements = focusableElements(activeModal);

        if (elements.length === 0) {
            event.preventDefault();
            return;
        }

        const first = elements[0];
        const last = elements[elements.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    /* 確認モーダル: form[data-confirm] */
    const confirmModal = document.getElementById("confirm-modal");
    const confirmMessage = confirmModal?.querySelector("[data-confirm-message]");
    const confirmAccept = confirmModal?.querySelector("[data-confirm-accept]");
    let pendingForm = null;

    if (confirmModal && confirmMessage && confirmAccept) {
        document.querySelectorAll("form[data-confirm]").forEach((form) => {
            form.addEventListener("submit", (event) => {
                if (form.dataset.confirmed === "true") {
                    return;
                }

                event.preventDefault();
                pendingForm = form;

                confirmMessage.textContent =
                    form.dataset.confirmMessage || "この操作を実行しますか。";
                confirmAccept.textContent = form.dataset.confirmLabel || "実行する";

                const opener = event.submitter instanceof HTMLElement
                    ? event.submitter
                    : form.querySelector("button[type='submit']");

                openModal(confirmModal, opener);
            });
        });

        confirmAccept.addEventListener("click", () => {
            if (!pendingForm) {
                closeModal();
                return;
            }

            const form = pendingForm;
            pendingForm = null;
            form.dataset.confirmed = "true";
            confirmAccept.disabled = true;
            closeModal(false);
            form.requestSubmit();
        });
    }

    /* バリデーションエラーを含むモーダルは自動的に再表示します。 */
    const erroredModal = [...document.querySelectorAll("[data-modal]")].find(
        (modal) => modal.id !== "confirm-modal" && modal.querySelector(".has-error")
    );

    if (erroredModal) {
        const opener = document.querySelector(`[data-modal-open="${erroredModal.id}"]`);
        openModal(erroredModal, opener);
    }
});
