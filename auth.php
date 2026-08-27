<?php
// auth.php - Global Security & Authentication Middleware

// 1. Start the session safely if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Boot unauthenticated users to the login screen
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 3. Ensure the database connection is available
require_once 'db.php';

// 4. Fetch the user's latest data from the database
$auth_stmt = $pdo->prepare("SELECT id, username, email, role, status, language_preference FROM users WHERE id = ?");
$auth_stmt->execute([$_SESSION['user_id']]);
$current_user = $auth_stmt->fetch(PDO::FETCH_ASSOC);

// 5. Instantly destroy the session if the account was banned or deleted
if (!$current_user || $current_user['status'] === 'banned') {
    session_unset();
    session_destroy();
    header("Location: login.php?banned=1");
    exit;
}

// 6. Keep the session role synced with the database
$_SESSION['role'] = $current_user['role'];
?>