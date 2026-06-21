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

function account_confirmation_generate_temporary_password(int $length = 16): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%*-_';
    $max = strlen($alphabet) - 1;
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $max)];
    }

    return $password;
}

function account_confirmation_is_new_dashboard_user_request(): bool
{
    return (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST')
        && trim((string) ($_POST['action'] ?? '')) === 'save_user'
        && (int) ($_POST['user_id'] ?? 0) === 0;
}

function account_confirmation_prepare_dashboard_create(): void
{
    if (!account_confirmation_is_new_dashboard_user_request()) {
        return;
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $temporaryPassword = account_confirmation_generate_temporary_password();
    $_POST['password'] = $temporaryPassword;
    $_SESSION['account_confirmation_temporary_password'] = [
        'email' => $email,
        'password' => $temporaryPassword,
    ];
}

function account_confirmation_html_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function account_confirmation_send_email(string $email, string $name, string $token, string $temporaryPassword): bool
{
    $confirmUrl = account_confirmation_url('/account-confirmation.php?token=' . rawurlencode($token));
    $loginUrl = account_confirmation_url('/login.html');
    $displayName = $name !== '' ? $name : 'there';
    $safeDisplayName = account_confirmation_html_escape($displayName);
    $safeConfirmUrl = account_confirmation_html_escape($confirmUrl);
    $safeLoginUrl = account_confirmation_html_escape($loginUrl);
    $safeTemporaryPassword = account_confirmation_html_escape($temporaryPassword);
    $subject = 'Confirm your Oligarchy Services account';
    $brandLogoUrl = trim((string) getenv('PORTAL_LOGO_URL'));
    $footerLogo = '<div style="font-size:22px;line-height:1;font-weight:800;letter-spacing:2px;color:#b00714;text-transform:uppercase;"><span style="display:inline-block;width:17px;height:17px;border-radius:50%;background:#b00714;margin-right:7px;vertical-align:-2px;"></span>OLIGARCHY</div>';
    if ($brandLogoUrl !== '' && preg_match('#^https?://#i', $brandLogoUrl)) {
        $safeLogoUrl = account_confirmation_html_escape($brandLogoUrl);
        $footerLogo = '<img src="' . $safeLogoUrl . '" alt="Oligarchy Services" width="190" style="display:block;width:190px;max-width:100%;height:auto;border:0;margin:0 auto 6px;">';
    }

    $body = '<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Confirm your Oligarchy Services account</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#111827;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">Your Oligarchy Services account is ready. Confirm your email and use the temporary password to sign in.</div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f4f6f8;margin:0;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 12px 32px rgba(17,24,39,.08);">
          <tr>
            <td style="background:#1f2328;padding:28px 30px;text-align:left;">
              <div style="font-size:24px;line-height:1;font-weight:800;letter-spacing:2px;color:#b00714;text-transform:uppercase;"><span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:#b00714;margin-right:8px;vertical-align:-2px;"></span>OLIGARCHY</div>
              <p style="margin:12px 0 0;color:#d1d5db;font-size:14px;line-height:1.5;">Client Portal Account Confirmation</p>
            </td>
          </tr>
          <tr>
            <td style="padding:34px 30px 12px;">
              <p style="margin:0 0 14px;font-size:16px;line-height:1.6;">Hi ' . $safeDisplayName . ',</p>
              <h1 style="margin:0 0 14px;font-size:28px;line-height:1.2;color:#111827;">Your account has been created.</h1>
              <p style="margin:0 0 22px;font-size:16px;line-height:1.65;color:#374151;">Confirm your email address first. After confirmation, sign in with the temporary password below. You will be required to create your own password on the first login before opening the dashboard.</p>

              <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin:0 0 22px;">
                <p style="margin:0 0 8px;color:#6b7280;font-size:13px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">Temporary password</p>
                <div style="font-family:Consolas,Monaco,monospace;font-size:20px;line-height:1.4;color:#111827;background:#ffffff;border:1px dashed #b00714;border-radius:10px;padding:13px 14px;word-break:break-all;">' . $safeTemporaryPassword . '</div>
                <p style="margin:10px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">Keep this temporary password private. It is only for your first sign-in.</p>
              </div>

              <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 18px;">
                <tr>
                  <td style="border-radius:999px;background:#b00714;">
                    <a href="' . $safeConfirmUrl . '" style="display:inline-block;padding:14px 22px;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;border-radius:999px;">Confirm Account</a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#6b7280;">If the button does not work, copy and paste this confirmation link into your browser:<br><a href="' . $safeConfirmUrl . '" style="color:#b00714;word-break:break-all;">' . $safeConfirmUrl . '</a></p>

              <div style="border-top:1px solid #e5e7eb;margin:24px 0 0;padding:20px 0 0;">
                <p style="margin:0 0 6px;font-size:14px;line-height:1.6;color:#374151;"><strong>Login page:</strong> <a href="' . $safeLoginUrl . '" style="color:#b00714;">' . $safeLoginUrl . '</a></p>
                <p style="margin:0;font-size:13px;line-height:1.6;color:#6b7280;">This confirmation link expires in 48 hours.</p>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding:26px 30px 30px;text-align:center;background:#fbfbfc;border-top:1px solid #e5e7eb;">
              ' . $footerLogo . '
              <p style="margin:10px 0 0;color:#6b7280;font-size:12px;line-height:1.5;">Oligarchy Services Client Portal</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

    $headers = [
        'From: ' . account_confirmation_from_header(),
        'Reply-To: ' . account_confirmation_from_header(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
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
    account_confirmation_prepare_dashboard_create();
    register_shutdown_function('account_confirmation_finalize_dashboard_create');
}

function account_confirmation_finalize_dashboard_create(): void
{
    if (!account_confirmation_is_new_dashboard_user_request()) {
        return;
    }
    if (!empty($_SESSION['dashboard_error'])) {
        unset($_SESSION['account_confirmation_temporary_password']);
        return;
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        unset($_SESSION['account_confirmation_temporary_password']);
        return;
    }

    $temporaryPasswordPayload = $_SESSION['account_confirmation_temporary_password'] ?? null;
    unset($_SESSION['account_confirmation_temporary_password']);
    $temporaryPassword = '';
    if (is_array($temporaryPasswordPayload) && ($temporaryPasswordPayload['email'] ?? '') === $email) {
        $temporaryPassword = (string) ($temporaryPasswordPayload['password'] ?? '');
    }
    if ($temporaryPassword === '') {
        $temporaryPassword = account_confirmation_generate_temporary_password();
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
        $update = $pdo->prepare('UPDATE users SET password_hash = ?, email_confirmation_token_hash = ?, email_confirmation_expires_at = DATE_ADD(NOW(), INTERVAL 2 DAY), password_change_required = 1, updated_at = NOW() WHERE id = ?');
        $update->execute([password_hash($temporaryPassword, PASSWORD_DEFAULT), $tokenHash, (int) $createdUser['id']]);

        if (account_confirmation_send_email($email, (string) ($createdUser['full_name'] ?? ''), $token, $temporaryPassword)) {
            $_SESSION['dashboard_notice'] = 'User created. Confirmation email with a temporary password sent to ' . $email . '.';
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
