<?php

define('BASE_URL', '/oldboys');

function url(string $path): string {
    return BASE_URL . $path;
}

function isLoggedIn(): bool {
    return isset($_SESSION['member_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . url('/auth/login.php'));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'You do not have permission to access that page.';
        header('Location: ' . url('/index.php'));
        exit;
    }
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('j M Y', $timestamp);
}

function excerpt(string $text, int $length = 200): string {
    if (strlen($text) <= $length) return $text;
    $break = strpos($text, ' ', $length);
    if ($break === false) return $text;
    return substr($text, 0, $break) . '...';
}

function redirectWith(string $location, string $message, string $type = 'success'): void {
    $_SESSION[$type] = $message;
    header("Location: " . url($location));
    exit;
}
