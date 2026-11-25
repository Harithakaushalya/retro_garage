<?php
/**
 * ==============================================================
 * Customer & Admin Logout (logout.php)
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

logout_user();
set_flash('info', 'You have been signed out from the garage system.');
header("Location: " . BASE_URL . "/index.php");
exit;
