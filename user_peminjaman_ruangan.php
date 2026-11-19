<!DOCTYPE html>
<html lang="id">
<head>
  <title>Pengajuan | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="stylesheet" href="css/bootstrap-datetimepicker.css" />
  <link href="./style.css" type="text/css" rel="stylesheet">
  <link href="./template.css" type="text/css" rel="stylesheet">
  <style>.booking-success{margin-bottom:18px;padding:11px 13px;border:1px solid #bfe3cc;border-radius:9px;background:#effaf3;color:#26704b}.booking-pending{display:none;margin:0 0 17px;padding:10px 12px;border:1px solid #f0d99b;border-radius:8px;background:#fff8e8;color:#725a22;font-size:13px;line-height:1.5}</style>
 
</head>





<body class="booking-page">
<nav class="navbar">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="index.php"><span class="glyphicon glyphicon-home"></span> &nbsp;Peminjaman Ruangan</a>
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>                        
      </button>
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="user_jadwal.php">Kalender</a></li>
        <li class="active"><a href="user_peminjaman.php">Ajukan Peminjaman</a></li>
      </ul>
    </div>
  </div>
</nav>

<?php
include 'koneksi.php';
include 'proses_pengajuan_otomatis.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = mysqli_prepare($koneksi, "SELECT id_ruang, nama_ruang, gedung, status FROM ruangan WHERE id_ruang = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$room = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$roomBookings = array();
if ($room) {
  $bookingStmt = mysqli_prepare(
    $koneksi,
    "SELECT start_date, end_date, status FROM meminjam
     WHERE id_ruang = ? AND status IN ('Diajukan', 'Disetujui')"
  );
  mysqli_stmt_bind_param($bookingStmt, 'i', $id);
  mysqli_stmt_execute($bookingStmt);
  $bookingResult = mysqli_stmt_get_result($bookingStmt);
  while ($booking = mysqli_fetch_assoc($bookingResult)) {
    $roomBookings[] = $booking;
  }
  mysqli_stmt_close($bookingStmt);
}
?>

<main class="booking-shell">
  <a class="booking-back" href="user_peminjaman.php"><span class="glyphicon glyphicon-menu-left"></span> Pilih ruangan lain</a>

  <header class="booking-heading">
    <h1>Ajukan Peminjaman Ruangan</h1>
    <p>Lengkapi data kegiatan dan tentukan waktu penggunaan ruangan.</p>
  </header>

  <?php if (isset($_GET['error'])): ?>
    <div class="booking-error" role="alert">
      <?php
      if ($_GET['error'] === 'waktu') echo 'Waktu selesai harus setelah waktu mulai.';
      elseif ($_GET['error'] === 'tanggal') echo 'Waktu mulai dan selesai harus berada pada tanggal yang sama.';
      elseif ($_GET['error'] === 'disetujui') echo 'Waktu tersebut sudah disetujui untuk peminjaman lain' . (isset($_GET['jam']) ? ' pada jam ' . htmlspecialchars($_GET['jam'], ENT_QUOTES, 'UTF-8') : '') . '. Silakan pilih jam lain.';
      elseif ($_GET['error'] === 'bentrok') echo 'Waktu tersebut bentrok dengan jadwal yang sudah disetujui. Silakan pilih jam lain.';
      elseif ($_GET['error'] === 'simpan') echo 'Pengajuan belum berhasil disimpan. Silakan coba lagi.';
      else echo 'Lengkapi semua data dengan benar.';
      ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['success'])): ?>
    <div class="booking-success" role="status"><span class="glyphicon glyphicon-ok-circle"></span> Pengajuan berhasil dikirim.<?php if (isset($_GET['pending_jam'])): ?> Perhatian: pada jam <strong><?php echo htmlspecialchars($_GET['pending_jam'], ENT_QUOTES, 'UTF-8'); ?></strong> sudah ada pengajuan lain yang masih menunggu persetujuan.<?php endif; ?></div>
  <?php endif; ?>

  <?php if (!$room): ?>
    <div class="booking-error">Ruangan tidak ditemukan atau tidak tersedia.</div>
  <?php else: ?>
  <div class="booking-grid">
    <section class="booking-card" aria-label="Form pengajuan peminjaman">
      <form method="post" action="user_peminjaman_aksi.php" id="bookingForm">
        <input type="hidden" name="id_ruang" value="<?php echo (int) $room['id_ruang']; ?>">

        <div class="form-group">
          <label for="nama_peminjam">Nama Peminjam</label>
          <input type="text" class="form-control" id="nama_peminjam" placeholder="Contoh: Ahmad Fajar" name="nama_peminjam" required maxlength="255" autocomplete="name">
        </div>

        <div class="form-group">
          <label for="instansi_peminjam">Nama Instansi / Unit</label>
          <input type="text" class="form-control" id="instansi_peminjam" placeholder="Contoh: Jurusan Kesehatan Lingkungan" name="instansi_peminjam" required maxlength="50" autocomplete="organization">
        </div>

        <div class="form-group">
          <label for="nama_kegiatan">Nama Kegiatan</label>
          <input type="text" class="form-control" id="nama_kegiatan" placeholder="Contoh: Pelatihan atau perkuliahan" name="nama_kegiatan" required maxlength="255">
        </div>

        <div class="form-group">
          <label>Waktu Mulai</label>
          <div class="booking-time-grid">
            <input type="date" id="start_date_input" class="form-control" aria-label="Tanggal mulai" required>
            <input type="time" id="start_time_input" class="form-control" aria-label="Jam mulai" step="1800" required>
          </div>
          <input type="hidden" id="start_date" name="start_date">
        </div>

        <div class="form-group">
          <label>Waktu Selesai</label>
          <div class="booking-time-grid">
            <input type="date" id="end_date_input" class="form-control" aria-label="Tanggal selesai" required readonly>
            <input type="time" id="end_time_input" class="form-control" aria-label="Jam selesai" step="1800" required>
          </div>
          <input type="hidden" id="end_date" name="end_date">
        </div>

        <div class="booking-duration" id="bookingDuration" aria-live="polite">
          <span class="glyphicon glyphicon-time"></span>
          <span>Pilih waktu untuk melihat durasi peminjaman.</span>
        </div>
        <div class="booking-pending" id="bookingConflictNotice" role="status"></div>

        <div class="form-group">
          <label for="jumlah_mahasiswa">Jumlah Peserta</label>
          <input type="number" class="form-control" id="jumlah_mahasiswa" placeholder="Masukkan jumlah peserta" name="jumlah_mahasiswa" required min="1" max="9999" autocomplete="off">
        </div>

        <div class="booking-actions">
          <a class="btn btn-default" href="user_peminjaman.php">Batal</a>
          <button type="submit" name="submit" class="btn btn-primary">Ajukan Peminjaman</button>
        </div>
      </form>
    </section>

    <aside class="room-summary" aria-label="Ringkasan ruangan">
      <div class="room-summary-icon"><span class="glyphicon glyphicon-blackboard"></span></div>
      <h2><?php echo htmlspecialchars($room['nama_ruang'], ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars($room['gedung'] ? 'Gedung '.$room['gedung'] : 'Ruangan tersedia', ENT_QUOTES, 'UTF-8'); ?></p>
      <span class="room-status"><?php echo htmlspecialchars($room['status'], ENT_QUOTES, 'UTF-8'); ?></span>

      <section class="availability-card" aria-live="polite">
        <div class="availability-heading">
          <span class="glyphicon glyphicon-calendar"></span>
          <div>
            <strong>Jam yang masih kosong</strong>
            <small id="availabilityDate">Untuk tanggal yang dipilih</small>
          </div>
        </div>
        <div class="availability-slots" id="availabilitySlots"></div>
      </section>
    </aside>
  </div>
  <?php endif; ?>
</main>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/moment.js"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.js"></script>
<script type="text/javascript">
  $(function () {
    var roomBookings = <?php echo json_encode($roomBookings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var pad = function(value) {
      return value < 10 ? '0' + value : '' + value;
    };

    var toDateInput = function(date) {
      return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    };

    var toTimeInput = function(date) {
      return pad(date.getHours()) + ':' + pad(date.getMinutes());
    };

    var now = new Date();
    var defaultStart = new Date(now);
    var minutes = defaultStart.getMinutes();
    defaultStart.setSeconds(0, 0);
    if (defaultStart.getHours() >= 23) {
      defaultStart.setHours(22);
      defaultStart.setMinutes(30);
    } else if (minutes < 30) {
      defaultStart.setMinutes(30);
    } else {
      defaultStart.setMinutes(0);
      defaultStart.setHours(defaultStart.getHours() + 1);
    }

    var defaultEnd = new Date(defaultStart.getTime() + (60 * 60 * 1000));

    var queryParams = new URLSearchParams(window.location.search);
    var prefillDate = queryParams.get('date');
    var prefillStart = queryParams.get('start');
    var prefillEnd = queryParams.get('end');
    var validDate = /^\d{4}-\d{2}-\d{2}$/.test(prefillDate || '');
    var validTime = /^\d{2}:\d{2}$/.test(prefillStart || '') && /^\d{2}:\d{2}$/.test(prefillEnd || '');

    $('#start_date_input').val(validDate ? prefillDate : toDateInput(defaultStart)).attr('min', toDateInput(now));
    $('#start_time_input').val(validTime ? prefillStart : toTimeInput(defaultStart));
    $('#end_date_input').val(validDate ? prefillDate : toDateInput(defaultEnd)).attr('min', validDate ? prefillDate : toDateInput(defaultStart));
    $('#end_time_input').val(validTime ? prefillEnd : toTimeInput(defaultEnd));

    var getStartDate = function() {
      var dateValue = $('#start_date_input').val();
      var timeValue = $('#start_time_input').val();
      return dateValue && timeValue ? new Date(dateValue + 'T' + timeValue + ':00') : null;
    };

    var getEndDate = function() {
      var dateValue = $('#end_date_input').val();
      var timeValue = $('#end_time_input').val();
      return dateValue && timeValue ? new Date(dateValue + 'T' + timeValue + ':00') : null;
    };

    var updateAvailability = function() {
      var selectedDate = $('#start_date_input').val();
      var $slots = $('#availabilitySlots');
      if (!selectedDate) {
        $('#availabilityDate').text('Untuk tanggal yang dipilih');
        $slots.html('<span class="availability-empty">Pilih tanggal terlebih dahulu.</span>');
        return;
      }

      var parts = selectedDate.split('-');
      var dateLabel = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
      $('#availabilityDate').text(dateLabel.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));

      var dayStart = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 7, 0, 0);
      var dayEnd = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 22, 0, 0);
      var busy = [];

      $.each(roomBookings, function(index, booking) {
        var start = new Date(String(booking.start_date).replace(' ', 'T'));
        var end = new Date(String(booking.end_date).replace(' ', 'T'));
        if (isNaN(start.getTime()) || isNaN(end.getTime())) return;
        if (start < dayEnd && end > dayStart) {
          busy.push({
            start: start < dayStart ? dayStart : start,
            end: end > dayEnd ? dayEnd : end
          });
        }
      });

      busy.sort(function(a, b) { return a.start - b.start; });
      var merged = [];
      $.each(busy, function(index, range) {
        if (!merged.length || range.start > merged[merged.length - 1].end) {
          merged.push({ start: new Date(range.start), end: new Date(range.end) });
        } else if (range.end > merged[merged.length - 1].end) {
          merged[merged.length - 1].end = new Date(range.end);
        }
      });

      var free = [];
      var cursor = dayStart;
      $.each(merged, function(index, range) {
        if (range.start > cursor) free.push({ start: cursor, end: range.start });
        if (range.end > cursor) cursor = range.end;
      });
      if (cursor < dayEnd) free.push({ start: cursor, end: dayEnd });

      if (!free.length) {
        $slots.html('<span class="availability-empty">Tidak ada slot kosong pada tanggal ini.</span>');
        return;
      }

      var slotHtml = '';
      $.each(free, function(index, range) {
        var durationMinutes = Math.round((range.end - range.start) / 60000);
        if (durationMinutes >= 30) {
          slotHtml += '<span class="availability-slot">' +
            toTimeInput(range.start) + '–' + toTimeInput(range.end) +
            '</span>';
        }
      });
      $slots.html(slotHtml || '<span class="availability-empty">Tidak ada slot kosong pada tanggal ini.</span>');
    };

    var updateDuration = function() {
      var start = getStartDate();
      var end = getEndDate();

      $('#start_date').val(start ? toDateInput(start) + ' ' + toTimeInput(start) : '');
      $('#end_date').val(end ? toDateInput(end) + ' ' + toTimeInput(end) : '');
      $('#end_date_input').attr('min', start ? toDateInput(start) : toDateInput(now));

      if (!start || !end) {
        $('#bookingDuration').html('<span class="glyphicon glyphicon-time"></span><span>Pilih waktu untuk melihat durasi peminjaman.</span>');
        return;
      }

      var milliseconds = end.getTime() - start.getTime();
      if (milliseconds <= 0) {
        $('#bookingDuration').html('<span class="glyphicon glyphicon-warning-sign"></span><span><strong>Waktu selesai harus setelah waktu mulai.</strong></span>');
        return;
      }

      var totalMinutes = Math.floor(milliseconds / 60000);
      var hours = Math.floor(totalMinutes / 60);
      var minutes = totalMinutes % 60;
      var text = hours ? hours + ' jam' : '';
      if (minutes) text += (text ? ' ' : '') + minutes + ' menit';
      $('#bookingDuration').html('<span class="glyphicon glyphicon-time"></span><span>Durasi peminjaman: <strong>' + text + '</strong></span>');
    };

    var updateConflictNotice = function() {
      var start = getStartDate();
      var end = getEndDate();
      var $notice = $('#bookingConflictNotice');
      if (!start || !end || end <= start) {
        $notice.hide().text('');
        return;
      }
      var pending = false;
      var approved = false;
      $.each(roomBookings, function(index, booking) {
        var bookedStart = new Date(String(booking.start_date).replace(' ', 'T'));
        var bookedEnd = new Date(String(booking.end_date).replace(' ', 'T'));
        if (isNaN(bookedStart.getTime()) || isNaN(bookedEnd.getTime()) || !(start < bookedEnd && end > bookedStart)) return;
        if (booking.status === 'Disetujui') approved = true;
        if (booking.status === 'Diajukan') pending = true;
      });
      if (approved) {
        $notice.css({'display':'block','background':'#fff1f1','border-color':'#f3c4c4','color':'#9c2d2d'}).html('<span class="glyphicon glyphicon-remove-circle"></span> Jam yang dipilih sudah <strong>disetujui</strong> untuk peminjaman lain. Pilih jam lain.');
      } else if (pending) {
        $notice.css({'display':'block','background':'#fff8e8','border-color':'#f0d99b','color':'#725a22'}).html('<span class="glyphicon glyphicon-info-sign"></span> Jam yang dipilih sudah memiliki pengajuan lain yang masih <strong>menunggu persetujuan</strong>. Pengajuan Anda tetap dapat dikirim.');
      } else {
        $notice.hide().text('');
      }
    };

    $('#start_date_input, #start_time_input').on('change', function() {
      var start = getStartDate();
      if (start) {
        $('#end_date_input').val(toDateInput(start));
        var end = getEndDate();
        if (!end || end <= start) {
          end = new Date(start.getTime() + (60 * 60 * 1000));
          $('#end_time_input').val(toTimeInput(end));
        }
      }
      updateDuration();
      updateAvailability();
      updateConflictNotice();
    });

    $('#end_time_input').on('change', function() { updateDuration(); updateConflictNotice(); });
    updateDuration();
    updateAvailability();
    updateConflictNotice();

    $('#bookingForm').on('submit', function (e) {
      var start = getStartDate();
      var end = getEndDate();
      if (!start || !end) {
        e.preventDefault();
        alert('Tentukan waktu mulai dan selesai peminjaman.');
        return;
      }
      if (end.valueOf() <= start.valueOf()) {
        e.preventDefault();
        alert('Waktu selesai harus setelah waktu mulai.');
        return;
      }
      if (toDateInput(start) !== toDateInput(end)) {
        e.preventDefault();
        alert('Waktu mulai dan selesai harus berada pada tanggal yang sama.');
        return;
      }

       var approvedConflict = false;
      $.each(roomBookings, function(index, booking) {
        var bookedStart = new Date(String(booking.start_date).replace(' ', 'T'));
        var bookedEnd = new Date(String(booking.end_date).replace(' ', 'T'));
        if (!isNaN(bookedStart.getTime()) && !isNaN(bookedEnd.getTime()) &&
            start < bookedEnd && end > bookedStart) {
            if (booking.status === 'Disetujui') approvedConflict = true;
            return false;
        }
      });
       if (approvedConflict) {
        e.preventDefault();
        alert('Waktu yang dipilih sudah disetujui untuk peminjaman lain. Silakan pilih jam lain.');
      }
    });
  });
</script>
</body>
</html>
