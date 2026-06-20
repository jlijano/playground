<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$lockPath = __DIR__ . '/includes/installed.lock';
if (!is_file($lockPath)) {
    http_response_code(409);
    echo 'Run install.php first, then run this test-user seeder.';
    exit;
}

$seedToken = $_GET['token'] ?? '';
$expectedToken = substr(hash('sha256', basename(__FILE__) . __DIR__), 0, 12);

if ($seedToken !== $expectedToken) {
    http_response_code(403);
    echo 'Seeder locked. Add ?token=' . htmlspecialchars($expectedToken, ENT_QUOTES, 'UTF-8') . ' to the URL to create test users. Delete this file after testing.';
    exit;
}

$users = [
    ['admin@oligarchyservices.test', 'Test Admin', 'admin'],
    ['client@oligarchyservices.test', 'Test Client', 'client'],
    ['it@oligarchyservices.test', 'Test IT', 'it'],
    ['guest@oligarchyservices.test', 'Test Guest', 'guest'],
];

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 1) '
        . 'ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), full_name = VALUES(full_name), role = VALUES(role), is_active = 1, updated_at = NOW()'
    );

    foreach ($users as [$email, $fullName, $role]) {
        $stmt->execute([
            $email,
            password_hash('123', PASSWORD_DEFAULT),
            $fullName,
            $role,
        ]);
    }
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Seed failed: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Seed Test Users | Oligarchy Services</title>
    <link rel="stylesheet" href="/assets/styles.css?v=20260618-service-icons">
    <link rel="stylesheet" href="/assets/login.css?v=20260620-php-install">
  </head>
  <body>
    <main class="login-page">
      <section class="login-hero" aria-labelledby="seed-heading">
        <a class="login-brand-logo" href="/" aria-label="Oligarchy Services home">OLIGARCHY</a>
        <div class="login-panel">
          <div class="login-panel-heading">
            <p class="eyebrow">Development only</p>
            <h1 id="seed-heading">Test users created</h1>
            <p>These temporary accounts were added or refreshed. Delete <code>seed-test-users.php</code> after testing.</p>
          </div>
          <div class="form-alert is-visible is-success">Password for every test account: <strong>123</strong></div>
          <ul class="login-note">
            <li><strong>Admin:</strong> admin@oligarchyservices.test</li>
            <li><strong>Client:</strong> client@oligarchyservices.test</li>
            <li><strong>IT:</strong> it@oligarchyservices.test</li>
            <li><strong>Guest:</strong> guest@oligarchyservices.test</li>
          </ul>
          <p class="login-note"><a href="/login-test.php">Open dev login page</a></p>
        </div>
      </section>
    </main>
  </body>
</html>
