<?php
session_start();
unset($_SESSION['admin_id']);
session_regenerate_id(true);
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'admin_login.php'));
exit;
