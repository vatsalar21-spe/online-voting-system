<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Core\Auth;
use Core\Csrf;
use Core\Mailer;
use Core\Router;
use Core\Validator;
use Core\View;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Router::redirect(Auth::role() === 'admin' ? '/admin/dashboard' : '/voter/dashboard');
        }
        View::render('auth/login');
    }

    public function login(): void
    {
        if (!Csrf::verifyRequest()) {
            View::flash('error', 'Your session expired, please try again.');
            Router::redirect('/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = Auth::attempt($email, $password);

        if (!$user) {
            View::flash('error', 'Incorrect email/password, or your account is not yet approved.');
            $_SESSION['old'] = ['email' => $email];
            Router::redirect('/login');
        }

        AuditLog::record((int) $user['id'], 'login');
        Router::redirect($user['role'] === 'admin' ? '/admin/dashboard' : '/voter/dashboard');
    }

    public function logout(): void
    {
        AuditLog::record(Auth::id(), 'logout');
        Auth::logout();
        Router::redirect('/login');
    }

    public function showRegister(): void
    {
        View::render('auth/register');
    }

    public function register(): void
    {
        if (!Csrf::verifyRequest()) {
            Router::redirect('/register');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
        ];

        $validator = new Validator($data);
        $validator->required('name')
            ->required('email')->email('email')->unique('email', 'users', 'email')
            ->required('password')->minLength('password', 8)
            ->matches('password_confirm', 'password', 'Passwords do not match.');

        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->firstErrors();
            $_SESSION['old'] = $data;
            Router::redirect('/register');
        }

        $id = User::createSelfRegistration($data['name'], $data['email'], $data['password']);
        AuditLog::record($id, 'self_registered');

        View::flash('success', 'Registration submitted. An admin needs to approve your account before you can log in.');
        Router::redirect('/login');
    }

    public function showForgotPassword(): void
    {
        View::render('auth/forgot_password');
    }

    public function forgotPassword(): void
    {
        if (!Csrf::verifyRequest()) {
            Router::redirect('/forgot-password');
        }

        $email = trim($_POST['email'] ?? '');
        $user = User::findByEmail($email);

        // Same message whether or not the account exists, so this can't be
        // used to enumerate registered emails.
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            User::setResetToken((int) $user['id'], $token, $expiresAt);

            $resetUrl = Router::url('/reset-password?token=' . urlencode($token));
            Mailer::send(
                $user['email'],
                $user['name'],
                'Reset your Online Voting System password',
                "<p>Click the link below to reset your password (valid for 1 hour):</p><p><a href=\"{$resetUrl}\">{$resetUrl}</a></p>"
            );
            AuditLog::record((int) $user['id'], 'password_reset_requested');
        }

        View::flash('success', 'If that email is registered, a reset link has been sent.');
        Router::redirect('/login');
    }

    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        $user = $token !== '' ? User::findByResetToken($token) : null;

        if (!$user) {
            View::flash('error', 'This reset link is invalid or has expired.');
            Router::redirect('/login');
        }

        View::render('auth/reset_password', ['token' => $token]);
    }

    public function resetPassword(): void
    {
        if (!Csrf::verifyRequest()) {
            Router::redirect('/login');
        }

        $token = $_POST['token'] ?? '';
        $user = User::findByResetToken($token);
        if (!$user) {
            View::flash('error', 'This reset link is invalid or has expired.');
            Router::redirect('/login');
        }

        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        if (strlen($password) < 8 || $password !== $confirm) {
            View::flash('error', 'Passwords must match and be at least 8 characters.');
            Router::redirect('/reset-password?token=' . urlencode($token));
        }

        User::resetPassword((int) $user['id'], $password);
        AuditLog::record((int) $user['id'], 'password_reset');

        View::flash('success', 'Password updated. You can now log in.');
        Router::redirect('/login');
    }
}
