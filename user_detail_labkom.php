

<!DOCTYPE html>
<html lang="id">
<head>
  <title>List Jadwal | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="./style.css" type="text/css" rel="stylesheet"> 
  <link href="./template.css" type="text/css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap.min.js"></script>
  
  
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
$totalRows = $data ? mysqli_num_rows($data) : 0;

function detailTableText($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function detailTableDate($value) {
  $timestamp = strtotime((string) $value);
  return $timestamp ? date('d M Y, H:i', $timestamp) : '-';
}

function detailTableSortValue($value) {
  $timestamp = strtotime((string) $value);
  return $timestamp ? date('Y-m-d H:i:s', $timestamp) : '0000-00-00 00:00:00';
}
?>

<main class="detail-table-shell">
  <a class="detail-table-back" href="index.php"><span class="glyphicon glyphicon-menu-left"></span> Kembali ke Beranda</a>

  <header class="detail-table-heading">
    <div>
      <h1>Jadwal Lab Komputer</h1>
      <p>Daftar kegiatan, waktu penggunaan, dan status peminjaman ruangan.</p>
    </div>
  </header>

  <section class="detail-table-card" aria-label="Tabel jadwal Lab Komputer">
    <div class="detail-table-toolbar">
      <strong>Daftar Peminjaman</strong>
      <span class="room-status"><?php echo $totalRows; ?> jadwal tercatat</span>
    </div>

    <div class="detail-table-wrapper">
      <table class="table detail-table" id="pengajuan">
        <thead>
          <tr>
            <th>Nama Peminjam</th>
            <th>Mulai</th>
            <th>Selesai</th>
            <th>Status</th>
            <th>Detail</th>
            <th>Informasi pencarian</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($data): ?>
          <?php while ($d = mysqli_fetch_array($data)): ?>
            <?php
            $status = $d['status'];
            if ($status === 'Disetujui') $statusClass = 'status-approved';
            elseif ($status === 'Diajukan') $statusClass = 'status-pending';
            elseif ($status === 'Batal') $statusClass = 'status-cancelled';
            else $statusClass = 'status-completed';
            ?>
            <tr>
              <td class="table-primary-cell"><?php echo detailTableText($d['nama_peminjam']); ?></td>
              <td class="table-secondary-cell" data-order="<?php echo detailTableText(detailTableSortValue($d['start_date'])); ?>"><?php echo detailTableDate($d['start_date']); ?></td>
              <td class="table-secondary-cell" data-order="<?php echo detailTableText(detailTableSortValue($d['end_date'])); ?>"><?php echo detailTableDate($d['end_date']); ?></td>
              <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo detailTableText($status); ?></span></td>
              <td>
                <button type="button" class="expand-toggle"
                  data-instansi="<?php echo detailTableText($d['instansi_peminjam'] ?: '-'); ?>"
                  data-kegiatan="<?php echo detailTableText($d['nama_kegiatan'] ?: '-'); ?>"
                  data-peserta="<?php echo detailTableText($d['jumlah_mahasiswa'] ?: '-'); ?>"
                  data-keterangan="<?php echo detailTableText($d['keterangan'] ?: '-'); ?>"
                  aria-expanded="false">
                  <span class="glyphicon glyphicon-plus"></span> Detail
                </button>
              </td>
              <td class="detail-search-data"><?php echo detailTableText(implode(' ', array($d['instansi_peminjam'], $d['nama_kegiatan'], $d['jumlah_mahasiswa'], $d['keterangan']))); ?></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script type="text/javascript">
  $(document).ready(function () {
    $('#pengajuan').DataTable({
      order: [[1, 'desc']],
      pageLength: 10,
      scrollX: true,
      lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
      language: {
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ data',
        info: 'Menampilkan _START_-_END_ dari _TOTAL_ jadwal',
        infoEmpty: 'Tidak ada jadwal',
        zeroRecords: 'Jadwal tidak ditemukan',
        paginate: {
          previous: 'Sebelumnya',
          next: 'Berikutnya'
        }
      },
      columnDefs: [
        { targets: [4, 5], orderable: false },
        { targets: [4], searchable: false, className: 'text-center' },
        { targets: [5], visible: false }
      ]
    });

    $('#pengajuan tbody').on('click', '.expand-toggle', function () {
      var button = $(this);
      var row = $('#pengajuan').DataTable().row(button.closest('tr'));

      if (row.child.isShown()) {
        row.child.hide();
        button.attr('aria-expanded', 'false').html('<span class="glyphicon glyphicon-plus"></span> Detail');
        return;
      }

      var details = [
        ['Instansi', this.getAttribute('data-instansi')],
        ['Kegiatan', this.getAttribute('data-kegiatan')],
        ['Peserta', this.getAttribute('data-peserta') === '-' ? '-' : this.getAttribute('data-peserta') + ' orang'],
        ['Keterangan', this.getAttribute('data-keterangan')]
      ];
      var panel = $('<div class="public-detail-panel"></div>');
      $.each(details, function (index, item) {
        var detailItem = $('<div class="public-detail-item"></div>');
        detailItem.append($('<span></span>').text(item[0]));
        detailItem.append($('<strong></strong>').text(item[1] || '-'));
        panel.append(detailItem);
      });

      row.child(panel).show();
      button.attr('aria-expanded', 'true').html('<span class="glyphicon glyphicon-minus"></span> Tutup detail');
    });
  });
</script>
</body>
</html>