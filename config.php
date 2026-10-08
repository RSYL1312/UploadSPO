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
// Bersihkan nama file upload tanpa mengenkripsi/mengubah namanya (hanya membuang karakter terlarang)
function clean_filename($name) {
    $name = str_replace('\\', '/', $name);
    $name = basename($name);
    $name = str_replace(array('/', ':', '*', '?', '"', '<', '>', '|'), '', $name);
    $name = preg_replace('/[\x00-\x1F]/', '', $name);
    $name = trim($name, " .");
    return $name === '' ? 'dokumen.pdf' : $name;
}
// Jika nama sudah ada di folder, tambahkan " (1)", " (2)", dst. agar tidak menimpa file lain
function unique_name($dir, $name, $ignore = '') {
    $info = pathinfo($name);
    $base = $info['filename'];
    $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';
    $try = $name; $i = 1;
    while (file_exists($dir . '/' . $try) && $dir . '/' . $try !== $ignore) {
        $try = $base . ' (' . $i . ')' . $ext;
        $i++;
    }
    return $try;
}
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
<link rel="icon" type="image/png" href="favicon/logors.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
<meta name="theme-color" content="#006838">
</head><body>
<nav class="navbar navbar-expand navbar-dark glass-nav mb-4"><div class="container">
  <a class="navbar-brand" href="index.php">Dokumen SPO</a>
  <div class="navbar-nav">
<?php
    $cur = basename($_SERVER['SCRIPT_NAME']);
    $menu = array(
        array('index.php', 'Beranda', ''),
        array('dokumen_form.php', 'Tambah Dokumen', ''),
        array('master.php?t=unit_spo', 'Kelola Unit SPO', 'unit_spo'),
        array('master.php?t=unit', 'Kelola Unit Terkait', 'unit'),
    );
    foreach ($menu as $m) {
        $file = explode('?', $m[0]); $file = $file[0];
        if ($file === 'index.php')            $on = ($cur === 'index.php' || ($cur === 'dokumen_form.php' && g($_GET, 'id')));
        elseif ($file === 'dokumen_form.php') $on = ($cur === $file && !g($_GET, 'id'));
        else                                  $on = ($cur === $file && g($_GET, 't', 'unit_spo') === $m[2]);
        echo '    <a class="nav-link' . ($on ? ' active' : '') . '" href="' . $m[0] . '"' . ($on ? ' aria-current="page"' : '') . '>' . $m[1] . '</a>' . "\n";
    }
?>
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
