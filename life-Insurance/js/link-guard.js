"use strict";

/*
 * MiraiLink - link-guard.js
 *
 * ナビゲーションとボタン型リンクから href を外し、ブラウザ左下に出る
 * URL 表示（ステータスバブル）が現れないようにします。
 *
 * 仕組み:
 *   href を持たない <a> をブラウザはリンクとして扱わないため、URL を表示しません。
 *   遷移先は data-href に退避し、クリックとキー操作で遷移させます。
 *
 * アクセシビリティの補償:
 *   href が無いと Tab 移動も読み上げもできなくなるため、role="link" と
 *   tabindex="0" を付け、Enter と Space で遷移できるようにしています。
 *   aria-current="page" による現在地表示はそのまま機能します。
 *
 * この方式で失われる機能（回復できません）:
 *   - 中クリック / Ctrl+クリックで新しいタブを開く
 *   - 右クリックからリンクのアドレスをコピー
 *
 * JS が動作しない環境では href が残るため、通常のリンクとして機能します。
 */
document.addEventListener("DOMContentLoaded", () => {
    /* URL 表示を抑止する対象。左メニューとボタン型のリンクです。 */
    const TARGETS = [
        ".app-sidebar a[href]",
        "a.primary-button[href]",
        "a.secondary-button[href]",
    ].join(", ");

    document.querySelectorAll(TARGETS).forEach((link) => {
        const url = link.getAttribute("href");

        // ページ内アンカーと空の href は対象外です。
        if (url === null || url === "" || url.charAt(0) === "#") {
            return;
        }

        link.dataset.href = url;
        link.removeAttribute("href");
        link.setAttribute("role", "link");

        if (!link.hasAttribute("tabindex")) {
            link.setAttribute("tabindex", "0");
        }

        // href が無い <a> は既定でポインタカーソルにならないため補います。
        link.classList.add("is-guarded-link");
    });

    const navigate = (element) => {
        const url = element.dataset.href;

        if (typeof url === "string" && url !== "") {
            window.location.href = url;
        }
    };

    const findTarget = (event) => {
        if (!(event.target instanceof Element)) {
            return null;
        }

        return event.target.closest("[data-href]");
    };

    document.addEventListener("click", (event) => {
        const target = findTarget(event);

        if (target !== null) {
            navigate(target);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Enter" && event.key !== " ") {
            return;
        }

        const target = findTarget(event);

        if (target === null) {
            return;
        }

        // Space による画面スクロールを抑止します。
        event.preventDefault();
        navigate(target);
    });
});
