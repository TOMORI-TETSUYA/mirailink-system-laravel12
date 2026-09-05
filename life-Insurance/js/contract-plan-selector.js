"use strict";

/*
 * MiraiLink - contract-plan-selector.js（仕様 20.3 / 7.6）
 * プラン選択時に契約日時点の金額と支払単位を表示し、保存前の確認表示を更新します。
 * ここで扱うのはプランマスタの金額のみで、顧客情報は含みません。
 * 送信された価格IDはサーバー側で信用せず、契約日とプランIDから価格を再取得します。
 */
document.addEventListener("DOMContentLoaded", () => {
    const select = document.querySelector("[data-plan-select]");
    const priceIdInput = document.querySelector("[data-plan-price-id]");
    const output = document.querySelector("[data-plan-price]");
    const contractDateInput = document.querySelector("[data-contract-date]");
    const filterDateInput = document.querySelector("[data-filter-contract-date]");
    const summaryText = document.querySelector("[data-contract-summary-text]");
    const overrideToggle = document.querySelector("[data-override-toggle]");
    const overrideDisplay = document.querySelector("[data-override-panel] [data-money-display]");
    const overrideValue = document.querySelector("[data-override-panel] [data-money-value]");
    const formatter = new Intl.NumberFormat("ja-JP");

    const formatDate = (value) => {
        if (!value) {
            return "未入力";
        }

        const [year, month, day] = value.split("-").map((part) => Number.parseInt(part, 10));

        if (!year || !month || !day) {
            return value;
        }

        return `${year}年${month}月${day}日`;
    };

    const currentSelection = () => {
        const option = select?.selectedOptions?.[0];

        if (!option || option.value === "") {
            return null;
        }

        return {
            name: option.textContent.trim().replace(/\s+/g, " "),
            price: option.dataset.price ?? "",
            priceId: option.dataset.priceId ?? "",
            cycleLabel: option.dataset.cycleLabel ?? "",
            effectiveFrom: option.dataset.effectiveFrom ?? "",
        };
    };

    const overrideAmount = () => {
        if (!overrideToggle?.checked || !overrideValue) {
            return null;
        }

        const digits = overrideValue.value.replace(/[^0-9]/g, "");

        return digits === "" ? null : Number.parseInt(digits, 10);
    };

    const render = () => {
        const selection = currentSelection();

        if (priceIdInput) {
            priceIdInput.value = selection?.priceId ?? "";
        }

        if (!output || !summaryText) {
            return;
        }

        if (!selection) {
            output.textContent = "プランを選択すると金額が表示されます。";
            output.classList.remove("is-missing");
            summaryText.textContent = "プランと契約日を選択すると、適用金額をここに表示します。";
            return;
        }

        if (selection.price === "") {
            output.textContent =
                "このプランには契約日時点で有効な金額が設定されていません。設定画面で金額を登録してください。";
            output.classList.add("is-missing");
            summaryText.textContent = `契約日 ${formatDate(contractDateInput?.value)}: 適用金額がないため保存できません。`;
            return;
        }

        output.classList.remove("is-missing");
        output.textContent =
            `選択プラン: ${selection.name} / 現在金額: ${selection.cycleLabel} ${formatter.format(Number(selection.price))}円` +
            (selection.effectiveFrom ? ` / 適用開始日: ${selection.effectiveFrom}` : "");

        const override = overrideAmount();
        const applied = override ?? Number(selection.price);
        const overrideNote = override !== null ? "（管理者による上書き）" : "";

        summaryText.textContent =
            `契約日 ${formatDate(contractDateInput?.value)} に ${selection.name} を ${selection.cycleLabel} ${formatter.format(applied)}円${overrideNote} で登録します。`;
    };

    select?.addEventListener("change", render);
    contractDateInput?.addEventListener("change", () => {
        if (filterDateInput) {
            filterDateInput.value = contractDateInput.value;
        }

        render();
    });

    /* 上書き金額の3桁区切り表示補助 */
    if (overrideDisplay && overrideValue) {
        overrideDisplay.addEventListener("input", () => {
            const digits = overrideDisplay.value.replace(/[^0-9]/g, "");

            overrideValue.value = digits;
            overrideDisplay.value = digits === "" ? "" : formatter.format(Number.parseInt(digits, 10));
            render();
        });
    }

    overrideToggle?.addEventListener("change", render);

    render();
});
