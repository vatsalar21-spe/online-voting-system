<?php

namespace App\Models;

use Core\Database;

class User
{
    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findByResetToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function createSelfRegistration(string $name, string $email, string $password): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, voter_code, password_hash, role, status, source)
             VALUES (?, ?, ?, ?, \'voter\', \'pending\', \'self_registered\')'
        );
        $stmt->execute([$name, $email, self::generateVoterCode(), password_hash($password, PASSWORD_BCRYPT)]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function setResetToken(int $id, string $token, string $expiresAt): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?'
        );
        $stmt->execute([$token, $expiresAt, $id]);
    }

    public static function resetPassword(int $id, string $password): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?'
        );
        $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
    }

    public static function pending(): array
    {
        $stmt = Database::connection()->query(
            "SELECT * FROM users WHERE role = 'voter' AND status = 'pending' ORDER BY created_at ASC"
        );
        return $stmt->fetchAll();
    }

    public static function allVoters(): array
    {
        $stmt = Database::connection()->query(
            "SELECT * FROM users WHERE role = 'voter' ORDER BY created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public static function approve(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE users SET status = 'approved' WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public static function reject(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE users SET status = 'rejected' WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    /**
     * Creates (or finds, if the email already exists) one row from a
     * bulk-imported Excel roster. Called per-election, so the caller enrolls
     * the returned user_id regardless of whether it was newly created or
     * already existed — only truly malformed rows are skipped entirely.
     *
     * Admin-added voters (bulk or individual) never go through an
     * email-activation step: the account is created active, with a
     * generated password handed back here so the caller can show it to the
     * admin directly. An emailed activation link would be a dead end if SMTP
     * isn't configured — self-registered voters are the only ones who set
     * their own password, and they're gated by admin *approval* instead.
     *
     * @return array{status: 'created'|'existing'|'invalid', user_id?: int, voter_code?: string, password?: string, name?: string, email?: string}
     */
    public static function bulkImportRow(string $name, string $email): array
    {
        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'invalid'];
        }

        $existing = self::findByEmail($email);
        if ($existing) {
            return ['status' => 'existing', 'user_id' => (int) $existing['id']];
        }

        return ['status' => 'created', ...self::insertAdminAdded($name, $email, 'bulk_import')];
    }

    /**
     * Admin adding a single voter by hand — same as one bulk-import row, just
     * via a form instead of a spreadsheet.
     *
     * @return array{id: int, voter_code: string, password: string}
     */
    public static function createManual(string $name, string $email): array
    {
        $created = self::insertAdminAdded($name, $email, 'admin_manual');
        return ['id' => $created['user_id'], 'voter_code' => $created['voter_code'], 'password' => $created['password']];
    }

    private static function insertAdminAdded(string $name, string $email, string $source): array
    {
        $voterCode = self::generateVoterCode();
        $password = self::generatePassword();

        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, voter_code, password_hash, role, status, source)
             VALUES (?, ?, ?, ?, \'voter\', \'approved\', ?)'
        );
        $stmt->execute([$name, $email, $voterCode, password_hash($password, PASSWORD_BCRYPT), $source]);

        return [
            'user_id' => (int) Database::connection()->lastInsertId(),
            'voter_code' => $voterCode,
            'password' => $password,
            'name' => $name,
            'email' => $email,
        ];
    }

    private static function generateVoterCode(): string
    {
        // UUID v4
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }

    private static function generatePassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $password;
    }

    public static function updateDetails(int $id, string $name, string $email): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $stmt->execute([$name, $email, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}
