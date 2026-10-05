<?php
session_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_spo;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) { die('Koneksi database gagal: ' . e($ex->getMessage())); }

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD', 5 * 1024 * 1024); // 5 MB

// Pengganti operator ?? (PHP 7+) agar kompatibel dengan PHP 5.4
function g($arr, $key, $default = null) { return (is_array($arr) && isset($arr[$key])) ? $arr[$key] : $default; }
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function slug($s) { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-') ?: 'file'; }
function flash($msg = null, $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return ''; }
    if (!isset($_SESSION['flash'])) return '';
    $m = $_SESSION['flash'][0]; $t = $_SESSION['flash'][1]; unset($_SESSION['flash']);
    return "<div class='alert alert-$t alert-dismissible'>" . e($m) . "<button class='btn-close' data-bs-dismiss='alert'></button></div>";
}
function layout_top($title) { ?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - SPO</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<nav class="navbar navbar-expand-md navbar-dark bg-primary mb-4"><div class="container">
  <a class="navbar-brand" href="index.php">Dokumen SPO</a>
  <div class="navbar-nav">
    <a class="nav-link" href="index.php">Beranda</a>
    <a class="nav-link" href="dokumen_form.php">Tambah Dokumen</a>
    <a class="nav-link" href="master.php?t=unit_spo">Kelola Unit SPO</a>
    <a class="nav-link" href="master.php?t=unit">Kelola Unit Terkait</a>
  </div>
</div></nav>
<div class="container pb-5">
<?= flash() ?>
<?php }
function layout_bottom() { ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
<?php }
