<?php
$host = "localhost";
$user = "root";
$pass = ""; // default Laragon kosong
$db   = "db_ukssmartcare";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>