# MiraiLink — 保険顧客管理システム

Laravel 12 / PHP 8.4 / MySQL 8.4 LTS / Nginx / Docker Compose  
仕様書「保険顧客管理システム 実装仕様書 v1.3（レスポンシブ対応版）」を元に生成した実装です。

- システム名: **MiraiLink**
- ルートディレクトリ: `mirailink-system/`（仕様書の `lifeInsurance-ｍanagement` を置き換え。全角「ｍ」を含まないため、Docker・Git・シェルでの事故を防げます）
- 公開ディレクトリ: `life-Insurance/`（Nginx ドキュメントルート、`index.php`・CSS・JS のみ）
- Laravel 本体: `life-Insurance_app/`（非公開。URL から直接アクセスできません）
- Docker 設定: `docker/php/`（Dockerfile, php.ini, opcache.ini, entrypoint.sh）、`docker/nginx/default.conf`

> 大文字・小文字（`life-Insurance` / `life-Insurance_app`）は仕様書どおりです。変更しないでください。

---

## 1. 起動手順

```bash
cd mirailink-system

# 1. 環境ファイルを作成し、パスワード類を書き換える
cp .env.example .env

# 2. APP_KEY を生成して .env の APP_KEY に貼り付ける
#    （初回はイメージのビルドと composer install が走るため数分かかります）
docker compose run --rm app php artisan key:generate --show
#    -> base64:xxxx を .env の APP_KEY= に設定
#    CUSTOMER_SEARCH_HMAC_KEY も APP_KEY とは別のランダム値にする（例: openssl rand -base64 32）

# 3. 起動
docker compose up -d --build

# 4. DB テーブル作成・公開ストレージリンク
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link

# 5. 管理者を対話式で作成（平文パスワードはコードに置きません）
docker compose exec app php artisan app:create-admin

# 6. テスト（artisan test は nunomaduro/collision に依存するため本プロジェクトでは未定義。
#    phpunit を直接実行します）
docker compose exec app ./vendor/bin/phpunit

# 7. ブラウザ
#    アプリ:       http://localhost:8080
#    phpMyAdmin:   http://localhost:8081
```

- `app` コンテナは起動時に `vendor/` が無ければ自動で `composer install` を実行します（`docker/php/entrypoint.sh`）。`composer.lock` は初回 install 時に生成されるので、その後は Git 管理してください。
- ホストのユーザー ID が 1000 以外の場合は `.env` の `APP_UID` / `APP_GID` を合わせてください（`storage/` への書き込み権限のため）。
- `db` サービスは `ports` を持たず、`backend` ネットワークは `internal: true` です。ホストや外部から MySQL へ直接接続できません。MySQL を操作する経路は `docker compose exec db mysql ...` と phpMyAdmin（後述）の 2 つです。
- `phpunit.xml` の `<server>` 要素は**削除しないでください**。compose が `DB_CONNECTION=mysql` などを実環境変数としてコンテナへ渡すため、これが無いとテストが sqlite の `:memory:` ではなく開発用 MySQL に接続し、`RefreshDatabase` が開発データを消去します。`<env force="true">` だけでは不十分です（PHPUnit は `$_ENV` と `putenv()` しか更新せず、Laravel の `env()` は `$_SERVER` を優先して読むため）。理由は `phpunit.xml` のコメントに記載しています。
- イメージタグ（`php:8.4.23-fpm-bookworm` / `mysql:8.4.10` / `nginx:1.28.3-alpine`）は仕様書の指定値です。レジストリに存在しない場合は `.env` の `PHP_IMAGE` / `MYSQL_IMAGE` / `NGINX_IMAGE` で差し替えてください（例: `php:8.4-fpm-bookworm`, `mysql:8.4`, `nginx:1.28-alpine`）。私はレジストリの存在確認ができないため、ここは断定していません。

### 「読み込み中」のまま画面が開かないとき

コンテナは `Up` なのにブラウザが応答を待ち続ける場合、アプリの不具合ではなく
**Docker Desktop のファイル共有（バインドマウント）が停止している**可能性があります。

切り分け方法（コンテナ自身のファイルは読めるのに、マウント配下だけ固まるのが特徴です）。

```bash
# 1. コンテナ内のファイル → OK なら Docker 自体は生きている
docker compose exec -T nginx sh -c 'timeout 8 cat /etc/nginx/nginx.conf >/dev/null && echo OK || echo NG'

# 2. マウント配下 → ここだけ NG / タイムアウトならファイル共有の停止
docker compose exec -T nginx sh -c 'timeout 8 cat /var/www/html/life-Insurance/robots.txt >/dev/null && echo OK || echo NG'
```

2 が NG のときは `docker compose restart` では復旧しません。Docker エンジンごと再起動します。

```bash
docker desktop restart
docker compose up -d
```

`mysql_data` は名前付きボリュームのため、この操作でデータは失われません。
nginx のアクセスログが途中で止まっていること、`stat() failed (5: I/O error)` が出ることも同じ症状の目印です。

#### 実施済みの軽減策（元に戻さないでください）

| 箇所 | 内容 | 理由 |
| --- | --- | --- |
| `docker-compose.yml` の nginx | マウントを `./life-Insurance` のみに限定 | nginx が必要とするのは公開ディレクトリの約15ファイルだけです。以前はリポジトリ全体（`vendor` を含む約8,400ファイル）を共有しており、ファイル共有層への負荷が過大でした。PHP へは `SCRIPT_FILENAME` を文字列で渡すだけなので、アプリ本体は不要です |
| `docker/nginx/default.conf` | `sendfile off;` + `aio threads;` | ファイル読み込みをワーカーからスレッドプールへ移し、共有層が遅延しても全ワーカーが専有されて無応答になるのを防ぎます |
| 同上 | `open_file_cache` | 同じファイルの `stat()` / `open()` の繰り返しを減らします。開発中の更新が遅れて見えないよう `valid` は 10 秒と短くしています |

app コンテナは引き続きリポジトリ全体（約8,400ファイル、うち `vendor/` が約7,961）を共有しています。
`vendor/` を名前付きボリュームへ移すと共有ファイルは約470まで減りますが、Windows 側から
`vendor/` が見えなくなり IDE の PHP 補完が効かなくなるため、現時点では採用していません。
再発が続く場合はこの変更を検討してください。

### アプリのログイン情報（ローカル開発用）

手順 5 の `app:create-admin` で作成した初期管理者です。

| 項目 | 値 |
| --- | --- |
| URL | http://localhost:8080 |
| ログインID | `admin` |
| 初期パスワード | `MiraiLink@Local2026` |
| 表示名 | システム管理者 |
| 権限 | `admin`（管理者） |

- **この初期パスワードは初回ログイン時に必ず変更を求められます。** `app:create-admin` は `must_change_password = true` を設定するため（`app/Console/Commands/CreateAdmin.php`）、`EnsurePasswordIsChanged` ミドルウェアがパスワード変更画面へ強制的に誘導します。変更するまで他の画面は開けません。
- そのため上記は**一度きりの初期パスワード**です。本番環境ではこの値を使わず、`app:create-admin` を対話式で実行して別の値を設定してください。README に平文を残すのはローカル開発の利便性のためで、仕様 30（平文パスワードをコード・Seeder に固定しない）の趣旨に沿って、固定値は Seeder ではなくこのドキュメントにのみ置いています。
- 管理者を作り直す場合は、先に既存ユーザーを削除してください（`login_id` は一意制約）。

```bash
docker compose exec app php artisan app:create-admin
```

### 公開パスの確認（仕様 31.6）

```bash
docker compose exec app php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; echo $app->publicPath(), PHP_EOL;'
# => /var/www/html/life-Insurance
```

### phpMyAdmin（MySQL 管理 UI）

`docker compose up -d` で他サービスと一緒に起動します。

| 項目 | 値 |
| --- | --- |
| URL | http://localhost:8081 （`.env` の `PMA_PORT` で変更可） |
| 公開範囲 | **127.0.0.1 のみ**。同一 LAN の他端末からは接続できません |
| 認証方式 | cookie 認証。資格情報はコンテナに保存されず、ログイン画面で毎回入力します |
| ログイン | `mirailink_app` … `mirailink` DB のみ操作可（通常はこちら）<br>`root` … 全 DB とユーザー管理が可能 |
| パスワード | `.env` の `DB_PASSWORD` / `DB_ROOT_PASSWORD` |

- `db` は `internal: true` の `backend` にいるため、phpMyAdmin は `backend`（DB 接続用）と `frontend`（ブラウザ公開用）の両方に接続しています。MySQL 自体のポートは公開されません。
- 設定ストレージ（クエリ履歴・ブックマーク・リレーション・デザイナ）を有効にしてあります。`docker/mysql/initdb/` の 2 ファイルが**初回起動時のみ**実行され、`phpmyadmin` データベースと権限を作成します。
- `controluser` は用意していません。ログインしたユーザー自身の権限で設定ストレージを読み書きするため、管理用の認証情報が増えません。
- **既にボリュームが存在する環境**では initdb は実行されません。後から有効化する場合は次を実行してください。

```bash
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < docker/mysql/initdb/01-phpmyadmin-storage.sql
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON \`phpmyadmin\`.* TO '"'"'$MYSQL_USER'"'"'@'"'"'%'"'"'; FLUSH PRIVILEGES;"'
```

- `PMA_BLOWFISH_SECRET` はログイン Cookie の暗号化鍵です。未設定だとイメージがコンテナ起動ごとにランダム生成するため、値を固定して挙動を再現可能にしています。
  なお PHP セッションはコンテナ内の `/sessions` に置かれるため、鍵を固定していても**コンテナを作り直す（`--force-recreate` など）と再ログインが必要**です。`docker compose restart` ではログインは維持されます（実測で確認）。

---

## 2. ディレクトリ構成

```text
mirailink-system/
├── life-Insurance/                 # 公開側（Nginx root）
│   ├── index.php                   # Laravel 公開入口（兄弟ディレクトリの Laravel を起動）
│   ├── robots.txt
│   ├── css/ app.css responsive.css plan-settings.css
│   ├── js/  app.js responsive-navigation.js plan-settings.js contract-plan-selector.js modal.js
│   └── images/
├── life-Insurance_app/             # Laravel 12 本体（非公開）
│   ├── app/
│   │   ├── Actions/Audit/RecordCustomerAccess.php
│   │   ├── Actions/Plans/{CreateInsurancePlan,ChangePlanPrice}.php
│   │   ├── Casts/EncryptedSearchable.php          # 暗号化 + HMAC 列を同時に書き込むキャスト
│   │   ├── Console/Commands/CreateAdmin.php       # php artisan app:create-admin
│   │   ├── Http/Controllers/…（Auth, Settings, Customer, Contract, Interaction, User, AuditLog）
│   │   ├── Http/Middleware/{SecurityHeaders,EnsureUserIsActive,EnsurePasswordIsChanged,NoStoreCache}.php
│   │   ├── Http/Requests/…
│   │   ├── Models/…（User, Customer, CustomerIntention, InsurancePlan, InsurancePlanPrice,
│   │   │              InsuranceContract, Interaction, MaintenanceHistory, AuditLog, Setting）
│   │   ├── Policies/{Customer,InsurancePlan,AuditLog,User}Policy.php
│   │   ├── Providers/AppServiceProvider.php        # Gate 定義・View Composer
│   │   ├── Services/{AuditLogService,CustomerCodeService,PlanCodeService,PlanPriceResolver,SearchHashService}.php
│   │   └── View/Composers/DashboardComposer.php
│   ├── bootstrap/app.php                           # usePublicPath(../life-Insurance)
│   ├── config/{app,auth,database,hashing,logging,session,mirailink}.php
│   ├── database/{migrations,factories,seeders}/
│   ├── lang/ja/
│   ├── resources/views/…（layouts, components, auth, customers, contracts, settings, users, audit-logs, errors）
│   ├── routes/{web,console}.php
│   ├── tests/{Feature,Unit}/
│   ├── artisan / composer.json / phpunit.xml
│   └── storage/
├── docker/
│   ├── mysql/initdb/               # 初回起動時のみ実行（phpMyAdmin 設定ストレージ）
│   │   ├── 01-phpmyadmin-storage.sql
│   │   └── 02-phpmyadmin-grants.sh
│   ├── nginx/default.conf
│   ├── php/{Dockerfile,php.ini,opcache.ini,entrypoint.sh}
│   └── phpmyadmin/config.user.inc.php
├── .dockerignore  .env.example  .gitignore  docker-compose.yml  README.md
```

---

## 3. 主な実装内容（仕様との対応）

| 仕様 | 実装 |
|---|---|
| 公開入口と本体の分離（4.1） | `life-Insurance/index.php` → `../life-Insurance_app`。`bootstrap/app.php` で `usePublicPath()`。Nginx root は `/var/www/html/life-Insurance` に限定 |
| 3 権限（4.2） | `admin` / `staff` / `auditor`。Blade の `@can` に加え、Policy と Middleware でサーバー側判定 |
| 認証・レート制限（6.1–6.2） | `LoginController`（仕様 16 のコードを踏襲）。ID+IP 5回/1分、IP 20回/1時間。共通エラーメッセージ |
| パスワード（6.3） | 12〜128 文字、かつ大文字・小文字・数字・記号をすべて必須（`AppServiceProvider` の `Password::defaults()` 一箇所で定義）。`must_change_password` により初回ログイン時に変更を強制（`EnsurePasswordIsChanged`）。ユーザー作成・再設定・パスワード変更の各画面に自動生成ボタンとコピーボタンを用意（`js/password-tools.js`） |
| セッション（6.4） | `SESSION_DRIVER=database`, 30 分, 暗号化, HttpOnly, SameSite=Lax。「ログイン状態を保持」は実装しない |
| CSRF / XSS / SQLi（6.5–6.7） | `@csrf` 必須、`{!! !!}` 不使用、Eloquent / Query Builder のみ |
| 暗号化（6.8） | Customer 等の機微列は `encrypted` キャスト。電話番号・メールは `EncryptedSearchable` で暗号化 + HMAC（鍵は `CUSTOMER_SEARCH_HMAC_KEY`、`APP_KEY` と分離） |
| 監査ログ（6.10） | `AuditLogService`。操作者・日時・種別・対象・成否・IP/UA（暗号化）・リクエストID・変更項目名のみ。平文の値は保存しない |
| 論理削除・保存期間（6.11） | 顧客は SoftDeletes。保存期間は `settings` テーブルで管理（`/settings/retention`）。固定年数をコードに書かない |
| ブラウザ保存・キャッシュ（6.14） | `localStorage` 等を一切使用しない。顧客・監査ログ画面は `NoStoreCache` で `Cache-Control: no-store` |
| セキュリティヘッダー（18） | `SecurityHeaders`（CSP: `style-src 'self'; script-src 'self'`）。CSS/JS はすべて外部ファイル |
| プラン設定（7.5） | `/settings/plans`。プランコード自動発行、価格履歴（`insurance_plan_prices`）、有効化には適用中/将来価格が必須 |
| 契約時プラン選択（7.6） | プラン選択で金額自動表示。保存時はサーバーで契約日基準に再判定し、スナップショットを暗号化保存。管理者のみ理由必須で上書き。前後金額・理由を監査ログへ |
| レスポンシブ（4.4, 7.7–7.9, 8.x） | モバイルファースト、768/1024/1440 ブレークポイント、テーブル→カード切替、44px 操作領域、`100dvh`、`safe-area-inset`、`prefers-reduced-motion` |
| Docker（23–27） | 仕様書の Dockerfile / nginx / compose を踏襲。名前・コンテナ名は ASCII（`mirailink-system`, `mirailink_system_*`） |

---

## 4. 4 つの視点によるレビューと反映

生成前に「賛成派・中立派・反対派」の 4 名（賛成 1・中立 2・反対 1）の立場で仕様を読み直し、次のように反映しました。

| 視点 | 意見 | 反映 |
|---|---|---|
| 賛成派（セキュリティ担当） | 仕様どおり CSP を厳格にし、インライン JS/CSS を排除すべき。モーダルも標準 `confirm()` ではなく自前で。 | すべての確認ダイアログ・登録フォームをカスタムモーダル（`components/modal.blade.php` + `js/modal.js`）で実装。フォーカストラップ・Escape・フォーカス復帰・JS 無効時のフォールバック（そのまま送信）を実装 |
| 中立派 A（フロント） | 余白を各所で個別指定すると「スペーシングバグ」（margin 二重適用・collapse・カード間のズレ）が起きる。 | 余白を `--space-*` スケールに統一し、縦方向の間隔は `.content-container { display:grid; gap }` に一元化。`.page-header` などの個別 `margin-bottom` を持たない。フォームは grid gap のみ |
| 中立派 B（Laravel） | `Route::view('/', 'index')` のままだと集計値を渡せない。ルートを変えずに解決したい。 | `View::composer('index', DashboardComposer::class)` で仕様のルート定義を維持したまま集計値を供給 |
| 反対派（運用・法務） | バージョン固定（PHP 8.4.23 等）は存在しない可能性があり、ハルシネーションになる。保存期間の固定や平文の初期パスワードも危険。 | イメージタグは仕様値をデフォルトにしつつ `.env` で差し替え可能に。保存期間は設定テーブル管理。管理者は対話式コマンドで作成し Seeder に平文を置かない。確認できない事項は本 README に「確認が必要」と明記 |

---

## 5. 仕様書からの意図的な変更点・追加点（要確認）

1. **ルート名**: `lifeInsurance-ｍanagement` → `mirailink-system`（依頼条件）。内部の `life-Insurance` / `life-Insurance_app` は維持。
2. **タイトル**: 画面・`APP_NAME` を「MiraiLink」に変更（依頼条件）。副題として「生命保険顧客管理」を併記。
3. **追加ルート**（仕様 15 に無い MVP 機能を補完）: パスワード変更、契約の登録画面/編集、保全・給付履歴登録、監査ログ一覧、CSV 出力、保存期間設定。仕様 15 のルートはそのまま含んでいます。
4. **追加ファイル**: `js/modal.js`（カスタムモーダル）、`Middleware/NoStoreCache.php`、`Middleware/EnsurePasswordIsChanged.php`、`Models/Setting.php`、`Services/SearchHashService.php`、`View/Composers/DashboardComposer.php`。
5. **CSV 出力**: 顧客コード・氏名・担当者・状態・登録日のみ。住所・電話・病歴は含めません（範囲は 34 の「CSV 出力の許可範囲」で要決定）。
6. **監査ログ**: 追記専用（`updated_at` なし、更新/削除ルートなし）。改ざん抑止として DB 権限側での UPDATE/DELETE 禁止を推奨。
7. **composer.lock**: この環境では Packagist にアクセスできないため未生成です。初回 `composer install` 後にコミットしてください。同じ理由で `php artisan test` の実行結果はここでは確認できていません（全 PHP ファイルは `php -l` で構文検証済み）。

---

## 6. 本番前チェック（抜粋）

- `APP_ENV=production` / `APP_DEBUG=false` / `SESSION_SECURE_COOKIE=true` / HTTPS 終端
- `APP_KEY`・`CUSTOMER_SEARCH_HMAC_KEY` を Secret Manager 等で管理、Git に含めない
- `composer install --no-dev` と `php artisan optimize`（entrypoint は `APP_ENV=production` で `--no-dev`）
- **phpMyAdmin を本番で起動しない**。顧客の機微情報へ直接アクセスできるため、`docker-compose.yml` から `phpmyadmin` サービスを削除するか、`profiles: [tools]` を付けて既定では起動しないようにしてください（ローカルでは `docker compose --profile tools up -d` で起動）
- 管理者の多要素認証追加（仕様 6.1 受入条件。本実装には含まれません）
- 320 / 375 / 768 / 1024 / 1440px での表示確認、`prefers-reduced-motion` の確認
- 仕様書 32 のチェックリスト全項目
