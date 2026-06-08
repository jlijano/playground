<?php

declare(strict_types=1);

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $statusCode = 400, array $details = []): void
{
    jsonResponse([
        'success' => false,
        'error' => [
            'message' => $message,
            'details' => $details,
        ],
    ], $statusCode);
}

function jsonSuccess(array $data = [], int $statusCode = 200): void
{
    jsonResponse([
        'success' => true,
        'data' => $data,
    ], $statusCode);
}
