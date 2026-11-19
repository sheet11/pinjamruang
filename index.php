<!DOCTYPE html>
<html lang="en">
<head>
  <title>Menu Utama | Poltekkes Kemenkes Bengkulu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="./style.css" type="text/css" rel="stylesheet">
  <link href="./template.css" type="text/css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script>
    (function($, window) {
    'use strict';

    var MultiModal = function(element) {
        this.$element = $(element);
        this.modalCount = 0;
    };

    MultiModal.BASE_ZINDEX = 1040;

    MultiModal.prototype.show = function(target) {
        var that = this;
        var $target = $(target);
        var modalIndex = that.modalCount++;

        $target.css('z-index', MultiModal.BASE_ZINDEX + (modalIndex * 20) + 10);

        // Bootstrap triggers the show event at the beginning of the show function and before
        // the modal backdrop element has been created. The timeout here allows the modal
        // show function to complete, after which the modal backdrop will have been created
        // and appended to the DOM.
        window.setTimeout(function() {
            // we only want one backdrop; hide any extras
            if(modalIndex > 0)
                $('.modal-backdrop').not(':first').addClass('hidden');

            that.adjustBackdrop();
        });
    };

    MultiModal.prototype.hidden = function(target) {
        this.modalCount--;

        if(this.modalCount) {
           this.adjustBackdrop();

            // bootstrap removes the modal-open class when a modal is closed; add it back
            $('body').addClass('modal-open');
        }
    };

    MultiModal.prototype.adjustBackdrop = function() {
        var modalIndex = this.modalCount - 1;
        $('.modal-backdrop:first').css('z-index', MultiModal.BASE_ZINDEX + (modalIndex * 20));
    };

    function Plugin(method, target) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('multi-modal-plugin');

            if(!data)
                $this.data('multi-modal-plugin', (data = new MultiModal(this)));

            if(method)
                data[method](target);
        });
    }

    $.fn.multiModal = Plugin;
    $.fn.multiModal.Constructor = MultiModal;

    $(document).on('show.bs.modal', function(e) {
        $(document).multiModal('show', e.target);
    });

    $(document).on('hidden.bs.modal', function(e) {
        $(document).multiModal('hidden', e.target);
    });
}(jQuery, window));
  </script>
</head>





<body class="home-page">
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
        <li class="active"><a href="index.php">Beranda</a></li>
        <li><a href="user_jadwal.php">Kalender</a></li>
        <li><a href="user_peminjaman.php">Ajukan Peminjaman</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="kontak.php">Kontak</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a id="login" href="login.php"><span class="glyphicon glyphicon-log-in"></span> Admin</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="home-wrap">
  <section class="home-hero" aria-labelledby="page-title">
    <div class="eyebrow">Poltekkes Kemenkes Bengkulu</div>
    <h1 id="page-title">Temukan ruang, mulai kegiatan.</h1>
    <p>Jelajahi jadwal ruangan dan ajukan peminjaman dengan mudah melalui satu tempat.</p>
    <div class="home-actions">
      <a class="primary-action" href="user_peminjaman.php"><span class="glyphicon glyphicon-edit"></span> Ajukan Peminjaman</a>
      <a class="secondary-action" href="user_jadwal.php"><span class="glyphicon glyphicon-calendar"></span> Lihat Kalender</a>
    </div>
  </section>
  <section aria-labelledby="room-title">
    <div class="section-heading"><div><h2 id="room-title">Jelajahi Ruangan</h2><p>Pilih ruang untuk melihat informasi dan detailnya.</p></div></div>
    <div class="room-grid">
      <a class="room-card" href="user_detail_osca.php"><span class="room-icon glyphicon glyphicon-blackboard"></span><span><strong>Lab OSCA</strong><small>Laboratorium keterampilan</small></span></a>
      <a class="room-card" href="user_detail_labkom.php"><span class="room-icon glyphicon glyphicon-blackboard"></span><span><strong>Lab Komputer</strong><small>Laboratorium komputer</small></span></a>
      <a class="room-card" href="user_detail_teater.php"><span class="room-icon glyphicon glyphicon-blackboard"></span><span><strong>Ruang Teater</strong><small>Ruang kegiatan dan acara</small></span></a>
      <a class="room-card" href="user_detail_upbahasa.php"><span class="room-icon glyphicon glyphicon-blackboard"></span><span><strong>Unit Pengembangan Bahasa</strong><small>Ruang Unit Pengembangan Bahasa</small></span></a>
      <a class="room-card" href="user_detail_labcurup.php"><span class="room-icon glyphicon glyphicon-blackboard"></span><span><strong>Lab Komputer Curup</strong><small>Laboratorium komputer Curup</small></span></a>
    </div>
  </section>
  <footer class="home-footer">Poltekkes Kemenkes Bengkulu &middot; Layanan Peminjaman Ruangan</footer>
</main>
</body>
</html>
