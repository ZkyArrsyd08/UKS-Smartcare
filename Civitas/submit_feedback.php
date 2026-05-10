<?php
require_once '../koneksi.php';

header('Content-Type: application/json');

// Cek jika request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil data JSON dari body request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validasi sederhana
    if (isset($data['rating']) && isset($data['pesan'])) {
        
        $rating = mysqli_real_escape_string($conn, $data['rating']);
        $kategori = mysqli_real_escape_string($conn, $data['kategori']);
        $pesan = mysqli_real_escape_string($conn, $data['pesan']);

        // Query Insert
        $query = "INSERT INTO feedback (rating, kategori, pesan) VALUES ('$rating', '$kategori', '$pesan')";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['status' => 'success', 'message' => 'Feedback berhasil dikirim']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid']);
}
?>