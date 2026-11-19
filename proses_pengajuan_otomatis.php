<?php
// Menutup pengajuan yang belum diproses ketika waktu pelaksanaannya sudah lewat.
// Riwayat tetap disimpan; hanya status dan keterangannya yang diperbarui.
if (isset($koneksi) && $koneksi) {
    mysqli_query($koneksi, "UPDATE meminjam
        SET status = 'Selesai', color = '#2474c6'
        WHERE status = 'Disetujui'
          AND ((TIME(end_date) = '00:00:00' AND DATE(end_date) < CURDATE())
            OR (TIME(end_date) <> '00:00:00' AND end_date < NOW()))");

    mysqli_query($koneksi, "UPDATE meminjam
        SET status = 'Batal',
            color = '#788492',
            keterangan = CASE
                WHEN keterangan IS NULL OR TRIM(keterangan) = ''
                    THEN 'Tidak diproses sampai batas waktu'
                ELSE CONCAT(keterangan, ' | Tidak diproses sampai batas waktu')
            END
        WHERE status = 'Diajukan'
          AND ((TIME(start_date) = '00:00:00' AND DATE(start_date) < CURDATE())
            OR (TIME(start_date) <> '00:00:00' AND start_date < NOW()))");
}
?>
