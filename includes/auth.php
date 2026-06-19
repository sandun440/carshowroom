<?php
// includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function hasRole($role) {
    return isset($_SESSION['user']['role_name']) && $_SESSION['user']['role_name'] === $role;
}

// Load user data into session
function loadUserSession($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $_SESSION['user'] = $stmt->fetch();
}
?>