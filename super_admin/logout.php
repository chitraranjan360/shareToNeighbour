<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/super_admin_auth.php';

superAdminLogout();
header('Location: ' . Super_admin_URL. '/login.php');
exit;