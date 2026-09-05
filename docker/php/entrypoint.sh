#!/bin/sh
# MiraiLink app コンテナの起動スクリプト
# - vendor が無い場合のみ composer install を実行し、初回の docker compose up だけで起動できるようにします。
# - APP_KEY 等の秘密情報は生成・書き込みしません（ルートの .env で管理します）。
set -e

APP_DIR=/var/www/html/life-Insurance_app
cd "${APP_DIR}"

if [ ! -f "${APP_DIR}/vendor/autoload.php" ]; then
    echo "[mirailink] vendor が見つからないため composer install を実行します。"
    if [ "${APP_ENV:-local}" = "production" ]; then
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    else
        composer install --no-interaction --prefer-dist
    fi
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public

if [ -z "${APP_KEY}" ]; then
    echo "[mirailink] 警告: APP_KEY が未設定です。ルートの .env に設定してください。"
    echo "[mirailink]   docker compose run --rm app php artisan key:generate --show"
fi

exec "$@"
