<?php
/**
 * ==============================================================
 * Admin Logout (admin/logout.php)
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

logout_user();
set_flash('info', 'Command center session locked.');
header("Location: " . BASE_URL . "/admin/login.php");
exit;
