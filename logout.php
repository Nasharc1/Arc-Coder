<?php
require_once 'includes/auth.php';

// Destroy session and redirect
session_destroy();
header('Location: login.php?message=logged_out');
exit;
?>