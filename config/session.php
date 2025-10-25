<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Check if user is farmer
function isFarmer() {
    return isLoggedIn() && $_SESSION['user_type'] === 'farmer';
}

// Check if user is buyer
function isBuyer() {
    return isLoggedIn() && $_SESSION['user_type'] === 'buyer';
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /farmer_auction/login.php');
        exit();
    }
}

// Redirect to appropriate dashboard
function redirectToDashboard() {
    if (isFarmer()) {
        header('Location: /farmer_auction/farmer/dashboard.php');
    } elseif (isBuyer()) {
        header('Location: /farmer_auction/buyer/dashboard.php');
    } else {
        header('Location: /farmer_auction/index.php');
    }
    exit();
}
?>
