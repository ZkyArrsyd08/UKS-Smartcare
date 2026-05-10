<?php
// 1. Mulai sesi
session_start();

// 2. Hapus semua data sesi
$_SESSION = array();

// 3. Hancurkan sesi di server
session_destroy();

// 4. Tendang balik ke halaman login
header("Location: ../index.php");
exit;
?>