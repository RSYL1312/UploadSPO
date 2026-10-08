<?php
require 'config.php';
$id = (int)g($_GET, 'id', 0);
$doc = null;
if ($id) {
    $s = $pdo->prepare("SELECT * FROM dokumen WHERE id=?"); $s->execute([$id]); $doc = $s->fetch();
    if (!$doc) { flash('Dokumen tidak ditemukan.', 'danger'); header('Location: index.php'); exit; }
}
$kid  = g($doc, 'unit_spo_id', 0);
$nama = g($doc, 'nama_dokumen', '');
$sel  = $id ? $pdo->query("SELECT unit_id FROM dokumen_unit WHERE dokumen_id=$id")->fetchAll(PDO::FETCH_COLUMN) : [];
$err  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kid  = (int)g($_POST, 'unit_spo_id', 0);
    $nama = trim(g($_POST, 'nama_dokumen', ''));
    $sel  = array_map('intval', g($_POST, 'unit', array()));
    $f    = g($_FILES, 'pdf', array('error' => UPLOAD_ERR_NO_FILE));
    $has  = $f['error'] === UPLOAD_ERR_OK;

    if (!$kid)  $err[] = 'Unit SPO wajib dipilih.';
    if ($nama === '') $err[] = 'Nama dokumen wajib diisi.';
    if (!$id && !$has) $err[] = 'File PDF wajib diupload.';
    if ($has) {
        // Validasi: ekstensi .pdf + penanda "%PDF-" di 1024 byte pertama (sesuai spesifikasi PDF).
        // Tidak memakai MIME dari finfo saja karena sebagian PDF terdeteksi sebagai application/octet-stream.
        $extOk  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) === 'pdf';
        $head   = (string)file_get_contents($f['tmp_name'], false, null, 0, 1024);
        if (!$extOk || strpos($head, '%PDF-') === false) $err[] = 'File harus berformat PDF.';
        if ($f['size'] > MAX_UPLOAD) $err[] = 'Ukuran file maksimal 5 MB.';
    } elseif ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        $err[] = 'Ukuran file melebihi batas server. Pastikan maksimal 5 MB dan upload_max_filesize di php.ini minimal 5M.';
    } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
        $err[] = 'Upload gagal (kode ' . $f['error'] . ').';
    }

    if (!$err) {
        $k = $pdo->prepare("SELECT folder FROM unit_spo WHERE id=?"); $k->execute([$kid]);
        $folder = $k->fetchColumn();
        if (!is_dir(UPLOAD_DIR . $folder)) mkdir(UPLOAD_DIR . $folder, 0775, true);
        try {
            $pdo->beginTransaction();
            $path = g($doc, 'file_path', '');
            if ($has) {
                $newPath = "$folder/" . unique_name(UPLOAD_DIR . $folder, clean_filename($f['name']), $path ? UPLOAD_DIR . $path : '');
                move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $newPath);
                if ($path && $path !== $newPath) @unlink(UPLOAD_DIR . $path);
                $path = $newPath;
            } elseif ($id && $kid != $doc['unit_spo_id']) {   // unit_spo berubah -> pindahkan file
                $newPath = "$folder/" . unique_name(UPLOAD_DIR . $folder, basename($path));
                rename(UPLOAD_DIR . $path, UPLOAD_DIR . $newPath);
                $path = $newPath;
            }
            if ($id) {
                $pdo->prepare("UPDATE dokumen SET unit_spo_id=?, nama_dokumen=?, file_path=? WHERE id=?")->execute([$kid, $nama, $path, $id]);
            } else {
                $pdo->prepare("INSERT INTO dokumen (unit_spo_id, nama_dokumen, file_path) VALUES (?,?,?)")->execute([$kid, $nama, $path]);
                $id = $pdo->lastInsertId();
            }
            $pdo->prepare("DELETE FROM dokumen_unit WHERE dokumen_id=?")->execute([$id]);
            $ins = $pdo->prepare("INSERT INTO dokumen_unit (dokumen_id, unit_id) VALUES (?,?)");
            foreach (array_unique($sel) as $u) $ins->execute([$id, $u]);
            $pdo->commit();
            flash('Dokumen berhasil disimpan.');
            header('Location: index.php'); exit;
        } catch (Exception $ex) {
            $pdo->rollBack();
            $err[] = 'Gagal menyimpan: ' . $ex->getMessage();
        }
    }
}

$unit_spo = $pdo->query("SELECT * FROM unit_spo ORDER BY nama")->fetchAll();
$unitAll  = $pdo->query("SELECT * FROM unit ORDER BY nama")->fetchAll();

layout_top($doc ? 'Edit Dokumen' : 'Tambah Dokumen'); ?>
<h4 class="mb-3"><?= $doc ? 'Edit' : 'Tambah' ?> Dokumen</h4>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="card card-body">
  <div class="mb-3">
    <label class="form-label">Unit SPO</label>
    <select name="unit_spo_id" class="form-select" required>
      <option value="">-- Pilih Unit SPO --</option>
      <?php foreach ($unit_spo as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $kid == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Unit Terkait <a href="master.php?t=unit" class="small ms-2">+ kelola unit</a></label>
    <div class="row row-cols-2 row-cols-md-4 g-1 border rounded p-2">
      <?php foreach ($unitAll as $u): ?>
        <div class="col"><div class="form-check">
          <input class="form-check-input" type="checkbox" name="unit[]" value="<?= $u['id'] ?>" id="u<?= $u['id'] ?>" <?= in_array($u['id'], $sel) ? 'checked' : '' ?>>
          <label class="form-check-label" for="u<?= $u['id'] ?>"><?= e($u['nama']) ?></label>
        </div></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Nama Dokumen</label>
    <input type="text" name="nama_dokumen" value="<?= e($nama) ?>" class="form-control" required maxlength="255">
  </div>
  <div class="mb-3">
    <label class="form-label">File PDF (maks. 5 MB)</label>
    <input type="file" name="pdf" accept="application/pdf" class="form-control" <?= $doc ? '' : 'required' ?>>
    <?php if ($doc): ?><div class="form-text">File saat ini: <a href="uploads/<?= e($doc['file_path']) ?>" target="_blank"><?= e(basename($doc['file_path'])) ?></a>. Kosongkan jika tidak ingin mengganti.</div><?php endif; ?>
  </div>
  <div><button class="btn btn-primary">Simpan</button> <a href="index.php" class="btn btn-secondary">Batal</a></div>
</form>
<?php layout_bottom();
