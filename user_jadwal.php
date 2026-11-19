<?php
try
{
  $bdd = new PDO('mysql:host=localhost;dbname=db_jadwal_cbt;charset=utf8', 'root', '');
}
catch(Exception $e)
{
        die('Error gan : '.$e->getMessage());
}

// Sinkronkan status pengajuan tanpa menghapus riwayatnya.
$bdd->exec("UPDATE meminjam
  SET status = 'Selesai', color = '#2474c6'
  WHERE status = 'Disetujui'
    AND ((TIME(end_date) = '00:00:00' AND DATE(end_date) < CURDATE())
      OR (TIME(end_date) <> '00:00:00' AND end_date < NOW()))");
$bdd->exec("UPDATE meminjam
  SET status = 'Batal', color = '#788492',
      keterangan = CASE
        WHEN keterangan IS NULL OR TRIM(keterangan) = '' THEN 'Tidak diproses sampai batas waktu'
        ELSE CONCAT(keterangan, ' | Tidak diproses sampai batas waktu')
      END
  WHERE status = 'Diajukan'
    AND ((TIME(start_date) = '00:00:00' AND DATE(start_date) < CURDATE())
      OR (TIME(start_date) <> '00:00:00' AND start_date < NOW()))");

$sql = "SELECT kode_pinjam, nama_peminjam, instansi_peminjam, nama_ruang, nama_kegiatan, start_date, end_date, status, color FROM meminjam ";

$req = $bdd->prepare($sql);
$req->execute();

$meminjam = $req->fetchAll();

$roomStmt = $bdd->query("SELECT id_ruang, nama_ruang, status FROM ruangan ORDER BY nama_ruang ASC");
$rooms = $roomStmt->fetchAll();

$calendarEvents = array();
foreach ($meminjam as $event) {
  $start = $event['start_date'];
  $end = $event['end_date'];
  if (substr($start, 11) === '00:00:00') $start = substr($start, 0, 10);
  $displayEnd = $end;
  if (substr($end, 11) === '00:00:00') {
    $displayEnd = substr($end, 0, 10);
    $endDate = new DateTime($displayEnd);
    $endDate->modify('+1 day');
    $end = $endDate->format('Y-m-d');
  }
  $statusColors = array('Diajukan' => '#e39a22', 'Disetujui' => '#23865b', 'Selesai' => '#2474c6', 'Batal' => '#788492');
  $calendarEvents[] = array(
    'id' => $event['kode_pinjam'], 'title' => $event['nama_kegiatan'],
    'nama' => $event['nama_peminjam'], 'instansi' => $event['instansi_peminjam'],
    'namaruang' => $event['nama_ruang'], 'status' => $event['status'],
    'start' => $start, 'end' => $end, 'displayEnd' => $displayEnd,
    'color' => isset($statusColors[$event['status']]) ? $statusColors[$event['status']] : '#788492'
  );
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <title>Kalender Peminjaman Ruangan | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="">


  <!-- Bootstrap Core CSS -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="./style.css" type="text/css" rel="stylesheet">
  <link href="./template.css" type="text/css" rel="stylesheet">
  
  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.3.0/flatpickr.css">

  <!-- FullCalendar -->
  <link href='css/fullcalendar.min.css' rel='stylesheet' />

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
        <li><a href="index.php"><span class="glyphicon glyphicon-home"></span> Beranda</a></li>
        <li class="active"><a href="user_jadwal.php"><span class="glyphicon glyphicon-calendar"></span> Kalender</a></li>
        <li><a href="user_peminjaman.php">Ajukan Peminjaman</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="schedule-wrap">
  <header class="schedule-intro">
    <h1>Kalender Peminjaman Ruangan</h1>
    <p>Periksa jadwal sebelum mengajukan peminjaman. Pilih agenda untuk melihat detail ruang dan waktunya.</p>
  </header>
  <div class="schedule-steps" aria-label="Langkah peminjaman"><span>1. Cek jadwal</span><b>›</b><span>2. Pilih waktu kosong</span><b>›</b><span>3. Ajukan peminjaman</span></div>
  <section class="calendar-panel" aria-label="Jadwal ruangan">
    <div id="calendar"></div>
    <div class="calendar-legend" aria-label="Keterangan status">
      <strong class="legend-title">Arti status agenda</strong>
      <span class="legend-item"><i class="legend-dot" style="background:#f0a32f"></i>Diajukan — menunggu konfirmasi</span>
      <span class="legend-item"><i class="legend-dot" style="background:#28a745"></i>Disetujui — jadwal terpakai</span>
      <span class="legend-item"><i class="legend-dot" style="background:#2474c6"></i>Selesai</span>
      <span class="legend-item"><i class="legend-dot" style="background:#788492"></i>Batal</span>
    </div>
  </section>
  <div class="schedule-note"><strong>Perhatian:</strong> Agenda berstatus <b>Diajukan</b> masih menunggu konfirmasi. Pastikan pengajuan Anda sudah berstatus <b>Disetujui</b> sebelum menggunakan ruangan. Kalender ini membantu pengecekan awal; ketersediaan dapat berubah.</div>
  <!-- <div class="schedule-cta"><p>Sudah menemukan waktu yang sesuai? Lanjutkan pengajuan dan tunggu konfirmasi admin.</p><a href="user_peminjaman.php"><span class="glyphicon glyphicon-edit"></span> Ajukan Peminjaman</a></div> -->
</main>


<div class="modal fade" id="ModalEdit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Detail Jadwal</h4>
      </div>
      <div class="modal-body">
        <div class="detail-grid">
          <div class="detail-label">Nama Kegiatan</div><div class="detail-value" id="title">-</div>
          <div class="detail-label">Nama Peminjam</div><div class="detail-value" id="nama">-</div>
          <div class="detail-label">Nama Instansi</div><div class="detail-value" id="instansi">-</div>
          <div class="detail-label">Ruangan</div><div class="detail-value" id="namaruang">-</div>
          <div class="detail-label">Status</div><div><span id="status" class="detail-status">Tidak diketahui</span></div>
          <div class="detail-label">Mulai</div><div class="detail-value" id="start">-</div>
          <div class="detail-label">Selesai</div><div class="detail-value" id="end">-</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="BookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="booking-modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="bookingModalTitle">Ajukan Peminjaman</h4>
      </div>
      <div class="modal-body">
        <div class="booking-modal-field">
          <label>Tanggal Peminjaman</label>
          <div class="booking-modal-date"><span class="glyphicon glyphicon-calendar"></span><span id="bookingDateLabel">-</span></div>
          <input type="hidden" id="bookingDate">
        </div>
        <div class="booking-modal-field">
          <label for="bookingRoom">Pilih Ruangan</label>
          <select id="bookingRoom" class="form-control">
            <option value="">Pilih ruangan</option>
            <?php foreach ($rooms as $room): ?>
              <?php if ($room['status'] !== 'Tidak Tersedia'): ?>
                <option value="<?php echo (int) $room['id_ruang']; ?>"><?php echo htmlspecialchars($room['nama_ruang'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="booking-modal-grid">
          <div class="booking-modal-field">
            <label for="bookingStart">Waktu Mulai</label>
            <input type="time" id="bookingStart" class="form-control" value="08:00" step="1800">
          </div>
          <div class="booking-modal-field">
            <label for="bookingEnd">Waktu Selesai</label>
            <input type="time" id="bookingEnd" class="form-control" value="09:00" step="1800">
          </div>
        </div>
        <div class="booking-modal-error" id="bookingModalError" role="alert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="booking-modal">Batal</button>
        <button type="button" class="btn btn-primary" id="continueBooking">Lanjutkan</button>
      </div>
    </div>
  </div>
</div>


  <!-- JQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

  <!-- Bootstrap Core JavaScript -->
  <script src="js/bootstrap.min.js"></script>
  
  <!-- Flatpickr -->
  <!--
  <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.3.0/flatpickr.js"></script>
  <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
  <script>
    $("#basicDate").flatpickr({
      enableTime: true,
      minDate: "today",
      locale: "id",
      //altInput: true,
      //altFormat: "j F Y",
      dateFormat: "Y-m-d H:i",
      time_24hr: true,
      timeFormat: "H:i",
      static: true
  });
  </script>
-->
  <!-- FullCalendar -->
  <script src='js/moment.min.js'></script>
  <script src='js/fullcalendar.min.js'></script>
  <script src='js/id.js'></script>
  <script>

  $(document).ready(function() {
    var initialLocaleCode = 'id';
    var openScheduleDetail = function(event) {
      var status = event.status || 'Tidak diketahui';
      var statusClass = 'status-' + status.toLowerCase();
      var formatDetailDate = function(value, fallbackMoment) {
        if (value) {
          var parsed = moment(value, [moment.ISO_8601, 'YYYY-MM-DD HH:mm', 'YYYY-MM-DD']);
          return parsed.isValid() ? parsed.format(value.length === 10 ? 'D MMMM YYYY' : 'D MMMM YYYY, HH:mm') : value;
        }
        return fallbackMoment ? fallbackMoment.format('D MMMM YYYY, HH:mm') : '-';
      };

      $('#ModalEdit #title').text(event.title || '-');
      $('#ModalEdit #nama').text(event.nama || '-');
      $('#ModalEdit #instansi').text(event.instansi || '-');
      $('#ModalEdit #namaruang').text(event.namaruang || '-');
      $('#ModalEdit #status').text(status).removeClass('status-diajukan status-disetujui status-selesai status-batal').addClass(statusClass);
      $('#ModalEdit #start').text(formatDetailDate(event.start ? event.start.format('YYYY-MM-DD HH:mm') : null, event.start));
      $('#ModalEdit #end').text(formatDetailDate(event.displayEnd || (event.end ? event.end.format('YYYY-MM-DD HH:mm') : null), event.end));

      $('#ModalEdit').addClass('show').attr('aria-hidden', 'false');
      $('#schedule-modal-backdrop').remove();
      $('<div id="schedule-modal-backdrop"></div>').insertAfter('#ModalEdit');
      $('body').addClass('modal-open');
    };

    var closeScheduleDetail = function() {
      $('#ModalEdit').removeClass('show').attr('aria-hidden', 'true');
      $('#schedule-modal-backdrop').remove();
      $('body').removeClass('modal-open');
    };

    var closeBookingModal = function() {
      $('#BookingModal').removeClass('show').attr('aria-hidden', 'true');
      $('#schedule-modal-backdrop').remove();
      $('body').removeClass('modal-open');
      $('#bookingModalError').hide().text('');
    };

    var openBookingModal = function(date) {
      closeScheduleDetail();
      var selectedDate = date.format('YYYY-MM-DD');
      $('#bookingDate').val(selectedDate);
      $('#bookingDateLabel').text(date.format('dddd, D MMMM YYYY'));
      $('#bookingRoom').val('');

      var now = moment();
      var startTime = '08:00';
      var endTime = '09:00';
      if (date.isSame(now, 'day')) {
        var next = now.clone();
        if (next.minutes() < 30) {
          next.minutes(30);
        } else {
          next.add(1, 'hour').minutes(0);
        }
        next.seconds(0);
        startTime = next.format('HH:mm');
        endTime = next.clone().add(1, 'hour').format('HH:mm');
      }
      $('#bookingStart').val(startTime);
      $('#bookingEnd').val(endTime);

      $('#BookingModal').addClass('show').attr('aria-hidden', 'false');
      $('#schedule-modal-backdrop').remove();
      $('<div id="schedule-modal-backdrop"></div>').insertAfter('#BookingModal');
      $('body').addClass('modal-open');
    };

    $('#ModalEdit [data-dismiss="modal"]').on('click', closeScheduleDetail);
    $('#ModalEdit .close').on('click', closeScheduleDetail);
    $('#BookingModal [data-dismiss="booking-modal"]').on('click', closeBookingModal);
    $('#BookingModal .close').on('click', closeBookingModal);
    $(document).on('click', '#schedule-modal-backdrop', function() {
      closeScheduleDetail();
      closeBookingModal();
    });
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape' && $('#ModalEdit').hasClass('show')) closeScheduleDetail();
      if (e.key === 'Escape' && $('#BookingModal').hasClass('show')) closeBookingModal();
    });
    $(document).on('keydown', '#calendar .fc-event', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).trigger('click');
      }
    });

    $('#calendar').fullCalendar({
      header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month,agendaWeek,listMonth'
      },
      locale: initialLocaleCode,
      buttonText: { today: 'Hari ini', month: 'Bulan', week: 'Minggu', list: 'Daftar' },
      height: 'auto',
      firstDay: 1,
      navLinks: true,
      editable: false,
      eventLimit: true,
      selectable: false,
      dayClick: function(date) {
        if (date.isBefore(moment(), 'day')) return;
        openBookingModal(date);
      },
      dayRender: function(date, cell) {
        if (date.isBefore(moment(), 'day')) return;
        cell.css('cursor', 'pointer');
        cell.attr('title', 'Ajukan peminjaman pada ' + date.format('D MMMM YYYY'));
      },
      eventRender: function(event, element) {
        element.attr({
          'role': 'button',
          'tabindex': '0',
          'aria-label': 'Lihat detail ' + (event.title || 'jadwal')
        });
        element.off('click.scheduleDetail').on('click.scheduleDetail', function(e) {
          e.preventDefault();
          e.stopPropagation();
          openScheduleDetail(event);
        });
      },
      eventClick: function(event) {
          openScheduleDetail(event);
      },
      events: <?php echo json_encode($calendarEvents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
    });

    $('#bookingStart, #bookingEnd, #bookingRoom').on('change', function() {
      $('#bookingModalError').hide().text('');
    });

    $(document).on('click', '#calendar td.fc-day[data-date]', function(e) {
      if ($(e.target).closest('.fc-event').length) return;
      var dateValue = $(this).attr('data-date');
      var date = moment(dateValue, 'YYYY-MM-DD');
      if (!date.isValid() || date.isBefore(moment(), 'day')) return;
      openBookingModal(date);
    });

    $('#continueBooking').on('click', function() {
      var roomId = $('#bookingRoom').val();
      var date = $('#bookingDate').val();
      var startTime = $('#bookingStart').val();
      var endTime = $('#bookingEnd').val();
      var error = $('#bookingModalError');

      if (!roomId) {
        error.text('Pilih ruangan yang ingin dipinjam.').show();
        return;
      }
      if (!startTime || !endTime) {
        error.text('Tentukan waktu mulai dan selesai.').show();
        return;
      }

      var start = moment(date + ' ' + startTime, 'YYYY-MM-DD HH:mm');
      var end = moment(date + ' ' + endTime, 'YYYY-MM-DD HH:mm');
      if (!start.isValid() || !end.isValid() || !end.isAfter(start)) {
        error.text('Waktu selesai harus setelah waktu mulai.').show();
        return;
      }
      if (start.isBefore(moment())) {
        error.text('Waktu peminjaman tidak boleh berada di masa lalu.').show();
        return;
      }

      var selectedRoom = $('#bookingRoom option:selected').text();
      var conflict = false;
      $.each($('#calendar').fullCalendar('clientEvents'), function(index, event) {
        if (event.namaruang === selectedRoom &&
            event.status !== 'Batal' &&
            event.status !== 'Selesai') {
          if (start.isBefore(event.end) && end.isAfter(event.start)) conflict = true;
        }
      });
      if (conflict) {
        error.text('Waktu tersebut bentrok dengan jadwal ruangan yang sudah diajukan atau disetujui.').show();
        return;
      }

      window.location.href = 'user_peminjaman_ruangan.php?id=' +
        encodeURIComponent(roomId) +
        '&date=' + encodeURIComponent(date) +
        '&start=' + encodeURIComponent(startTime) +
        '&end=' + encodeURIComponent(endTime);
    });
  });




  </script>
</body>
</html>
