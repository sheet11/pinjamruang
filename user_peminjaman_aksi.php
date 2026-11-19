<?php
include 'koneksi.php';
include 'proses_pengajuan_otomatis.php';

if (!isset($_POST['submit'])) {
    header("Location: user_peminjaman.php");
    exit;
}

$id_ruang = (int) ($_POST['id_ruang'] ?? 0);
$nama_peminjam = trim($_POST['nama_peminjam'] ?? '');
$instansi_peminjam = trim($_POST['instansi_peminjam'] ?? '');
$nama_kegiatan = trim($_POST['nama_kegiatan'] ?? '');
$start_date = trim($_POST['start_date'] ?? '');
$end_date = trim($_POST['end_date'] ?? '');
$jumlah_mahasiswa = (int) ($_POST['jumlah_mahasiswa'] ?? 0);
$status = 'Diajukan';
$color = '#28a745';

if ($id_ruang < 1 || $nama_peminjam === '' || $instansi_peminjam === '' || $nama_kegiatan === '' || $start_date === '' || $end_date === '' || $jumlah_mahasiswa < 1) {
    header("Location: user_peminjaman_ruangan.php?id=".$id_ruang."&error=data");
    exit;
}

$start = DateTime::createFromFormat('Y-m-d H:i', $start_date);
$end = DateTime::createFromFormat('Y-m-d H:i', $end_date);
if (!$start || !$end || $end <= $start) {
    header("Location: user_peminjaman_ruangan.php?id=".$id_ruang."&error=waktu");
    exit;
}

if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
    header("Location: user_peminjaman_ruangan.php?id=".$id_ruang."&error=tanggal");
    exit;
}

$now = new DateTime();
if ($start < $now) {
    header("Location: user_peminjaman_ruangan.php?id=".$id_ruang."&error=waktu");
    exit;
}

$conflictStmt = mysqli_prepare(
    $koneksi,
    "SELECT kode_pinjam, status, start_date, end_date FROM meminjam
     WHERE id_ruang = ?
       AND status IN ('Diajukan', 'Disetujui')
       AND start_date < ?
       AND end_date > ?
      ORDER BY (status = 'Disetujui') DESC, start_date ASC"
);
$startDb = $start->format('Y-m-d H:i');
$endDb = $end->format('Y-m-d H:i');
mysqli_stmt_bind_param($conflictStmt, 'iss', $id_ruang, $endDb, $startDb);
mysqli_stmt_execute($conflictStmt);
$conflictResult = mysqli_stmt_get_result($conflictStmt);
$conflicts = array();
while ($conflictRow = mysqli_fetch_assoc($conflictResult)) {
    $conflicts[] = $conflictRow;
}
mysqli_stmt_close($conflictStmt);

foreach ($conflicts as $conflict) {
    if ($conflict['status'] === 'Disetujui') {
        $jam = date('H:i', strtotime($conflict['start_date'])) . '–' . date('H:i', strtotime($conflict['end_date']));
        header("Location: user_peminjaman_ruangan.php?id=".$id_ruang."&error=disetujui&jam=" . urlencode($jam));
        exit;
    }
}

// Pengajuan yang masih Diajukan boleh tetap diajukan, tetapi pengguna diberi tahu.
$pendingConflict = null;
foreach ($conflicts as $conflict) {
    if ($conflict['status'] === 'Diajukan') {
        $pendingConflict = $conflict;
        break;
    }
}

if ($pendingConflict) {
    // Nilai ini diteruskan agar halaman hasil dapat memberi informasi tambahan.
    $pendingJam = date('H:i', strtotime($pendingConflict['start_date'])) . '–' . date('H:i', strtotime($pendingConflict['end_date']));
} else {
    $pendingJam = '';
}

if ($pendingJam !== '') {
    // Tidak menghentikan pengajuan; keterangan ditampilkan setelah data berhasil disimpan.
    $pendingNotice = $pendingJam;
} else {
    $pendingNotice = '';
}

$roomStmt = mysqli_prepare($koneksi, "SELECT nama_ruang, status FROM ruangan WHERE id_ruang = ?");
mysqli_stmt_bind_param($roomStmt, 'i', $id_ruang);
mysqli_stmt_execute($roomStmt);
$roomResult = mysqli_stmt_get_result($roomStmt);
$room = mysqli_fetch_assoc($roomResult);
mysqli_stmt_close($roomStmt);

if (!$room || $room['status'] !== 'Tersedia') {
    header("Location: user_peminjaman.php");
    exit;
}

$nama_ruang = $room['nama_ruang'];
$tgl_pinjam = date('Y-m-d H:i:s');
$insert = mysqli_prepare($koneksi, "INSERT INTO meminjam (id_ruang, nama_peminjam, instansi_peminjam, nama_ruang, nama_kegiatan, tgl_pinjam, start_date, end_date, status, jumlah_mahasiswa, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param(
    $insert,
    'issssssssis',
    $id_ruang,
    $nama_peminjam,
    $instansi_peminjam,
    $nama_ruang,
    $nama_kegiatan,
    $tgl_pinjam,
    $start_date,
    $end_date,
    $status,
    $jumlah_mahasiswa,
    $color
);

if (mysqli_stmt_execute($insert)) {
    mysqli_stmt_close($insert);
    $noticeQuery = $pendingNotice !== '' ? '&pending_jam=' . urlencode($pendingNotice) : '';
    header("Location: user_peminjaman_ruangan.php?id=".$id_ruang."&success=1" . $noticeQuery);
    exit;
}

mysqli_stmt_close($insert);
header("Location: user_peminjaman_ruangan.php?id=".$id_ruang."&error=simpan");
exit;
?>
