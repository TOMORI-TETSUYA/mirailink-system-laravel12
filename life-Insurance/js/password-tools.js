"use strict";

/*
 * MiraiLink - password-tools.js
 * 管理画面の資格情報入力補助です。
 *
 * - パスワードの自動生成（大文字・小文字・数字・記号を必ず 1 文字以上含む）
 * - 生成直後だけ平文表示し、画面を離れると伏せ字へ戻す
 * - ログインID・表示名・パスワードのクリップボードコピー
 *
 * 生成値やコピーした値を localStorage 等のブラウザ保存領域へ書き込みません（仕様 6.14）。
 * 乱数は Math.random ではなく crypto.getRandomValues を使用します。
 */
document.addEventListener("DOMContentLoaded", () => {
    /*
     * 見間違えやすい文字（大文字の I と O、小文字の l、数字の 0 と 1）は除いています。
     * 記号は Laravel の Password::symbols() が認める範囲のうち、
     * 引用符・バックスラッシュ・山括弧のようにコピー先で誤解釈されやすいものを外しています。
     */
    const POOLS = [
        "ABCDEFGHJKLMNPQRSTUVWXYZ",
        "abcdefghijkmnopqrstuvwxyz",
        "23456789",
        "!#$%&*+-=?@^_",
    ];

    const ALL = POOLS.join("");
    const UINT32_RANGE = 2 ** 32;

    /** 剰余による偏りを避けた乱数です。 */
    const randomInt = (maxExclusive) => {
        const limit = Math.floor(UINT32_RANGE / maxExclusive) * maxExclusive;
        const buffer = new Uint32Array(1);
        let value;

        do {
            crypto.getRandomValues(buffer);
            value = buffer[0];
        } while (value >= limit);

        return value % maxExclusive;
    };

    const generatePassword = (length) => {
        const size = Math.max(length, POOLS.length);

        // 各種別から 1 文字ずつ確保し、必ず全種別が含まれるようにします。
        const chars = POOLS.map((pool) => pool.charAt(randomInt(pool.length)));

        while (chars.length < size) {
            chars.push(ALL.charAt(randomInt(ALL.length)));
        }

        // 先頭 4 文字の種別が固定されないよう並べ替えます（Fisher-Yates）。
        for (let i = chars.length - 1; i > 0; i -= 1) {
            const j = randomInt(i + 1);
            const swap = chars[i];
            chars[i] = chars[j];
            chars[j] = swap;
        }

        return chars.join("");
    };

    /* ------------------------------------------------------------------
       表示 / 非表示
       ------------------------------------------------------------------ */

    const visibilityButtons = () => document.querySelectorAll("[data-credential-visibility]");

    const applyVisibility = (button, visible) => {
        const input = document.getElementById(button.dataset.credentialVisibility);

        if (!input) {
            return;
        }

        input.type = visible ? "text" : "password";
        button.textContent = visible ? "非表示" : "表示";
        button.setAttribute("aria-pressed", String(visible));
        button.setAttribute("aria-label", visible ? "パスワードを非表示にする" : "パスワードを表示する");
    };

    /** 画面を離れるときは必ず伏せ字へ戻します。 */
    const maskAll = () => {
        visibilityButtons().forEach((button) => applyVisibility(button, false));
        document.querySelectorAll("[data-credential-note]").forEach((note) => {
            note.hidden = true;
        });
    };

    visibilityButtons().forEach((button) => {
        applyVisibility(button, false);

        button.addEventListener("click", () => {
            const input = document.getElementById(button.dataset.credentialVisibility);
            applyVisibility(button, input ? input.type === "password" : false);
        });
    });

    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            maskAll();
        }
    });

    window.addEventListener("pagehide", maskAll);

    /* ------------------------------------------------------------------
       クリップボードコピー
       ------------------------------------------------------------------ */

    const announce = (message) => {
        const region = document.querySelector("[data-credential-status]");

        if (region) {
            region.textContent = message;
        }
    };

    const flash = (button, message) => {
        const original = button.dataset.originalLabel ?? button.textContent.trim();
        button.dataset.originalLabel = original;
        button.textContent = message;

        window.setTimeout(() => {
            button.textContent = original;
        }, 1600);
    };

    /** navigator.clipboard が使えない環境（非セキュアコンテキスト等）の代替です。 */
    const fallbackCopy = (text) => {
        const area = document.createElement("textarea");
        area.value = text;
        area.setAttribute("readonly", "readonly");
        area.classList.add("visually-hidden");
        document.body.appendChild(area);
        area.select();

        let copied = false;

        try {
            copied = document.execCommand("copy");
        } catch (error) {
            copied = false;
        }

        area.remove();

        return copied;
    };

    const readValue = (element) => {
        if (element === null) {
            return "";
        }

        if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
            return element.value;
        }

        return element.textContent.trim();
    };

    const writeToClipboard = async (text) => {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);

                return true;
            } catch (error) {
                return fallbackCopy(text);
            }
        }

        return fallbackCopy(text);
    };

    /*
     * ログインID・表示名・パスワードを 1 つのボタンでまとめてコピーします。
     * data-credential-copy-set には {"ラベル": "要素id"} を JSON で渡します。
     */
    document.querySelectorAll("[data-credential-copy-set]").forEach((button) => {
        button.addEventListener("click", async () => {
            let sources;

            try {
                sources = JSON.parse(button.dataset.credentialCopySet);
            } catch (error) {
                return;
            }

            const lines = [];
            const missing = [];

            Object.entries(sources).forEach(([label, id]) => {
                const value = readValue(document.getElementById(id));

                if (value === "") {
                    missing.push(label);

                    return;
                }

                lines.push(label + ": " + value);
            });

            if (lines.length === 0) {
                flash(button, "空です");
                announce("コピーできる値がありません。");

                return;
            }

            const copied = await writeToClipboard(lines.join("\n"));

            flash(button, copied ? "コピー済" : "失敗");
            announce(
                copied
                    ? `${lines.length}項目をコピーしました。` +
                          (missing.length > 0 ? `${missing.join("・")}は空のため含めていません。` : "")
                    : "コピーできませんでした。"
            );
        });
    });

    /* ------------------------------------------------------------------
       自動生成
       ------------------------------------------------------------------ */

    document.querySelectorAll("[data-credential-generate]").forEach((button) => {
        button.addEventListener("click", () => {
            const targets = (button.dataset.credentialTargets ?? "")
                .split(" ")
                .map((id) => id.trim())
                .filter((id) => id !== "");

            if (targets.length === 0) {
                return;
            }

            const length = Number.parseInt(button.dataset.credentialLength ?? "16", 10);
            const password = generatePassword(Number.isNaN(length) ? 16 : length);

            targets.forEach((id) => {
                const input = document.getElementById(id);

                if (input) {
                    input.value = password;
                    // 入力イベントに依存する検証やボタン制御へ変更を伝えます。
                    input.dispatchEvent(new Event("input", { bubbles: true }));
                }
            });

            // 生成した値を確認・コピーできるよう、主フィールドだけ平文表示にします。
            const primaryToggle = document.querySelector(
                `[data-credential-visibility="${targets[0]}"]`
            );

            if (primaryToggle) {
                applyVisibility(primaryToggle, true);
            }

            const note = document.querySelector("[data-credential-note]");

            if (note) {
                note.hidden = false;
            }

            announce("パスワードを自動生成しました。画面を離れると伏せ字に戻ります。");
        });
    });
});
