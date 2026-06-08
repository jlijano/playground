<?php

declare(strict_types=1);

function requireAuthenticatedUser(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['user'])) {
        require_once __DIR__ . '/response.php';
        jsonError('Authentication required.', 401);
    }

    return $_SESSION['user'];
}
