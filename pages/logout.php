<?php
/**
 * pages/logout.php
 * Menghancurkan session dan mengarahkan kembali ke halaman login
 */
require_once __DIR__ . '/../config/session.php';

$_SESSION = [];
session_destroy();

header('Location: ../index.php');
exit;
