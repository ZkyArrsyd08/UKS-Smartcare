<?php
session_start();

// hapus semua session
$_SESSION = [];

// destroy session
session_destroy();

// hapus cookie juga
setcookie('role', '', time() - 3600, "/");

// redirect ke halaman awal
header("Location: ../index.php");
exit;