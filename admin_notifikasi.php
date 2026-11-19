<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['username']) || empty($_SESSION['password'])) {
    http_response_code(401);
    echo json_encode(array('error' => 'unauthorized'));
    exit;
}

include 'koneksi.php';
include 'proses_pengajuan_otomatis.php';

$result = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM meminjam WHERE status = 'Diajukan'");
$total = $result ? (int) mysqli_fetch_assoc($result)['total'] : 0;
echo json_encode(array('pending' => $total));
?>
