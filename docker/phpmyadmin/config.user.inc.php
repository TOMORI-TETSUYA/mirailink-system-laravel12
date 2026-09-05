<?php

declare(strict_types=1);

/**
 * phpMyAdmin 追加設定。
 *
 * イメージの /etc/phpmyadmin/config.inc.php が最後にこのファイルを include します。
 * その直前で $i は最後に構成されたサーバ番号へ戻されているため、
 * $cfg['Servers'][$i] でそのまま db サーバの設定を上書きできます。
 */

// コンテナを再作成しても blowfish_secret が変わらないようにします。
// 未設定の場合はイメージが起動時に生成したランダム値をそのまま使います。
if (! empty($_ENV['PMA_BLOWFISH_SECRET'])) {
    $cfg['blowfish_secret'] = $_ENV['PMA_BLOWFISH_SECRET'];
}

// イメージ既定の AllowNoPassword = true を打ち消し、パスワード無しログインを禁止します。
if (isset($i, $cfg['Servers'][$i])) {
    $cfg['Servers'][$i]['AllowNoPassword'] = false;
}

// PHP 既定の session.gc_maxlifetime (1440秒) に合わせます。
// これより大きい値にすると phpMyAdmin が警告を表示します。
$cfg['LoginCookieValidity'] = 1440;

// 顧客の機微情報を扱うため、外部への送信・問い合わせを行いません。
$cfg['SendErrorReports'] = 'never';
$cfg['VersionCheck'] = false;
