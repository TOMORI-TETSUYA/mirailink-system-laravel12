<?php

namespace App\Support;

final class Asset
{
    /**
     * 更新日時をクエリに付けた CSS / JS の URL を返します。
     *
     * nginx はキャッシュ制御ヘッダーを付けていないため、ブラウザのヒューリスティック
     * キャッシュによって古い CSS / JS が使われ続けることがあります。ファイルを更新すると
     * URL が変わるようにして、確実に新しい内容が読み込まれるようにします。
     *
     * 公開ディレクトリは bootstrap/app.php の usePublicPath() で life-Insurance を指します。
     */
    public static function url(string $path): string
    {
        $file = public_path($path);

        if (! is_file($file)) {
            return asset($path);
        }

        return asset($path).'?v='.filemtime($file);
    }
}
