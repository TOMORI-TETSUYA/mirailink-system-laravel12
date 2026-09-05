#!/bin/bash
# phpMyAdmin 設定ストレージ（01-phpmyadmin-storage.sql で作成した `phpmyadmin` DB）への
# アクセス権をアプリ用ユーザーへ付与します。
#
# controluser は用意せず、ログインしたユーザー自身の権限で設定ストレージを読み書きします
# （認証情報を増やさないため）。root はグローバル権限を持つので付与は不要です。
#
# このスクリプトは MySQL のデータディレクトリが空の初回起動時のみ実行されます。
set -e

mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    GRANT ALL PRIVILEGES ON \`phpmyadmin\`.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL

echo "[mirailink] phpMyAdmin 設定ストレージの権限を ${MYSQL_USER} へ付与しました。"
