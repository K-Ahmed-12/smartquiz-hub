<?php
require_once 'config/config.php';

// Clear all session data
session_destroy();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page with success message
showAlert('You have been successfully logged out.', 'success');
redirect('login.php');
?>
