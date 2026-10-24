# Prerequisites & Setup — Online Voting System (Windows)

Everything below is Windows-specific. This project is plain PHP (custom MVC) with Composer dependencies for email and Excel import.

## 1. Install XAMPP

1. Download XAMPP with **PHP 8.1 or newer** from https://www.apachefriends.org and run the installer.
2. Install to the default `C:\xampp`.
3. Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
4. Add PHP and MySQL to your PATH so you can run `php`, `mysql`, `composer` from any terminal:
   - Windows Settings → System → About → Advanced system settings → Environment Variables.
   - Under "System variables", edit `Path`, add `C:\xampp\php` and `C:\xampp\mysql\bin`.
   - Open a **new** terminal and confirm: `php -v` and `mysql --version`.

## 2. Install Composer

1. Download and run `Composer-Setup.exe` from https://getcomposer.org/download/ — it detects `C:\xampp\php\php.exe` automatically and adds `composer` to PATH.
2. Confirm: `composer -V` in a new terminal.

## 3. Enable required PHP extensions

This project's `composer.json` needs `phpmailer/phpmailer` and `phpoffice/phpspreadsheet`, which require the `gd` and `zip` extensions (`pdo_mysql` is enabled by default in XAMPP).

1. Open `C:\xampp\php\php.ini` in a text editor.
2. Find and uncomment (remove the leading `;`) these lines:
   ```
   extension=gd
   extension=zip
   ```
3. Save, then restart Apache from the XAMPP Control Panel for the change to take effect.

## 4. Configure MySQL/MariaDB port

`config/database.php` in this project is committed with `host=127.0.0.1, port=3306` — XAMPP's own default MariaDB port, so no changes should be needed on a standard XAMPP install.

- **If your MySQL runs on a different port**, edit `config/database.php` and change `'port' => '3306'` to match, or move MariaDB back to 3306 by editing `C:\xampp\mysql\bin\my.ini` (`[mysqld]` → `port=3306`) and restarting MySQL via the XAMPP Control Panel or `C:\xampp\mysql_start.bat`.

## 5. Install project dependencies

From this project's folder in a terminal:
```
composer install
```

## 6. Database setup

Two options — pick one:

**A. Setup wizard (recommended)** — after serving the app (see HOW-TO-RUN.md), visit `/install.php` in the browser. It tests the DB connection, creates the `online_voting_system` database, imports `database/schema.sql`, lets you set your own admin account, and writes `config/database.php`/`config/app.php` itself. **Delete or rename `public/install.php` once done** — a working DB-setup script reachable over HTTP is a security risk to leave in place.

**B. Manual import** — creates the DB with the seeded demo admin instead of a fresh one:
```
mysql -u root -h 127.0.0.1 -P 3306 < database/schema.sql
```
(swap `-P 3306` for your actual port from step 4). To wipe the seeded data and start over, drop the database first, then re-run the import (re-running it directly without dropping first fails with "table already exists"):
```
mysql -u root -h 127.0.0.1 -P 3306 -e "DROP DATABASE IF EXISTS online_voting_system;"
```

## 7. Configure outgoing email (for OTP/reset emails)

Edit `config/mail.php` with SMTP credentials from a free test inbox such as https://mailtrap.io — sign up, create an inbox, and copy its SMTP host/port/username/password in. Without this, mail-sending calls fail (usually silently/logged) but the rest of the app still works for a demo.

## 8. Verify everything

```
php -v
composer -V
mysql -u root -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```
If the last command connects without error, the database is reachable and you're ready to run the app — see `HOW-TO-RUN.md`.
