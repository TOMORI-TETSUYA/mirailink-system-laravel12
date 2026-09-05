"use strict";

/*
 * MiraiLink - app.js（仕様 22）
 * ログイン画面の入力補助と、共通のフォーム二重送信防止・通知閉じる処理。
 * 認証・権限判定はすべてサーバー側で行い、顧客情報をブラウザ保存領域へ書き込みません。
 */
document.addEventListener("DOMContentLoaded", () => {
    const passwordInput = document.querySelector("[data-password-input]");
    const passwordToggle = document.querySelector("[data-password-toggle]");
    const loginForm = document.querySelector("[data-login-form]");
    const loginCard = document.querySelector("[data-login-card]");
    const submitButton = document.querySelector("[data-submit-button]");
    const errorMessage = document.querySelector(".form-error");

    if (passwordInput && passwordToggle) {
        passwordToggle.addEventListener("click", () => {
            const isHidden = passwordInput.type === "password";

            passwordInput.type = isHidden ? "text" : "password";
            passwordToggle.textContent = isHidden ? "非表示" : "表示";
            passwordToggle.setAttribute("aria-pressed", String(isHidden));
            passwordToggle.setAttribute(
                "aria-label",
                isHidden ? "パスワードを非表示" : "パスワードを表示"
            );
        });
    }

    if (loginForm && submitButton) {
        loginForm.addEventListener("submit", () => {
            submitButton.disabled = true;
            submitButton.textContent = "確認中...";
        });
    }

    if (loginCard && errorMessage) {
        loginCard.classList.add("is-error");
    }

    /* 業務画面の二重送信防止（form[data-single-submit]） */
    document.querySelectorAll("form[data-single-submit]").forEach((form) => {
        form.addEventListener("submit", () => {
            if (form.dataset.submitting === "true") {
                return;
            }

            form.dataset.submitting = "true";

            const buttons = [
                ...form.querySelectorAll("button[type='submit']"),
                ...document.querySelectorAll(`button[type='submit'][form='${form.id}']`),
            ];

            window.setTimeout(() => {
                buttons.forEach((button) => {
                    button.disabled = true;
                });
            }, 0);
        });
    });

    /* フラッシュ通知を閉じる */
    document.querySelectorAll("[data-flash-close]").forEach((button) => {
        button.addEventListener("click", () => {
            button.closest("[data-flash]")?.remove();
        });
    });
});
