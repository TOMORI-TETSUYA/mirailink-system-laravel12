"use strict";

/*
 * MiraiLink - plan-settings.js（仕様 20.2）
 * 金額の3桁区切り表示補助と「月額 12,000円」形式の確認表示。
 * 表示用入力のカンマはサーバーへ送らず、hidden入力の整数値だけを送信します。
 * サーバー側でも必ず整数バリデーションを実施します。
 */
document.addEventListener("DOMContentLoaded", () => {
    const cycleLabels = {
        monthly: "月額",
        annual: "年額",
        single: "一時払",
        other: "その他",
    };

    const formatter = new Intl.NumberFormat("ja-JP");

    /* 「プランを追加」ボタンで入力パネルをページ移動なしに開閉します。 */
    const openButton = document.querySelector("[data-plan-form-open]");
    const quickForm = document.querySelector("[data-plan-form]");

    if (openButton && quickForm && quickForm.id === "plan-quick-form") {
        openButton.addEventListener("click", () => {
            quickForm.hidden = !quickForm.hidden;
            openButton.setAttribute("aria-expanded", String(!quickForm.hidden));

            if (!quickForm.hidden) {
                quickForm.querySelector("[name='plan_name']")?.focus();
            }
        });
    }

    /* 詳細項目の展開 */
    document.querySelectorAll("[data-plan-details-toggle]").forEach((toggle) => {
        const details = document.getElementById(toggle.getAttribute("aria-controls") ?? "");

        if (!details) {
            return;
        }

        toggle.addEventListener("click", () => {
            details.hidden = !details.hidden;
            toggle.setAttribute("aria-expanded", String(!details.hidden));
            toggle.textContent = details.hidden ? "詳細項目を表示" : "詳細項目を閉じる";
        });
    });

    /* 金額入力の表示補助（複数フォーム対応） */
    document.querySelectorAll("[data-plan-form]").forEach((form) => {
        const displayInput = form.querySelector("[data-money-display]");
        const valueInput = form.querySelector("[data-money-value]");
        const preview = form.querySelector("[data-plan-preview]");
        const cycleSelect = form.querySelector("[name='billing_cycle']");

        if (!displayInput || !valueInput) {
            return;
        }

        const updateMoney = () => {
            const digits = displayInput.value.replace(/[^0-9]/g, "");
            const amount = digits === "" ? 0 : Number.parseInt(digits, 10);

            valueInput.value = digits;
            displayInput.value = digits === "" ? "" : formatter.format(amount);

            if (!preview) {
                return;
            }

            const cycle = cycleSelect?.value ?? "monthly";
            preview.textContent = digits === ""
                ? "金額を入力してください。"
                : `${cycleLabels[cycle] ?? cycle} ${formatter.format(amount)}円`;
        };

        displayInput.addEventListener("input", updateMoney);
        cycleSelect?.addEventListener("change", updateMoney);

        updateMoney();
    });
});
