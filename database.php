<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'cyutfest_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Session helper
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: /index.php?error=unauthorized');
        exit;
    }
}

function currentUser(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return [
        'id'    => $_SESSION['user_id']   ?? null,
        'name'  => $_SESSION['name']      ?? 'Guest',
        'email' => $_SESSION['email']     ?? '',
        'role'  => $_SESSION['role']      ?? 'guest',
    ];
}
