<?php
// Preview PDF (dengan tombol Cetak & Unduh) - tidak langsung mengunduh
require __DIR__ . '/config.php';

$id = (int)g($_GET, 'id', 0);
$s = $pdo->prepare("SELECT d.nama_dokumen, u.nama AS unit_spo FROM dokumen d JOIN unit_spo u ON u.id=d.unit_spo_id WHERE d.id=?");
$s->execute(array($id));
$d = $s->fetch();
if (!$d) { flash('Dokumen tidak ditemukan.', 'danger'); header('Location: index.php'); exit; }
$src = 'download.php?id=' . $id;
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($d['nama_dokumen']) ?> - Preview</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
<meta name="theme-color" content="#006838">
<link rel="icon" type="image/svg+xml" href="favicon/favicon.svg">
<link rel="icon" type="image/png" href="favicon/favicon.png">
<link rel="shortcut icon" href="favicon/favicon.ico">
</head><body class="preview-page">
<div class="glass-nav preview-bar">
  <a href="index.php" id="btnBack" class="btn btn-sm btn-light">&larr; Kembali</a>
  <div class="preview-title text-truncate">
    <strong><?= e($d['nama_dokumen']) ?></strong>
    <span class="small opacity-75 ms-2"><?= e($d['unit_spo']) ?></span>
  </div>
  <div class="d-flex gap-2">
    <button type="button" id="btnPrint" class="btn btn-sm btn-light">Cetak</button>
    <a href="<?= e($src) ?>" class="btn btn-sm btn-outline-light">Unduh</a>
  </div>
</div>
<iframe id="pdfFrame" class="preview-frame" src="<?= e($src) ?>&amp;inline=1" title="<?= e($d['nama_dokumen']) ?>"></iframe>
<script>
document.getElementById('btnBack').addEventListener('click', function (ev) {
  ev.preventDefault();
  if (history.length > 1) { history.back(); return; }
  window.close();
  setTimeout(function () { location.href = 'index.php'; }, 200);
});
document.getElementById('btnPrint').addEventListener('click', function () {
  var f = document.getElementById('pdfFrame');
  try { f.contentWindow.focus(); f.contentWindow.print(); }
  catch (e) { alert('Gunakan tombol cetak pada penampil PDF di bawah.'); }
});
</script>
</body></html>
