<?php

declare(strict_types=1);

/**
 * PDO database connection factory.
 *
 * Configure these values in Hostinger or a server-side local config.
 * Never expose database credentials to browser JavaScript.
 */
function getDatabaseConnection(): PDO
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $database = getenv('DB_NAME') ?: 'playground_db';
    $username = getenv('DB_USER') ?: 'playground_user';
    $password = getenv('DB_PASS') ?: '';

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $host,
        $database
    );

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
