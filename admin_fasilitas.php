<?php
session_start();
include 'koneksi.php';

if (empty($_SESSION['username']) || empty($_SESSION['password'])) {
    header('Location: cek_login.php');
    exit;
}

$message = '';
$messageType = 'success';
$editRoom = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'delete') {
        $id = (int) ($_POST['id_ruang'] ?? 0);
        $stmt = mysqli_prepare($koneksi, 'DELETE FROM ruangan WHERE id_ruang = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: admin_fasilitas.php?pesan=deleted');
        exit;
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id_ruang'] ?? 0);
        $nama = trim($_POST['nama_ruang'] ?? '');
        $gedung = trim($_POST['gedung'] ?? '');
        $lantai = trim($_POST['lantai'] ?? '');
        $fasilitas1 = trim($_POST['fasilitas1'] ?? '');
        $fasilitas2 = trim($_POST['fasilitas2'] ?? '');
        $fasilitas3 = trim($_POST['fasilitas3'] ?? '');
        $status = in_array($_POST['status'] ?? '', array('Tersedia', 'Tidak Tersedia'), true) ? $_POST['status'] : 'Tersedia';

        if ($nama === '' || $gedung === '' || $lantai === '') {
            $message = 'Nama ruang, gedung, dan lantai wajib diisi.';
            $messageType = 'danger';
            $editRoom = $_POST;
        } elseif ($id > 0) {
            $stmt = mysqli_prepare($koneksi, 'UPDATE ruangan SET nama_ruang=?, gedung=?, lantai=?, fasilitas1=?, fasilitas2=?, fasilitas3=?, status=? WHERE id_ruang=?');
            mysqli_stmt_bind_param($stmt, 'sssssssi', $nama, $gedung, $lantai, $fasilitas1, $fasilitas2, $fasilitas3, $status, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header('Location: admin_fasilitas.php?pesan=updated');
            exit;
        } else {
            $stmt = mysqli_prepare($koneksi, 'INSERT INTO ruangan (nama_ruang, gedung, lantai, fasilitas1, fasilitas2, fasilitas3, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sssssss', $nama, $gedung, $lantai, $fasilitas1, $fasilitas2, $fasilitas3, $status);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header('Location: admin_fasilitas.php?pesan=created');
            exit;
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($koneksi, 'SELECT * FROM ruangan WHERE id_ruang=?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editRoom = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$rooms = mysqli_query($koneksi, 'SELECT * FROM ruangan ORDER BY nama_ruang ASC');
$messages = array(
    'created' => 'Ruangan berhasil ditambahkan.',
    'updated' => 'Data ruangan berhasil diperbarui.',
    'deleted' => 'Ruangan berhasil dihapus.'
);
$notice = isset($_GET['pesan'], $messages[$_GET['pesan']]) ? $messages[$_GET['pesan']] : $message;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <title>[Admin] Fasilitas Ruangan | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="./style.css" rel="stylesheet">
  <link href="./template.css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <style>
    body{background:#f4f7fb;color:#19324d}.navbar{position:sticky;top:0;min-height:64px;background:#fff;box-shadow:0 3px 18px rgba(25,50,77,.08)}.navbar li a{color:#3d5369;padding-top:22px;padding-bottom:22px}.navbar li a:hover{background:#eef5fb;color:#087f8c}.icon-bar{background:#163b63}
    .facility-page{max-width:1200px;margin:0 auto;padding:35px 20px 55px}.facility-head{display:flex;align-items:end;justify-content:space-between;gap:15px;margin-bottom:22px}.facility-head h1{margin:0 0 7px;color:#193b59;font-size:29px;font-weight:700}.facility-head p{margin:0;color:#718196}.facility-card{padding:20px;border:1px solid #e3eaf1;border-radius:15px;background:#fff;box-shadow:0 7px 22px rgba(25,50,77,.05)}.facility-table{min-width:860px;margin:0}.table-wrap{overflow-x:auto}.facility-table thead th{padding:13px 11px;border:0;background:#eef5f8;color:#52677c;font-size:12px;text-transform:uppercase;white-space:nowrap}.facility-table tbody td{padding:13px 11px;border-color:#edf1f5;vertical-align:middle}.facility-table tbody tr:hover{background:#f8fbfc}.status-pill{display:inline-block;padding:5px 10px;border-radius:20px;font-size:12px;font-weight:700}.available{background:#e4f5eb;color:#26704b}.unavailable{background:#fff0f0;color:#a34b4b}.action-group{display:flex;gap:6px}.action-group .btn{border:0;border-radius:6px;font-size:11px}.empty-state{padding:40px!important;text-align:center;color:#8190a1}.form-control{border-radius:8px;box-shadow:none}.modal-content{border-radius:13px}.modal-header{border-bottom:1px solid #edf1f5}.modal-footer{border-top:1px solid #edf1f5}@media(max-width:767px){.facility-page{padding:24px 13px 40px}.facility-head{display:block}.facility-head h1{font-size:24px}.facility-card{padding:13px}}
  </style>
</head>
<body>
<nav class="navbar"><div class="container-fluid"><div class="navbar-header"><button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button></div><div class="collapse navbar-collapse" id="myNavbar"><ul class="nav navbar-nav"><li class="active"><a href="admin_menu.php">Menu Admin</a></li><li><a href="admin_jadwal.php">Jadwal</a></li></ul><ul class="nav navbar-nav navbar-right"><li><a href="logout.php"><span class="glyphicon glyphicon-log-out"></span> Log Out</a></li></ul></div></div></nav>

<main class="facility-page">
  <header class="facility-head"><div><h1>Fasilitas Ruangan</h1><p>Tambah, ubah, atau hapus informasi ruangan yang tersedia.</p></div><button class="btn btn-primary" data-toggle="modal" data-target="#roomModal"><span class="glyphicon glyphicon-plus"></span> Tambah Ruangan</button></header>
  <?php if ($notice !== ''): ?><div class="alert alert-<?php echo $messageType; ?>"><span class="glyphicon glyphicon-ok-circle"></span> <?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
  <section class="facility-card"><div class="table-wrap"><table class="table facility-table"><thead><tr><th>ID</th><th>Nama Ruang</th><th>Lokasi</th><th>Lantai</th><th>Meja</th><th>Kursi</th><th>LCD</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
  <?php if (!$rooms || mysqli_num_rows($rooms) === 0): ?><tr><td colspan="9" class="empty-state">Belum ada data ruangan.</td></tr><?php endif; ?>
  <?php while ($room = mysqli_fetch_assoc($rooms)): ?><tr>
    <td><?php echo (int)$room['id_ruang']; ?></td><td><strong><?php echo htmlspecialchars($room['nama_ruang'], ENT_QUOTES, 'UTF-8'); ?></strong></td><td><?php echo htmlspecialchars($room['gedung'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($room['lantai'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($room['fasilitas1'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($room['fasilitas2'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($room['fasilitas3'], ENT_QUOTES, 'UTF-8'); ?></td><td><span class="status-pill <?php echo $room['status'] === 'Tersedia' ? 'available' : 'unavailable'; ?>"><?php echo htmlspecialchars($room['status'], ENT_QUOTES, 'UTF-8'); ?></span></td><td><div class="action-group"><a class="btn btn-warning" href="admin_fasilitas.php?edit=<?php echo (int)$room['id_ruang']; ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus ruangan ini?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_ruang" value="<?php echo (int)$room['id_ruang']; ?>"><button class="btn btn-danger" type="submit">Hapus</button></form></div></td>
  </tr><?php endwhile; ?></tbody></table></div></section>
</main>

<div class="modal fade" id="roomModal" tabindex="-1" role="dialog" aria-labelledby="roomModalTitle"><div class="modal-dialog" role="document"><div class="modal-content"><form method="post">
  <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button><h4 class="modal-title" id="roomModalTitle"><?php echo $editRoom ? 'Edit Ruangan' : 'Tambah Ruangan'; ?></h4></div>
  <div class="modal-body"><input type="hidden" name="action" value="save"><input type="hidden" name="id_ruang" value="<?php echo (int)($editRoom['id_ruang'] ?? 0); ?>">
    <?php $fields = array('nama_ruang' => 'Nama Ruang', 'gedung' => 'Gedung/Lokasi', 'lantai' => 'Lantai', 'fasilitas1' => 'Fasilitas Meja', 'fasilitas2' => 'Fasilitas Kursi', 'fasilitas3' => 'Fasilitas LCD'); foreach ($fields as $field => $label): ?><div class="form-group"><label><?php echo $label; ?><?php echo in_array($field, array('nama_ruang','gedung','lantai'), true) ? ' *' : ''; ?></label><input class="form-control" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($editRoom[$field] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($field, array('nama_ruang','gedung','lantai'), true) ? 'required' : ''; ?>></div><?php endforeach; ?>
    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Tersedia" <?php echo (($editRoom['status'] ?? '') === 'Tersedia') ? 'selected' : ''; ?>>Tersedia</option><option value="Tidak Tersedia" <?php echo (($editRoom['status'] ?? '') === 'Tidak Tersedia') ? 'selected' : ''; ?>>Tidak Tersedia</option></select></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span> Simpan</button></div>
</form></div></div></div>
<?php if ($editRoom || $messageType === 'danger'): ?><script>$(function(){ $('#roomModal').modal('show'); });</script><?php endif; ?>
</body></html>
