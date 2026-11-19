<?php 
  session_start();
  include "koneksi.php";
  if (empty($_SESSION['username']) || empty($_SESSION['password'])) {
    header("location:cek_login.php");
    exit;
  }
  include 'proses_pengajuan_otomatis.php';
  $pendingResult = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM meminjam WHERE status = 'Diajukan'");
  $pendingCount = (int) mysqli_fetch_assoc($pendingResult)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <title>[Admin] Menu | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="./style.css" type="text/css" rel="stylesheet">
  <link href="./template.css" type="text/css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <style>
    body{background:#f4f7fb}.navbar{position:sticky;top:0;min-height:64px;background:#fff;box-shadow:0 3px 18px rgba(25,50,77,.08)}.navbar li a{color:#3d5369;padding-top:22px;padding-bottom:22px}.navbar li a:hover{background:#eef5fb;color:#087f8c}.icon-bar{background:#163b63}.admin-notice{max-width:900px;margin:25px auto 0;padding:14px 18px;border-radius:10px;background:#fff8e8;color:#725a22;text-align:left}.admin-badge{display:inline-block;min-width:21px;margin-left:5px;padding:3px 7px;border-radius:12px;background:#e39a22;color:#fff;font-size:11px;font-weight:700;vertical-align:top}.admin-notification-toast{position:fixed;top:78px;right:22px;z-index:2000;display:none;padding:13px 17px;border:1px solid #f0d99b;border-radius:9px;background:#fff8e8;color:#725a22;box-shadow:0 8px 22px rgba(25,50,77,.14);font-size:13px;font-weight:600}.admin-notification-toast.visible{display:block}
  </style>
</head>





<body>
<nav class="navbar">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>                        
      </button>
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        <li class="active"><a href="admin_menu.php">Menu Admin</a></li>
        <li><a href="#">About</a></li>
        <li><a href="#">Contact</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="./admin_pengajuan.php" id="adminNotificationLink"><span class="glyphicon glyphicon-bell"></span> Pengajuan Baru <span id="adminNotificationBadge" class="admin-badge" <?php echo $pendingCount > 0 ? '' : 'style="display:none"'; ?>><?php echo $pendingCount; ?></span></a></li>
        <li><a href="./logout.php"><span class="glyphicon glyphicon-log-out"></span> Log Out</a></li>
      </ul>
    </div>
  </div>
</nav>





<div class="container-fluid text-center">
	<br><br><br><br>
	<h2>Menu Admin Peminjaman Ruangan</h2><br>
	<h3>Poltekkes Kemenkes Bengkulu</h3>
  <h3>Poltekkes Kemenkes Bengkulu</h3><br><br>

  <a href="admin_fasilitas.php" class="btn btn-lg"><span class="badge"></span><br><span class="glyphicon glyphicon-tower"></span><br>Fasilitas</a>
  <a href="./admin_pengajuan.php" class="btn btn-lg"><span class="badge"></span><br><span class="glyphicon glyphicon-folder-open"></span><br>Pengajuan</a>
  <a href="admin_jadwal.php" class="btn btn-lg"><span class="badge"></span><br><span class="glyphicon glyphicon-calendar"></span><br>Jadwal</a>
</div>
<div id="adminNotificationToast" class="admin-notification-toast" role="status" aria-live="polite"></div>
<script>
(function () {
  var currentCount = <?php echo (int) $pendingCount; ?>;
  var audioContext = null;
  var toast = document.getElementById('adminNotificationToast');
  var badge = document.getElementById('adminNotificationBadge');

  function unlockAudio() {
    if (!audioContext) {
      var AudioContext = window.AudioContext || window.webkitAudioContext;
      if (AudioContext) audioContext = new AudioContext();
    }
    if (audioContext && audioContext.state === 'suspended') audioContext.resume();
  }

  function beep() {
    unlockAudio();
    if (!audioContext) return;
    var oscillator = audioContext.createOscillator();
    var gain = audioContext.createGain();
    oscillator.type = 'sine';
    oscillator.frequency.value = 880;
    gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.16, audioContext.currentTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.35);
    oscillator.connect(gain);
    gain.connect(audioContext.destination);
    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.35);
  }

  function showNotification(count) {
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';
    if (count > currentCount) {
      toast.innerHTML = '<span class="glyphicon glyphicon-bell"></span> Ada pengajuan baru yang perlu diproses.';
      toast.classList.add('visible');
      window.setTimeout(function () { toast.classList.remove('visible'); }, 5000);
      beep();
      document.title = '(' + count + ') Pengajuan Baru | Admin';
    }
    currentCount = count;
  }

  document.addEventListener('click', unlockAudio, { once: true });
  window.setInterval(function () {
    fetch('admin_notifikasi.php', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) { if (data && typeof data.pending !== 'undefined') showNotification(Number(data.pending)); })
      .catch(function () {});
  }, 15000);
}());
</script>
</body>
</html>
