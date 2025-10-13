<?php
// auth.php - Authentication functions
require_once 'config.php';
require_once 'lib/functions.php';

function login($username, $password) {
    global $conn;
    
    $username = esc($username);
    $user = get_one("SELECT * FROM users WHERE username = '$username' AND is_active = 1");
    
    if ($user && $password === $user['password']) {  // Changed from password_verify
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        flash('err', 'Access denied. Insufficient permissions.');
        header('Location: index.php');
        exit;
    }
}

function canEdit() {
    return hasRole('admin');
}

function canDelete() {
    return hasRole('admin');
}

function canCreateGRN() {
    return hasRole('admin') || hasRole('manager');
}

function canView() {
    return isLoggedIn();
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name']
    ];
}
?>