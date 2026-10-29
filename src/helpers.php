<?php

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['_flash'] = [
        'type' => $type,
        'msg' => $msg,
    ];
}

function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $message = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);

    return is_array($message) ? $message : null;
}

function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function enumValues(string $columnType): array
{
    if (!preg_match('/^enum\((.*)\)$/i', $columnType, $matches)) {
        return [];
    }

    $definition = $matches[1];
    $length = strlen($definition);
    $values = [];
    $index = 0;

    while ($index < $length) {
        while ($index < $length && (ctype_space($definition[$index]) || $definition[$index] === ',')) {
            $index++;
        }

        if ($index >= $length || $definition[$index] !== "'") {
            return [];
        }

        $index++;
        $value = '';
        $closed = false;

        while ($index < $length) {
            $character = $definition[$index];

            if ($character === '\\') {
                if ($index + 1 >= $length) {
                    return [];
                }

                $escaped = $definition[$index + 1];
                $escapeMap = [
                    '0' => "\0",
                    'b' => "\x08",
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'Z' => "\x1a",
                    '\\' => '\\',
                    "'" => "'",
                    '"' => '"',
                ];
                $value .= $escapeMap[$escaped] ?? $escaped;
                $index += 2;
                continue;
            }

            if ($character === "'") {
                $index++;
                $closed = true;
                break;
            }

            $value .= $character;
            $index++;
        }

        if (!$closed) {
            return [];
        }

        $values[] = $value;
    }

    return $values;
}
