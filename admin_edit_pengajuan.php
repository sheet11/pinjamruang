<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

if (empty($_SESSION['username']) || empty($_SESSION['password'])) {
    header('Location: cek_login.php');
    exit;
}

$kode = isset($_GET['kode_pinjam']) ? (int) $_GET['kode_pinjam'] : 0;
$stmt = mysqli_prepare($koneksi, 'SELECT * FROM meminjam WHERE kode_pinjam = ?');
mysqli_stmt_bind_param($stmt, 'i', $kode);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

function adminEditText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminEditDatePart($value, $format) {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date($format, $timestamp) : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <title>Edit Pengajuan #<?php echo $kode; ?> | Admin</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="./style.css" type="text/css" rel="stylesheet">
  <link href="./template.css" type="text/css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>

<body class="booking-page">
<nav class="navbar">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="admin_menu.php"><span class="glyphicon glyphicon-home"></span> &nbsp;Admin Peminjaman</a>
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        <li><a href="admin_menu.php">Menu Admin</a></li>
        <li class="active"><a href="admin_pengajuan.php">Pengajuan</a></li>
        <li><a href="admin_jadwal.php">Jadwal</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="logout.php"><span class="glyphicon glyphicon-log-out"></span> Keluar</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="booking-shell">
  <a class="booking-back" href="admin_pengajuan.php"><span class="glyphicon glyphicon-menu-left"></span> Kembali ke pengajuan</a>

  <?php if (!$booking): ?>
    <div class="booking-error" role="alert">Data pengajuan tidak ditemukan.</div>
  <?php else: ?>
  <header class="booking-heading">
    <h1>Edit Pengajuan Peminjaman</h1>
    <p>Periksa data pemohon, jadwal, dan status sebelum menyimpan perubahan.</p>
  </header>

  <div class="booking-grid">
    <section class="booking-card" aria-label="Form edit pengajuan">
      <form method="post" action="admin_prosespinjam.php" id="adminBookingForm">
        <input type="hidden" name="kode_pinjam" value="<?php echo (int) $booking['kode_pinjam']; ?>">

        <div class="form-group">
          <label for="nama">Nama Peminjam</label>
          <input type="text" class="form-control" id="nama" name="nama" value="<?php echo adminEditText($booking['nama_peminjam']); ?>" required maxlength="255">
        </div>

        <div class="form-group">
          <label for="instansi">Nama Instansi / Unit</label>
          <input type="text" class="form-control" id="instansi" name="instansi" value="<?php echo adminEditText($booking['instansi_peminjam']); ?>" required maxlength="50">
        </div>

        <div class="form-group">
          <label for="ruang">Ruangan</label>
          <input type="text" class="form-control" id="ruang" name="ruang" value="<?php echo adminEditText($booking['nama_ruang']); ?>" readonly>
        </div>

        <div class="form-group">
          <label for="kegiatan">Nama Kegiatan</label>
          <input type="text" class="form-control" id="kegiatan" name="kegiatan" value="<?php echo adminEditText($booking['nama_kegiatan']); ?>" required maxlength="255">
        </div>

        <div class="form-group">
          <label>Waktu Mulai</label>
          <div class="booking-time-grid">
            <input type="date" id="start_date_input" class="form-control" aria-label="Tanggal mulai" value="<?php echo adminEditDatePart($booking['start_date'], 'Y-m-d'); ?>" required>
            <input type="time" id="start_time_input" class="form-control" aria-label="Jam mulai" value="<?php echo adminEditDatePart($booking['start_date'], 'H:i'); ?>" step="1800" required>
          </div>
          <input type="hidden" name="mulai" id="mulai">
        </div>

        <div class="form-group">
          <label>Waktu Selesai</label>
          <div class="booking-time-grid">
            <input type="date" id="end_date_input" class="form-control" aria-label="Tanggal selesai" value="<?php echo adminEditDatePart($booking['end_date'], 'Y-m-d'); ?>" required>
            <input type="time" id="end_time_input" class="form-control" aria-label="Jam selesai" value="<?php echo adminEditDatePart($booking['end_date'], 'H:i'); ?>" step="1800" required>
          </div>
          <input type="hidden" name="selesai" id="selesai">
        </div>

        <div class="booking-duration" id="adminBookingDuration" aria-live="polite">
          <span class="glyphicon glyphicon-time"></span>
          <span>Durasi akan dihitung dari waktu mulai dan selesai.</span>
        </div>

        <div class="form-group">
          <label for="jumlah">Jumlah Peserta</label>
          <input type="number" class="form-control" id="jumlah" name="jumlah" value="<?php echo (int) $booking['jumlah_mahasiswa']; ?>" required min="1" max="9999">
        </div>

        <div class="booking-actions">
          <a class="btn btn-default" href="admin_pengajuan.php">Batal</a>
          <button type="submit" class="btn btn-primary" name="update" value="1">
            <span class="glyphicon glyphicon-floppy-disk"></span> Simpan Perubahan
          </button>
        </div>
      </form>
    </section>

    <aside class="room-summary admin-booking-summary" aria-label="Kontrol admin">
      <div class="room-summary-icon"><span class="glyphicon glyphicon-edit"></span></div>
      <h2>Kontrol Admin</h2>
      <p>Perbarui status pengajuan sesuai hasil pemeriksaan.</p>
      <div class="admin-booking-meta">
        <span>Nomor pengajuan</span>
        <strong>#<?php echo (int) $booking['kode_pinjam']; ?></strong>
      </div>
      <div class="admin-booking-meta">
        <span>Ruangan</span>
        <strong><?php echo adminEditText($booking['nama_ruang']); ?></strong>
      </div>
      <div class="form-group admin-status-field">
        <label for="status">Status Pengajuan</label>
        <select class="form-control" id="status" name="status" form="adminBookingForm" required>
          <?php foreach (array('Diajukan', 'Disetujui', 'Selesai', 'Batal') as $option): ?>
            <option value="<?php echo $option; ?>" <?php echo $booking['status'] === $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="admin-booking-note">
        <span class="glyphicon glyphicon-info-sign"></span>
        Perubahan status akan terlihat pada kalender dan daftar pengajuan.
      </div>
    </aside>
  </div>
  <?php endif; ?>
</main>

<script>
  $(function () {
    function updateDuration() {
      var startValue = $('#start_date_input').val() + 'T' + $('#start_time_input').val();
      var endValue = $('#end_date_input').val() + 'T' + $('#end_time_input').val();
      var start = new Date(startValue);
      var end = new Date(endValue);
      var duration = $('#adminBookingDuration');

      $('#mulai').val(startValue.replace('T', ' '));
      $('#selesai').val(endValue.replace('T', ' '));

      if (isNaN(start.getTime()) || isNaN(end.getTime())) {
        duration.html('<span class="glyphicon glyphicon-time"></span><span>Pilih tanggal dan jam untuk menghitung durasi.</span>');
        return;
      }

      var milliseconds = end.getTime() - start.getTime();
      if (milliseconds <= 0) {
        duration.html('<span class="glyphicon glyphicon-warning-sign"></span><span><strong>Waktu selesai harus setelah waktu mulai.</strong></span>');
        return;
      }

      var totalMinutes = Math.floor(milliseconds / 60000);
      var hours = Math.floor(totalMinutes / 60);
      var minutes = totalMinutes % 60;
      var text = hours ? hours + ' jam' : '';
      if (minutes) text += (text ? ' ' : '') + minutes + ' menit';
      duration.html('<span class="glyphicon glyphicon-time"></span><span>Durasi peminjaman: <strong>' + text + '</strong></span>');
    }

    function syncDateTimeFields() {
      updateDuration();
    }

    $('#start_date_input, #start_time_input, #end_date_input, #end_time_input').on('change', syncDateTimeFields);
    $('#adminBookingForm').on('submit', function (event) {
      syncDateTimeFields();
      var start = new Date($('#mulai').val().replace(' ', 'T'));
      var end = new Date($('#selesai').val().replace(' ', 'T'));
      if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
        event.preventDefault();
        alert('Waktu selesai harus setelah waktu mulai.');
      }
    });

    syncDateTimeFields();
  });
</script>
</body>
</html>
