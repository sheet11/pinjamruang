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

  function formatTanggalWaktu($value, $withTime = true) {
    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') return '-';
    try {
      $date = new DateTime($value);
      $months = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
      $result = (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
      return $withTime ? $result . ', ' . $date->format('H:i') : $result;
    } catch (Exception $e) {
      return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
  }

  $statusMap = array('1' => array('Disetujui', '#23865b'), '2' => array('Selesai', '#2474c6'), '3' => array('Batal', '#788492'));
  if (isset($_GET['proses'], $_GET['kode_pinjam'], $_GET['status']) && isset($statusMap[$_GET['status']])) {
    $kode = (int) $_GET['kode_pinjam'];
    $statusData = $statusMap[$_GET['status']];
    $update = mysqli_prepare($koneksi, 'UPDATE meminjam SET status = ?, color = ? WHERE kode_pinjam = ?');
    mysqli_stmt_bind_param($update, 'ssi', $statusData[0], $statusData[1], $kode);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
    header('Location: admin_pengajuan.php?updated=' . urlencode($statusData[0]));
    exit;
  }

  $perPage = 15;
  $page = isset($_GET['halaman']) ? max(1, (int) $_GET['halaman']) : 1;
  $search = isset($_GET['cari']) ? trim($_GET['cari']) : '';
  $statusFilter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
  $where = array();
  if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($koneksi, $search);
    $where[] = "(nama_peminjam LIKE '%$safeSearch%' OR instansi_peminjam LIKE '%$safeSearch%' OR nama_ruang LIKE '%$safeSearch%' OR nama_kegiatan LIKE '%$safeSearch%')";
  }
  if (in_array($statusFilter, array('Diajukan', 'Disetujui', 'Selesai', 'Batal'), true)) {
    $where[] = "status = '" . mysqli_real_escape_string($koneksi, $statusFilter) . "'";
  }
  $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
  $countResult = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM meminjam $whereSql");
  $totalRows = (int) mysqli_fetch_assoc($countResult)['total'];
  $totalPages = max(1, (int) ceil($totalRows / $perPage));
  $page = min($page, $totalPages);
  $offset = ($page - 1) * $perPage;
  $data = mysqli_query($koneksi, "SELECT * FROM meminjam $whereSql ORDER BY start_date DESC, kode_pinjam DESC LIMIT $offset, $perPage");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>[Admin] Pengajuan | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="./style.css" type="text/css" rel="stylesheet"> 
  <link href="./template.css" type="text/css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="./fonts/font-awesome.min.css">
  <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <style>
    body { background:#f4f7fb; color:#19324d; }
    .navbar { position:sticky; top:0; min-height:64px; background:#fff; box-shadow:0 3px 18px rgba(25,50,77,.08); }
    .navbar li a { color:#3d5369; padding-top:22px; padding-bottom:22px; }
    .navbar li a:hover { background:#eef5fb; color:#087f8c; }
    .icon-bar { background:#163b63; }
    .admin-badge { display:inline-block; min-width:20px; margin-left:4px; padding:3px 7px; border-radius:12px; background:#e39a22; color:#fff; font-size:11px; font-weight:700; vertical-align:top; }
    .admin-list { max-width:1240px; margin:0 auto; padding:35px 20px 55px; }
    .admin-heading { display:flex; align-items:end; justify-content:space-between; gap:15px; margin-bottom:22px; }
    .admin-heading h1 { margin:0 0 7px; color:#193b59; font-size:29px; font-weight:700; }
    .admin-heading p { margin:0; color:#718196; }
    .admin-card { padding:20px; border:1px solid #e3eaf1; border-radius:15px; background:#fff; box-shadow:0 7px 22px rgba(25,50,77,.05); }
    .filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
    .filter-bar .form-control { height:40px; border-radius:8px; border-color:#dce5ed; box-shadow:none; }
    .filter-search { flex:1 1 280px; }
    .filter-status { flex:0 1 180px; }
    .btn-filter { height:40px; border:0; border-radius:8px; background:#087f8c; color:#fff; font-weight:600; }
    .btn-filter:hover { background:#066d77; color:#fff; }
    .table-wrap { overflow-x:auto; }
    .admin-table { min-width:1080px; margin:0; border:0; }
    .admin-table thead th { padding:13px 11px; border:0; background:#eef5f8; color:#52677c; font-size:12px; text-transform:uppercase; white-space:nowrap; }
    .admin-table tbody td { padding:13px 11px; border-color:#edf1f5; color:#40566b; vertical-align:middle; font-size:13px; }
    .admin-table tbody tr:hover { background:#f8fbfc; }
    .admin-table .detail-row:hover { background:#f8fbfc; }
    .expand-toggle { border:0; border-radius:6px; padding:6px 9px; background:#eaf6f5; color:#087f8c; font-size:11px; font-weight:700; }
    .expand-toggle:hover, .expand-toggle:focus { background:#087f8c; color:#fff; }
    .detail-row > td { padding:0!important; border-top:0!important; }
    .detail-panel { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:15px; padding:17px; border-top:1px solid #dcebed; background:#f8fbfc; }
    .detail-item label { display:block; margin:0 0 6px; color:#8190a1; font-size:11px; font-weight:700; text-transform:uppercase; }
    .detail-item strong { color:#35566a; font-size:13px; }
    .detail-document { display:inline-block; margin-right:5px; padding:6px 9px; border-radius:6px; background:#e4f5eb; color:#26704b; font-size:11px; font-weight:700; text-decoration:none; }
    .detail-document:hover { color:#fff; background:#26704b; text-decoration:none; }
    .status-pill { display:inline-block; padding:5px 10px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap; }
    .status-diajukan { background:#fff2d9; color:#9a6813; }.status-disetujui{background:#e4f5eb;color:#26704b}.status-selesai{background:#e5f0fc;color:#28649c}.status-batal{background:#edf0f2;color:#65717b}
    .action-group { display:flex; flex-wrap:wrap; gap:5px; min-width:190px; }
    .action-group .btn { border:0; border-radius:6px; padding:6px 8px; font-size:11px; }
    .empty-state { padding:40px 15px!important; text-align:center; color:#8190a1!important; }
    .table-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-top:18px; color:#718196; font-size:13px; }
    .pagination { margin:0; }.pagination>li>a { border:0; margin:0 2px; border-radius:6px!important; color:#087f8c; }.pagination>.active>a{background:#087f8c;color:#fff}
    @media(max-width:767px){.admin-list{padding:24px 13px 40px}.admin-heading{display:block}.admin-heading h1{font-size:24px}.admin-card{padding:13px}.filter-bar{display:block}.filter-bar>*{width:100%;margin-bottom:8px}.table-footer{display:block}.table-footer .pagination{margin-top:12px}.detail-panel{grid-template-columns:1fr;gap:12px}}
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
         <li><a href="admin_pengajuan.php"><span class="glyphicon glyphicon-bell"></span> Pengajuan <?php if ($pendingCount > 0): ?><span class="admin-badge"><?php echo $pendingCount; ?></span><?php endif; ?></a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="./logout.php"><span class="glyphicon glyphicon-log-out"></span> Log Out</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="admin-list">
  <header class="admin-heading">
    <div><h1>Daftar Pengajuan</h1><p>Kelola, verifikasi, dan pantau seluruh peminjaman ruangan.</p></div>
    <a href="admin_menu.php" class="btn btn-default"><span class="glyphicon glyphicon-menu-left"></span> Menu Admin</a>
  </header>
  <?php if (isset($_GET['updated'])): ?><div class="alert alert-success"><span class="glyphicon glyphicon-ok-circle"></span> Status pengajuan berhasil diperbarui menjadi <strong><?php echo htmlspecialchars($_GET['updated'], ENT_QUOTES, 'UTF-8'); ?></strong>.</div><?php endif; ?>
  <section class="admin-card">
    <form class="filter-bar" method="get" action="admin_pengajuan.php">
      <div class="filter-search"><input type="search" name="cari" class="form-control" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari nama, instansi, ruang, atau kegiatan..."></div>
      <div class="filter-status"><select name="status_filter" class="form-control"><option value="">Semua status</option><?php foreach (array('Diajukan','Disetujui','Selesai','Batal') as $option): ?><option value="<?php echo $option; ?>" <?php echo $statusFilter === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-filter" type="submit"><span class="glyphicon glyphicon-search"></span> Cari</button>
      <?php if ($search !== '' || $statusFilter !== ''): ?><a href="admin_pengajuan.php" class="btn btn-default" style="height:40px;padding-top:11px;border-radius:8px;">Reset</a><?php endif; ?>
    </form>
    <div class="table-wrap">
      <table class="table admin-table">
            <thead class="text-center">

              <tr>
                <th>Nama Peminjam</th>
                <th>Tanggal &amp; Jam Pengajuan</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>

          <tbody>
    <?php if (mysqli_num_rows($data) === 0): ?><tr><td colspan="6" class="empty-state"><span class="glyphicon glyphicon-inbox" style="font-size:28px;display:block;margin-bottom:10px;"></span>Tidak ada pengajuan yang sesuai.</td></tr><?php endif; ?>
    <?php while($d = mysqli_fetch_assoc($data)): $statusClass = 'status-' . strtolower($d['status']); ?>
			<tr>
				<td><strong><?php echo htmlspecialchars($d['nama_peminjam'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
        <td><?php echo formatTanggalWaktu($d['tgl_pinjam']); ?></td>
				<td><?php echo formatTanggalWaktu($d['start_date']); ?></td>
				<td><?php echo formatTanggalWaktu($d['end_date']); ?></td>
        <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td><div class="action-group"><button type="button" class="expand-toggle" data-toggle="collapse" data-target="#detail-<?php echo (int)$d['kode_pinjam']; ?>" aria-expanded="false"><span class="glyphicon glyphicon-plus"></span> Detail</button>
          <?php if ($d['status']=='Diajukan'): ?><a href="admin_pengajuan.php?proses=1&kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>&status=1" class="btn btn-success" onclick="return confirm('Setujui pengajuan ini?');">Setujui</a><a href="admin_pengajuan.php?proses=1&kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>&status=3" class="btn btn-warning" onclick="return confirm('Batalkan pengajuan ini?');">Batalkan</a><?php elseif ($d['status']=='Disetujui'): ?><a href="admin_pengajuan.php?proses=1&kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>&status=2" class="btn btn-primary" onclick="return confirm('Tandai sebagai selesai?');">Selesaikan</a><a href="admin_pengajuan.php?proses=1&kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>&status=3" class="btn btn-warning" onclick="return confirm('Batalkan pengajuan ini?');">Batalkan</a><?php elseif ($d['status']=='Batal'): ?><a href="admin_edit_keterangan.php?kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>" class="btn btn-warning">Alasan</a><?php endif; ?>
          <a href="admin_edit_pengajuan.php?kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>" class="btn btn-default">Edit</a><a href="delete.php?kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>" class="btn btn-danger" onclick="return confirm('Hapus pengajuan ini?');">Hapus</a>
        </div></td>
			</tr>
			<tr class="detail-row"><td colspan="6"><div id="detail-<?php echo (int)$d['kode_pinjam']; ?>" class="collapse"><div class="detail-panel">
          <div class="detail-item"><label>Instansi</label><strong><?php echo htmlspecialchars($d['instansi_peminjam'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
          <div class="detail-item"><label>Nama Ruang</label><strong><?php echo htmlspecialchars($d['nama_ruang'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
          <div class="detail-item"><label>Kegiatan</label><strong><?php echo htmlspecialchars($d['nama_kegiatan'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
          <div class="detail-item"><label>Surat Permohonan</label><a class="detail-document" target="_blank" href="surat_permohonan.php?kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>"><span class="glyphicon glyphicon-print"></span> Cetak Surat</a></div>
          <div class="detail-item"><label>Surat Persetujuan</label><?php if ($d['status']=='Disetujui' || $d['status']=='Selesai'): ?><a class="detail-document" target="_blank" href="surat_persetujuan.php?kode_pinjam=<?php echo (int)$d['kode_pinjam']; ?>"><span class="glyphicon glyphicon-print"></span> Cetak Surat</a><?php else: ?><strong class="text-muted">Belum tersedia</strong><?php endif; ?></div>
          <div class="detail-item"><label>Keterangan</label><strong><?php echo htmlspecialchars($d['keterangan'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong></div>
        </div></div></td></tr>
			<?php endwhile; ?>
    </tbody>
    </table>
    </div>
    <div class="table-footer"><span>Menampilkan <?php echo $totalRows ? ($offset + 1) . '–' . min($offset + $perPage, $totalRows) : 0; ?> dari <?php echo $totalRows; ?> pengajuan</span><ul class="pagination">
      <?php $pageStart = max(1, $page - 2); $pageEnd = min($totalPages, $page + 2); if ($pageStart > 1): ?><li><a href="?halaman=1&cari=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($statusFilter); ?>">1</a></li><?php if ($pageStart > 2): ?><li class="disabled"><span>…</span></li><?php endif; ?><?php endif; ?>
      <?php for ($i=$pageStart; $i <= $pageEnd; $i++): ?><li class="<?php echo $i === $page ? 'active' : ''; ?>"><a href="?halaman=<?php echo $i; ?>&cari=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
      <?php if ($pageEnd < $totalPages): ?><?php if ($pageEnd < $totalPages - 1): ?><li class="disabled"><span>…</span></li><?php endif; ?><li><a href="?halaman=<?php echo $totalPages; ?>&cari=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($statusFilter); ?>"><?php echo $totalPages; ?></a></li><?php endif; ?>
    </ul></div>
    <div style="margin-top:18px;"><a target="_blank" href="export_excel.php" class="btn btn-success"><span class="glyphicon glyphicon-download-alt"></span> Export ke Excel</a></div>
  </section>
</main>
</body>
</html>
