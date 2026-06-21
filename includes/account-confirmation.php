<?php
declare(strict_types=1);

function account_confirmation_base_url(): string
{
    foreach (['PORTAL_BASE_URL', 'APP_URL'] as $key) {
        $value = trim((string) getenv($key));
        if ($value !== '' && preg_match('#^https?://#i', $value)) {
            return rtrim($value, '/');
        }
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'sandbox.oligarchyservices.com'));
    $host = preg_replace('/[^a-z0-9.\-:]/', '', $host) ?: 'sandbox.oligarchyservices.com';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    return ($https ? 'https://' : 'http://') . $host;
}

function account_confirmation_url(string $path): string
{
    return account_confirmation_base_url() . '/' . ltrim($path, '/');
}

function account_confirmation_from_header(): string
{
    $from = trim((string) getenv('PORTAL_MAIL_FROM'));
    if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $host = parse_url(account_confirmation_base_url(), PHP_URL_HOST) ?: 'oligarchyservices.com';
        $from = 'no-reply@' . preg_replace('/^www\./', '', strtolower((string) $host));
    }

    return 'Oligarchy Services <' . $from . '>';
}

function account_confirmation_send_email(string $email, string $name, string $token): bool
{
    $confirmUrl = account_confirmation_url('/account-confirmation.php?token=' . rawurlencode($token));
    $loginUrl = account_confirmation_url('/login.html');
    $displayName = $name !== '' ? $name : 'there';
    $subject = 'Confirm your Oligarchy Services account';
    $body = "Hi {$displayName},\n\n"
        . "Your Oligarchy Services account has been created. Confirm your email address before signing in:\n\n"
        . "{$confirmUrl}\n\n"
        . "After confirming, log in here and create your own password before opening the dashboard:\n\n"
        . "{$loginUrl}\n\n"
        . "This confirmation link expires in 48 hours.\n";
    $headers = [
        'From: ' . account_confirmation_from_header(),
        'Reply-To: ' . account_confirmation_from_header(),
        'Content-Type: text/plain; charset=UTF-8',
    ];

    return mail($email, $subject, $body, implode("\r\n", $headers));
}

function account_confirmation_register_dashboard_hook(): void
{
    static $registered = false;
    if ($registered) {
        return;
    }

    $registered = true;
    register_shutdown_function('account_confirmation_finalize_dashboard_create');
}

function account_confirmation_finalize_dashboard_create(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    if (trim((string) ($_POST['action'] ?? '')) !== 'save_user') {
        return;
    }
    if ((int) ($_POST['user_id'] ?? 0) !== 0) {
        return;
    }
    if (!empty($_SESSION['dashboard_error'])) {
        return;
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    try {
        require_once __DIR__ . '/installer.php';
        require_once __DIR__ . '/password-change.php';

        $pdo = db();
        create_or_update_schema($pdo);
        password_change_ensure_schema($pdo);

        $stmt = $pdo->prepare('SELECT id, full_name, email_confirmed_at, email_confirmation_token_hash FROM users WHERE email = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$email]);
        $createdUser = $stmt->fetch();
        if (!$createdUser || !empty($createdUser['email_confirmed_at']) || !empty($createdUser['email_confirmation_token_hash'])) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $update = $pdo->prepare('UPDATE users SET email_confirmation_token_hash = ?, email_confirmation_expires_at = DATE_ADD(NOW(), INTERVAL 2 DAY), password_change_required = 1, updated_at = NOW() WHERE id = ?');
        $update->execute([$tokenHash, (int) $createdUser['id']]);

        if (account_confirmation_send_email($email, (string) ($createdUser['full_name'] ?? ''), $token)) {
            $_SESSION['dashboard_notice'] = 'User created. Confirmation email sent to ' . $email . '.';
        } else {
            unset($_SESSION['dashboard_notice']);
            $_SESSION['dashboard_error'] = 'User created, but the confirmation email could not be sent. Check Hostinger PHP mail settings.';
        }
    } catch (Throwable $error) {
        error_log('Account confirmation setup failed: ' . $error->getMessage());
        unset($_SESSION['dashboard_notice']);
        $_SESSION['dashboard_error'] = 'User created, but account confirmation setup failed. Check the PHP error log.';
    }
}
