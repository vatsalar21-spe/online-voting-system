# How to Run — Online Voting System

Assumes prerequisites in `PREREQUISITES.md` are already done (XAMPP installed, extensions enabled, Composer installed).

## One-time setup

```
composer install
```

Then set up the database — pick **one**:

**A. Setup wizard** (fresh admin account of your choosing):
1. Start the server (see below).
2. Visit `http://localhost:8000/install.php` and follow the wizard.
3. Delete or rename `public/install.php` afterward.

**B. Manual import** (keeps the seeded demo admin):
```
mysql -u root -h 127.0.0.1 -P 3306 < database/schema.sql
```
To wipe the seeded data and start over, drop the database first, then re-run the import above (re-running it directly without dropping first fails with "table already exists"):
```
mysql -u root -h 127.0.0.1 -P 3306 -e "DROP DATABASE IF EXISTS online_voting_system;"
```

Then configure `config/mail.php` with SMTP credentials (e.g. Mailtrap) so credential/reset emails can be sent.

## Start MySQL/Apache

Open **XAMPP Control Panel** and make sure **MySQL** is running (Apache only needed if you're serving via a vhost instead of PHP's built-in server below).

## Run the app

From this project's folder:
```
php -S localhost:8000 -t public public/index.php
```
(`-t public` is required — without it, CSS/JS won't load.)

## Open it

Visit http://localhost:8000/login and sign in.

- If you used the setup wizard: the admin account you created.
- If you imported manually: the demo credentials listed in `README.md` under "Default Login Credentials" (demo admin: `admin@ovs.test` / `Admin@123`).

## Stop the app

`Ctrl+C` in the terminal running the `php -S` command.
