<?php
declare(strict_types=1);

/**
 * CSRF protection (session token)
 * - Token stored in $_SESSION['csrf_token']
 * - Forms must send it via POST field: csrf_token
 */

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Session not started. Call session_start() before csrf_token().');
    }

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

function csrf_verify(?string $tokenFromPost = null): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        http_response_code(500);
        exit('Session not started');
    }

    $tokenFromPost = $tokenFromPost ?? ($_POST['csrf_token'] ?? null);

    if (!is_string($tokenFromPost) || $tokenFromPost === '') {
        http_response_code(403);
        exit('CSRF token missing');
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $tokenFromPost)) {
        http_response_code(403);
        exit('CSRF token invalid');
    }
}