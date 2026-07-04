# アプリケーション名
coachtech-timekeeper

# 環境構築
Dockerビルド

1. git clone https://github.com/shinichi-ushioda/coachtech-timekeeper.git

2. docker-compose up -d build

※MySQLは、OSによって起動しない場合があるのでそれぞれのPCに合わせてdocker-compose.ylmlファイルを編集してください。  
Laravel環境構築

1.docker-compose exec php bash  
2.composer install  
3.env.exampleファイルから.envを作成し、環境変数を変更  
4.php artisan key:generate  
5.php artisan migrate  
6.php artisan db:seed  

## 使用技術
・php 8.0
・Laravel 13.16.1  
・MySQL 8.0

## URL
・開発環境：http://localhost/:8080
・phpMyAdmin:http://localhost:8081

※Makefileは実行するコマンドを省略することができる便利な設定ファイルです。コマンドの入力を効率的に行えるようになります。<br>

## メール認証
mailtrapというツールを使用しています。<br>
以下のリンクから会員登録をしてください。　<br>
https://mailtrap.io/

動作確認する際は、以下の項目を .env に設定してください。  
.envファイルのMAIL_MAILERからMAIL_ENCRYPTIONまでの項目をコピー＆ペーストしてください。　<br>
MAIL_MAILER=smtp  
MAIL_HOST=sandbox.smtp.mailtrap.io  
MAIL_PORT=2525  
MAIL_USERNAME=（あなたのMailtrapのユーザー名）  
MAIL_PASSWORD=（あなたのMailtrapのパスワード）  
MAIL_ENCRYPTION=null  
MAIL_FROM_ADDRESSは任意のメールアドレスを入力してください。
Mailtrap の InboxID は、Mailtrap の「Email Testing → Inboxes」から確認できます。　<br>

　

## Stripeについて
〇〇〇〇〇が行える想定です。<br>

また、StripeのAPIキーは以下のように設定をお願いいたします。
```
STRIPE_PUBLIC_KEY="パブリックキー"
STRIPE_SECRET_KEY="シークレットキー"
```

以下のリンクは公式ドキュメントです。<br>
https://docs.stripe.com/payments/checkout?locale=ja-JP
## テーブル仕様
### usersテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint | ◯ |  | ◯ |  |
| name | varchar(255) |  |  | ◯ |  |
| email | varchar(255) |  | ◯ | ◯ |  |
| email_verified_at | timestamp |  |  |  |  |
| password | varchar(255) |  |  | ◯ |  |
| is_admin | boolean |  |  |  |  |
| remember_token | varchar(100) |  |  |  |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

### attendancesテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint | ◯ |  | ◯ |  |
| user_id | bigint |  |  | ◯ | users(id) |
| work_date | date |  |  | ◯ |  |
| clock_in | datetime |  |  | ◯ |  |
| clock_out | datetime |  |  | ◯ |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

### breaksテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint | ◯ |  | ◯ |  |
| attendance_id | bigint |  |  | ◯ | attendances(id) |
| break_in | datetime |  |  | ◯ |  |
| break_out | datetime |  |  | ◯ |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

### attendance_requestsテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint | ◯ |  | ◯ |  |
| attendance_id | bigint |  |  | ◯ | attendances(id) |
| break_id | bigint |  |  |  | breaks(id) |
| requested_clock_in | datetime |  |  |  |  |
| requested_clock_out | datetime |  |  |  |  |
| requested_break_in | datetime |  |  |  |  |
| requested_break_out | datetime |  |  |  |  |
| status | enum |  |  | ◯ |  |
| reason | varchar(255) |  |  | ◯ |  |
| approved_at | timestamp |  |  | ◯ |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |


## ER図
![alt](ER.png)

## テストアカウント
name: 一般ユーザ  
email: general1@gmail.com  
password: password  
-------------------------
name: 一般ユーザ  
email: general2@gmail.com  
password: password  
-------------------------

## PHPUnitを利用したテストに関して
以下のコマンド:  
```
//テスト用データベースの作成
docker-compose exec mysql bash
mysql -u root -p
//パスワードはrootと入力
create database test_database;

docker-compose exec php bash
php artisan migrate:fresh --env=testing
./vendor/bin/phpunit
```
※.env.testingにもStripeのAPIキーを設定してください。  


