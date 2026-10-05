<?php
// Download PDF dengan nama file sama persis dengan Nama Dokumen
require 'config.php';

$id = (int)g($_GET, 'id', 0);
$s = $pdo->prepare("SELECT nama_dokumen, file_path FROM dokumen WHERE id=?");
$s->execute(array($id));
$d = $s->fetch();
$file = $d ? UPLOAD_DIR . $d['file_path'] : '';

if (!$d || !is_file($file)) {
    http_response_code(404);
    die('File tidak ditemukan.');
}

// Bersihkan karakter yang tidak boleh ada pada nama file (spasi dan huruf besar/kecil tetap dipertahankan)
$nama = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]/', '', $d['nama_dokumen']);
$nama = trim(preg_replace('/\s+/', ' ', $nama), " .");
if ($nama === '') $nama = 'dokumen';
$nama = (function_exists('mb_substr') ? mb_substr($nama, 0, 150, 'UTF-8') : substr($nama, 0, 150)) . '.pdf';

$fallback = preg_replace('/[^\x20-\x7E]|%/', '_', $nama);   // untuk browser lama

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($nama));
header('Content-Length: ' . filesize($file));
header('X-Content-Type-Options: nosniff');
readfile($file);
exit;
