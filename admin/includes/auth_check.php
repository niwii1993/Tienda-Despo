<?php
// admin/includes/auth_check.php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect to main login with a return url could be nice, but simple for now
    header("Location: ../login.php");
    exit();
}
?>