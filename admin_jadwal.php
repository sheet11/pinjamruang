<?php
try
{
  $bdd = new PDO('mysql:host=localhost;dbname=db_jadwal_cbt;charset=utf8', 'root', '');
}
catch(Exception $e)
{
        die('Error gan : '.$e->getMessage());
}

$sql = "SELECT kode_pinjam, nama_peminjam, nama_ruang, nama_kegiatan, start_date, end_date, status, color FROM meminjam ";

$req = $bdd->prepare($sql);
$req->execute();

$meminjam = $req->fetchAll();
$calendarEvents = array();
foreach ($meminjam as $event) {
  $start = $event['start_date'];
  $end = $event['end_date'];
  if (substr($start, 11) === '00:00:00') $start = substr($start, 0, 10);
  if (substr($end, 11) === '00:00:00') $end = substr($end, 0, 10);
  $colors = array('Diajukan' => '#e39a22', 'Disetujui' => '#23865b', 'Selesai' => '#2474c6', 'Batal' => '#788492');
  $calendarEvents[] = array(
    'id' => $event['kode_pinjam'], 'title' => $event['nama_kegiatan'],
    'nama' => $event['nama_peminjam'], 'namaruang' => $event['nama_ruang'],
    'status' => $event['status'], 'start' => $start, 'end' => $end,
    'color' => isset($colors[$event['status']]) ? $colors[$event['status']] : '#788492'
  );
}

  session_start();
  include "koneksi.php";
  if (empty($_SESSION['username']) || empty($_SESSION['password'])) {
    header("location:cek_login.php");
    exit;
  }
  include 'proses_pengajuan_otomatis.php';
  $req = $bdd->prepare($sql);
  $req->execute();
  $meminjam = $req->fetchAll();

  if (isset($_GET['proses'], $_GET['kode_pinjam'], $_GET['status'])) {
    $statusMap = array('1' => array('Disetujui', '#23865b'), '2' => array('Selesai', '#2474c6'), '3' => array('Batal', '#788492'));
    $kode = (int) $_GET['kode_pinjam'];
    $aksi = $_GET['status'];
    if (isset($statusMap[$aksi])) {
      $update = $bdd->prepare('UPDATE meminjam SET status = ?, color = ? WHERE kode_pinjam = ?');
      $update->execute(array($statusMap[$aksi][0], $statusMap[$aksi][1], $kode));
    }
    header('Location: admin_jadwal.php?updated=1');
    exit;
  }

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <title>[Admin] Jadwal | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="./style.css" type="text/css" rel="stylesheet">
  <link href="./template.css" type="text/css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


  <!-- Bootstrap Core CSS >
  <link href="css/bootstrap.min.css" rel="stylesheet"-->
  
  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.3.0/flatpickr.css">

  <!-- FullCalendar -->
  <link href='css/fullcalendar.min.css' rel='stylesheet' />

  <style>
    body { background:#f4f7fb; color:#19324d; }
    .navbar { position:sticky; top:0; min-height:64px; background:#fff; box-shadow:0 3px 18px rgba(25,50,77,.08); }
    .navbar li a { color:#3d5369; padding-top:22px; padding-bottom:22px; }
    .navbar li a:hover { background:#eef5fb; color:#087f8c; }
    .icon-bar { background:#163b63; }
    .admin-schedule { max-width:1120px; margin:0 auto; padding:34px 20px 55px; }
    .admin-intro h1 { margin:0 0 8px; font-size:29px; font-weight:700; color:#193b59; }
    .admin-intro p { margin:0 0 22px; color:#718196; }
    .calendar-panel { padding:22px; border:1px solid #e3eaf1; border-radius:15px; background:#fff; box-shadow:0 7px 22px rgba(25,50,77,.05); }
    #calendar { width:100%; }
    .calendar-legend { display:flex; flex-wrap:wrap; gap:10px 18px; padding:17px 3px 2px; color:#52677c; font-size:13px; }
    .legend-title { width:100%; color:#193b59; }
    .legend-item { display:flex; align-items:center; gap:7px; }
    .legend-dot { width:11px; height:11px; border-radius:50%; }
    .admin-note { margin-top:16px; padding:13px 15px; border-radius:9px; background:#fff8e8; color:#725a22; font-size:13px; line-height:1.6; }
    .fc-toolbar h2 { color:#193b59; font-size:22px; font-weight:700; }
    .fc button { height:auto; padding:7px 11px; border:1px solid #dce5ed; border-radius:7px; background:#fff; color:#35566a; text-shadow:none; box-shadow:none; }
    .fc button:hover, .fc-state-active { background:#eaf6f5!important; color:#087f8c!important; }
    .fc-event { border:0; border-radius:4px; padding:2px 3px; cursor:pointer; }
    .fc-day-header { padding:10px 0!important; color:#66798d; }
    .fc td, .fc th { border-color:#e8edf2; }
    .detail-grid { display:grid; grid-template-columns:145px 1fr; gap:12px; align-items:center; }
    .detail-label { color:#718196; font-size:13px; font-weight:600; }
    .detail-value { color:#193b59; font-weight:600; word-break:break-word; }
    .detail-status { display:inline-block; padding:5px 10px; border-radius:20px; background:#eef3f7; color:#35566a; font-weight:600; }
    .status-actions { display:flex; flex-wrap:wrap; gap:8px; }
    @media(max-width:767px) { .admin-schedule{padding:24px 13px 38px}.calendar-panel{padding:11px 8px}.fc-toolbar{display:flex;flex-wrap:wrap;justify-content:center;gap:8px}.fc-toolbar .fc-left,.fc-toolbar .fc-center,.fc-toolbar .fc-right{float:none;width:100%;text-align:center}.fc-toolbar h2{font-size:19px}.detail-grid{grid-template-columns:1fr;gap:5px}.detail-label{margin-top:8px}.status-actions a{flex:1;text-align:center} }

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
        <li class="active"><a href="./admin_menu.php">Menu Admin</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="./logout.php"><span class="glyphicon glyphicon-log-out"></span> Log Out</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="admin-schedule">
  <header class="admin-intro">
    <h1>Kelola Jadwal Peminjaman</h1>
    <p>Pilih agenda untuk melihat detail dan melakukan tindakan sesuai status pengajuan.</p>
  </header>
  <section class="calendar-panel" aria-label="Kalender administrasi">
    <div id="calendar"></div>
    <div class="calendar-legend">
      <strong class="legend-title">Keterangan status</strong>
      <span class="legend-item"><i class="legend-dot" style="background:#e39a22"></i>Diajukan</span>
      <span class="legend-item"><i class="legend-dot" style="background:#23865b"></i>Disetujui</span>
      <span class="legend-item"><i class="legend-dot" style="background:#2474c6"></i>Selesai</span>
      <span class="legend-item"><i class="legend-dot" style="background:#788492"></i>Batal</span>
    </div>
  </section>
  <div class="admin-note"><strong>Tips:</strong> Klik satu agenda untuk membuka detail. Perubahan status akan langsung tercatat dan terlihat pada kalender.</div>
</main>

<div class="modal fade" id="ModalEdit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
      <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Detail Pengajuan</h4>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-label">Nama Kegiatan</div><div class="detail-value" id="title">-</div>
            <div class="detail-label">Nama Peminjam</div><div class="detail-value" id="nama">-</div>
            <div class="detail-label">Instansi</div><div class="detail-value" id="instansi">-</div>
            <div class="detail-label">Ruangan</div><div class="detail-value" id="nama_ruang">-</div>
            <div class="detail-label">Status</div><div><span id="status" class="detail-status">-</span></div>
            <div class="detail-label">Mulai</div><div class="detail-value" id="start">-</div>
            <div class="detail-label">Selesai</div><div class="detail-value" id="end">-</div>
          </div>
        </div>
        <div class="modal-footer">
        <div class="status-actions" id="statusActions"></div>
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
        </div>
      </div>
      </div>
    </div>

  <!-- Flatpickr -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.3.0/flatpickr.js"></script>
  <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
  <script>
    $("#start").flatpickr({
      enableTime: true,
      //minDate: "today",
      locale: "id",
      //altInput: true,
      //altFormat: "j F Y",
      dateFormat: "Y-m-d H:i",
      time_24hr: true,
      timeFormat: "H:i",
      static: true
  });
    $("#end").flatpickr({
      enableTime: true,
      //minDate: "today",
      locale: "id",
      //altInput: true,
      //altFormat: "j F Y",
      dateFormat: "Y-m-d H:i",
      time_24hr: true,
      timeFormat: "H:i",
      static: true
  });
  </script>

  <!-- FullCalendar -->
  <script src='js/moment.min.js'></script>
  <script src='js/fullcalendar.min.js'></script>
  <script src='js/id.js'></script>
  <script>

  $(document).ready(function() {
    var openDetail = function(event) {
      $('#ModalEdit #title').text(event.title || '-');
      $('#ModalEdit #nama').text(event.nama || '-');
      $('#ModalEdit #nama_ruang').text(event.namaruang || '-');
      $('#ModalEdit #status').text(event.status || '-');
      $('#ModalEdit #start').text(event.start ? event.start.format('D MMMM YYYY, HH:mm') : '-');
      $('#ModalEdit #end').text(event.end ? event.end.format('D MMMM YYYY, HH:mm') : '-');
      var actions = $('#statusActions').empty();
      if (event.status === 'Diajukan') {
        actions.append('<a class="btn btn-success" href="admin_jadwal.php?proses=1&kode_pinjam=' + encodeURIComponent(event.id) + '&status=1">Setujui</a>');
        actions.append('<a class="btn btn-danger" href="admin_jadwal.php?proses=1&kode_pinjam=' + encodeURIComponent(event.id) + '&status=3">Batalkan</a>');
      } else if (event.status === 'Disetujui') {
        actions.append('<a class="btn btn-primary" href="admin_jadwal.php?proses=1&kode_pinjam=' + encodeURIComponent(event.id) + '&status=2">Selesaikan</a>');
        actions.append('<a class="btn btn-danger" href="admin_jadwal.php?proses=1&kode_pinjam=' + encodeURIComponent(event.id) + '&status=3">Batalkan</a>');
      }
      actions.append('<a class="btn btn-warning" href="admin_edit_pengajuan.php?kode_pinjam=' + encodeURIComponent(event.id) + '">Edit Data</a>');
      $('#ModalEdit').modal('show');
    };

    $('#calendar').fullCalendar({
      header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,listMonth' },
      locale: 'id', buttonText: { today: 'Hari ini', month: 'Bulan', week: 'Minggu', list: 'Daftar' },
      height: 'auto', firstDay: 1, navLinks: true, editable: false, eventLimit: true,
      eventClick: function(event) { openDetail(event); },
      eventRender: function(event, element) {
        element.attr({'role':'button', 'tabindex':'0', 'aria-label':'Lihat detail ' + (event.title || 'jadwal')});
        element.off('click.adminDetail').on('click.adminDetail', function(e) {
          e.preventDefault();
          e.stopPropagation();
          openDetail(event);
        });
      },
      events: <?php echo json_encode($calendarEvents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
    });
  });




  </script>
</body>
</html>
