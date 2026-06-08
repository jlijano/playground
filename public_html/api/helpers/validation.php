<?php

declare(strict_types=1);

function requiredString(array $input, string $key, int $maxLength = 255): string
{
    $value = trim((string)($input[$key] ?? ''));

    if ($value === '') {
        throw new InvalidArgumentException("$key is required.");
    }

    if (mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException("$key must be {$maxLength} characters or fewer.");
    }

    return $value;
}

function optionalString(array $input, string $key, int $maxLength = 500): ?string
{
    if (!array_key_exists($key, $input) || $input[$key] === null) {
        return null;
    }

    $value = trim((string)$input[$key]);

    if ($value === '') {
        return null;
    }

    if (mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException("$key must be {$maxLength} characters or fewer.");
    }

    return $value;
}
