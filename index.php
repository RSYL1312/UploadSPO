<?php
require 'config.php';

// Hapus dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int)$_POST['hapus'];
    $s = $pdo->prepare("SELECT file_path FROM dokumen WHERE id=?"); $s->execute([$id]);
    if ($d = $s->fetch()) {
        $pdo->prepare("DELETE FROM dokumen WHERE id=?")->execute([$id]);
        @unlink(UPLOAD_DIR . $d['file_path']);
        flash('Dokumen berhasil dihapus.');
    }
    header('Location: index.php'); exit;
}

$q     = trim(g($_GET, 'q', ''));
$kat   = (int)g($_GET, 'unit_spo', 0);
$units = array_values(array_filter(array_map('intval', g($_GET, 'unit', array()))));

$sql = "SELECT d.*, k.nama AS unit_spo,
        (SELECT GROUP_CONCAT(u.nama ORDER BY u.nama SEPARATOR ', ') FROM dokumen_unit du
         JOIN unit u ON u.id=du.unit_id WHERE du.dokumen_id=d.id) AS units
        FROM dokumen d JOIN unit_spo k ON k.id=d.unit_spo_id WHERE 1=1";
$p = [];
if ($q !== '') {
    // Cari di kolom Unit SPO, Nama Dokumen, dan Unit Terkait
    $sql .= " AND (d.nama_dokumen LIKE ? OR k.nama LIKE ?
              OR EXISTS (SELECT 1 FROM dokumen_unit y JOIN unit u2 ON u2.id=y.unit_id WHERE y.dokumen_id=d.id AND u2.nama LIKE ?))";
    array_push($p, "%$q%", "%$q%", "%$q%");
}
$condSpo = '';
$condUnit = '';
if ($kat) { $condSpo = "d.unit_spo_id = ?"; $p[] = $kat; }
if ($units) {
    $units = array_values(array_unique($units));
    // Beberapa Unit Terkait: dokumen harus terkait dengan SEMUA unit yang dipilih
    $condUnit = "(SELECT COUNT(DISTINCT x.unit_id) FROM dokumen_unit x WHERE x.dokumen_id=d.id AND x.unit_id IN (" . implode(',', array_fill(0, count($units), '?')) . ")) = ?";
    $p = array_merge($p, $units, array(count($units)));
}
// Jika Unit SPO dan Unit Terkait sama-sama dipilih -> logika ATAU
if ($condSpo && $condUnit)  $sql .= " AND ($condSpo OR $condUnit)";
elseif ($condSpo)           $sql .= " AND $condSpo";
elseif ($condUnit)          $sql .= " AND $condUnit";
$sql .= " ORDER BY d.created_at DESC";
$st = $pdo->prepare($sql); $st->execute($p); $rows = $st->fetchAll();

$unit_spo = $pdo->query("SELECT * FROM unit_spo ORDER BY nama")->fetchAll();
$unitAll  = $pdo->query("SELECT * FROM unit ORDER BY nama")->fetchAll();

layout_top('Beranda'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Daftar Dokumen SPO</h4>
  <a href="dokumen_form.php" class="btn btn-primary">+ Tambah Dokumen</a>
</div>

<form method="get" class="card card-body mb-3">
  <div class="row g-2">
    <div class="col-md-5"><input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari unit SPO, nama dokumen, atau unit terkait..."></div>
    <div class="col-md-4">
      <select name="unit_spo" class="form-select">
        <option value="">Semua Unit SPO</option>
        <?php foreach ($unit_spo as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $kat == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-grid d-md-flex gap-2">
      <button class="btn btn-primary flex-fill">Cari</button>
      <a href="index.php" class="btn btn-outline-secondary flex-fill">Reset</a>
    </div>
  </div>
  <div class="mt-2">
    <a data-bs-toggle="collapse" href="#filterUnit" class="small">Filter Unit Terkait <?= $units ? '(' . count($units) . ' dipilih)' : '' ?> ▾</a>
    <div class="collapse <?= $units ? 'show' : '' ?> mt-2" id="filterUnit">
      <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-1">
        <?php foreach ($unitAll as $u): ?>
          <div class="col"><div class="form-check">
            <input class="form-check-input" type="checkbox" name="unit[]" value="<?= $u['id'] ?>" id="fu<?= $u['id'] ?>" <?= in_array($u['id'], $units) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="fu<?= $u['id'] ?>"><?= e($u['nama']) ?></label>
          </div></div>
        <?php endforeach; ?>
      </div>
      <div class="form-text">Jika Unit SPO dan Unit Terkait sama-sama dipilih, dokumen tampil bila cocok dengan Unit SPO <b>ATAU</b> Unit Terkait. Jika beberapa Unit Terkait dipilih, dokumen harus terkait dengan semuanya.</div>
    </div>
  </div>
</form>

<div class="card"><div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
  <thead class="table-dark"><tr>
    <th>No</th><th>Unit SPO</th><th>Nama Dokumen</th><th>Unit Terkait</th><th>Tanggal Input</th><th>Download</th><th>Aksi</th>
  </tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Tidak ada dokumen.</td></tr><?php endif; ?>
  <?php foreach ($rows as $i => $r): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= e($r['unit_spo']) ?></td>
      <td><?= e($r['nama_dokumen']) ?></td>
      <td><?= e($r['units'] ?: '-') ?></td>
      <td><?= date('d-m-Y', strtotime($r['created_at'])) ?></td>
      <td><a href="download.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger">PDF</a></td>
      <td class="text-nowrap">
        <a href="dokumen_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">EDIT</a>
        <form method="post" class="d-inline" onsubmit="return confirm('Hapus dokumen ini?')">
          <button name="hapus" value="<?= $r['id'] ?>" class="btn btn-sm btn-danger">HAPUS</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php layout_bottom();
