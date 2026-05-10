<?php
// check_status.php
session_start();
include '../koneksi.php';

 $request_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($request_id) {
    $query = "SELECT status_permintaan FROM permintaan_obat WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['status' => $row['status_permintaan']]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
} else {
    echo json_encode(['status' => 'invalid']);
}