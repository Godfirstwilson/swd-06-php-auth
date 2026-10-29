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
