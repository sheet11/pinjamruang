<!DOCTYPE html>
<html lang="id">
<head>
  <title>Jadwal Lab Komputer | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="./style.css" type="text/css" rel="stylesheet">
  <link href="./template.css" type="text/css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>

<body class="detail-table-page">
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
$data = mysqli_query($koneksi, "SELECT * FROM meminjam WHERE id_ruang='101' ORDER BY start_date DESC");
$rows = $data ? mysqli_fetch_all($data, MYSQLI_ASSOC) : array();

function cardText($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cardDate($value) {
  $timestamp = strtotime((string) $value);
  return $timestamp ? date('d M Y, H:i', $timestamp) : '-';
}

function cardStatus($status) {
  if ($status === 'Disetujui') return array('status-approved', 'Disetujui');
  if ($status === 'Diajukan') return array('status-pending', 'Diajukan');
  if ($status === 'Batal') return array('status-cancelled', 'Batal');
  return array('status-completed', $status ?: 'Selesai');
}
?>

<main class="detail-table-shell">
  <a class="detail-table-back" href="index.php"><span class="glyphicon glyphicon-menu-left"></span> Kembali ke Beranda</a>

  <header class="detail-table-heading">
    <div>
      <h1>Jadwal Lab Komputer</h1>
      <p>Cari jadwal, lihat waktu penggunaan, dan cek status peminjaman ruangan.</p>
    </div>
  </header>

  <section aria-label="Daftar kartu jadwal Lab Komputer">
    <div class="schedule-card-toolbar">
      <input type="search" id="scheduleCardSearch" class="schedule-card-search" placeholder="Cari kegiatan, peminjam, atau instansi">
      <div class="schedule-card-filters" aria-label="Filter status">
        <button type="button" class="btn schedule-card-filter active" data-status="Semua">Semua</button>
        <button type="button" class="btn schedule-card-filter" data-status="Diajukan">Diajukan</button>
        <button type="button" class="btn schedule-card-filter" data-status="Disetujui">Disetujui</button>
        <button type="button" class="btn schedule-card-filter" data-status="Selesai">Selesai</button>
        <button type="button" class="btn schedule-card-filter" data-status="Batal">Batal</button>
      </div>
    </div>

    <div class="schedule-card-grid" id="scheduleCardGrid">
      <?php foreach ($rows as $row): ?>
        <?php list($statusClass, $statusLabel) = cardStatus($row['status']); ?>
        <article class="schedule-card-item"
          data-status="<?php echo cardText($row['status']); ?>"
          data-search="<?php echo cardText(strtolower($row['nama_kegiatan'].' '.$row['nama_peminjam'].' '.$row['instansi_peminjam'].' '.$row['keterangan'])); ?>">
          <div class="schedule-card-top">
            <div>
              <h2 class="schedule-card-title"><?php echo cardText($row['nama_kegiatan']); ?></h2>
              <div class="schedule-card-person"><?php echo cardText($row['nama_peminjam']); ?> &middot; <?php echo cardText($row['instansi_peminjam'] ?: '-'); ?></div>
            </div>
            <span class="status-badge <?php echo $statusClass; ?>"><?php echo cardText($statusLabel); ?></span>
          </div>
          <div class="schedule-card-body">
            <div class="schedule-card-info">
              <strong>Mulai</strong>
              <span><?php echo cardText(cardDate($row['start_date'])); ?></span>
            </div>
            <div class="schedule-card-info">
              <strong>Selesai</strong>
              <span><?php echo cardText(cardDate($row['end_date'])); ?></span>
            </div>
            <div class="schedule-card-info">
              <strong>Peserta</strong>
              <span><?php echo cardText($row['jumlah_mahasiswa'] ?: '-'); ?> orang</span>
            </div>
            <div class="schedule-card-info">
              <strong>Keterangan</strong>
              <span><?php echo cardText($row['keterangan'] ?: '-'); ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="schedule-card-pagination">
      <button type="button" id="scheduleCardPrev"><span class="glyphicon glyphicon-chevron-left"></span> Sebelumnya</button>
      <span id="scheduleCardInfo"></span>
      <button type="button" id="scheduleCardNext">Berikutnya <span class="glyphicon glyphicon-chevron-right"></span></button>
    </div>
  </section>
</main>

<script type="text/javascript">
  $(function () {
    var selectedStatus = 'Semua';
    var currentPage = 1;
    var pageSize = 8;

    function visibleCards() {
      var keyword = $('#scheduleCardSearch').val().toLowerCase();
      var cards = $('.schedule-card-item');

      cards.hide().filter(function () {
        var card = $(this);
        var statusMatch = selectedStatus === 'Semua' || card.data('status') === selectedStatus;
        var searchMatch = !keyword || String(card.data('search')).toLowerCase().indexOf(keyword) !== -1;
        return statusMatch && searchMatch;
      }).show();

      return cards.filter(':visible');
    }

    function renderCards() {
      var cards = $('.schedule-card-item');
      cards.hide();
      var filtered = cards.filter(function () {
        var card = $(this);
        var keyword = $('#scheduleCardSearch').val().toLowerCase();
        var statusMatch = selectedStatus === 'Semua' || card.data('status') === selectedStatus;
        var searchMatch = !keyword || String(card.data('search')).toLowerCase().indexOf(keyword) !== -1;
        return statusMatch && searchMatch;
      });

      var pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
      if (currentPage > pageCount) currentPage = pageCount;
      var start = (currentPage - 1) * pageSize;
      filtered.slice(start, start + pageSize).show();

      $('#scheduleCardInfo').text(filtered.length ? 'Halaman ' + currentPage + ' dari ' + pageCount + ' · ' + filtered.length + ' jadwal' : 'Tidak ada jadwal ditemukan');
      $('#scheduleCardPrev').prop('disabled', currentPage <= 1);
      $('#scheduleCardNext').prop('disabled', currentPage >= pageCount);

      if (!filtered.length) {
        if (!$('#scheduleCardEmpty').length) {
          $('#scheduleCardGrid').append('<div class="schedule-card-empty" id="scheduleCardEmpty">Tidak ada jadwal yang sesuai.</div>');
        }
      } else {
        $('#scheduleCardEmpty').remove();
      }
    }

    $('.schedule-card-filter').on('click', function () {
      selectedStatus = $(this).data('status');
      $('.schedule-card-filter').removeClass('active');
      $(this).addClass('active');
      currentPage = 1;
      renderCards();
    });

    $('#scheduleCardSearch').on('input', function () {
      currentPage = 1;
      renderCards();
    });

    $('#scheduleCardPrev').on('click', function () {
      if (currentPage > 1) {
        currentPage -= 1;
        renderCards();
      }
    });

    $('#scheduleCardNext').on('click', function () {
      currentPage += 1;
      renderCards();
    });

    renderCards();
  });
</script>
</body>
</html>
