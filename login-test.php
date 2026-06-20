<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/csrf.php';

$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Development Login | Oligarchy Services</title>
    <meta name="description" content="Temporary development login for Oligarchy Services portal testing.">
    <link rel="stylesheet" href="/assets/styles.css?v=20260618-service-icons">
    <link rel="stylesheet" href="/assets/login.css?v=20260620-php-login">
  </head>
  <body>
    <main class="login-page">
      <section class="login-hero" aria-labelledby="login-heading">
        <a class="login-brand-logo" href="/" aria-label="Oligarchy Services home">OLIGARCHY</a>
        <form class="login-panel" id="client-login-form" action="/api/login.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="login-panel-heading">
            <p class="eyebrow">Development portal</p>
            <h1 id="login-heading">Test login</h1>
            <p>Temporary testing page for seeded development users.</p>
          </div>

          <div class="form-alert<?= $loginError !== '' ? ' is-visible is-error' : '' ?>" id="login-message" role="status" aria-live="polite"><?= e($loginError) ?></div>

          <label class="field" for="email">
            <span>Email address</span>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="username" placeholder="admin@oligarchyservices.test" required>
          </label>

          <label class="field" for="password">
            <span>Password</span>
            <span class="password-control">
              <input id="password" name="password" type="password" autocomplete="current-password" minlength="3" required>
              <button class="password-toggle" type="button" aria-controls="password" aria-pressed="false" onclick="const p=document.getElementById('password'); const show=p.type==='password'; p.type=show?'text':'password'; this.textContent=show?'Hide':'Show'; this.setAttribute('aria-pressed', String(show));">Show</button>
            </span>
          </label>

          <button class="button primary login-submit" type="submit">Sign in</button>
          <p class="login-note">Temporary test accounts use password <strong>123</strong>. Delete this page before production launch.</p>
        </form>
      </section>
    </main>
  </body>
</html>
