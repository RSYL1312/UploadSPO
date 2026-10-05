<?php
// Kelola master data: Unit SPO & Unit Terkait
require 'config.php';
$t = g($_GET, 't', 'unit_spo') === 'unit' ? 'unit' : 'unit_spo';
$label = $t === 'unit' ? 'Unit Terkait' : 'Unit SPO';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = g($_POST, 'aksi', '');
    $nama = trim(g($_POST, 'nama', ''));
    $id   = (int)g($_POST, 'id', 0);
    try {
        if ($aksi === 'tambah' && $nama !== '') {
            if ($t === 'unit_spo') {
                $folder = slug($nama);
                $pdo->prepare("INSERT INTO unit_spo (nama, folder) VALUES (?,?)")->execute([$nama, $folder]);
                if (!is_dir(UPLOAD_DIR . $folder)) mkdir(UPLOAD_DIR . $folder, 0775, true);
            } else {
                $pdo->prepare("INSERT INTO unit (nama) VALUES (?)")->execute([$nama]);
            }
            flash("$label berhasil ditambahkan.");
        } elseif ($aksi === 'ubah' && $nama !== '') {
            $pdo->prepare("UPDATE $t SET nama=? WHERE id=?")->execute([$nama, $id]);   // nama folder tidak berubah
            flash("$label berhasil diubah.");
        } elseif ($aksi === 'hapus') {
            $pdo->prepare("DELETE FROM $t WHERE id=?")->execute([$id]);
            flash("$label berhasil dihapus.");
        }
    } catch (PDOException $ex) {
        $dup = $ex->errorInfo[1] == 1062;
        flash($dup ? "$label sudah ada." : "$label tidak dapat dihapus karena masih dipakai dokumen.", 'danger');
    }
    header("Location: master.php?t=$t"); exit;
}

$rows = $pdo->query("SELECT * FROM $t ORDER BY nama")->fetchAll();
layout_top("Kelola $label"); ?>
<h4 class="mb-3">Kelola <?= $label ?></h4>
<form method="post" class="card card-body mb-3">
  <input type="hidden" name="aksi" value="tambah">
  <div class="input-group">
    <input type="text" name="nama" class="form-control" placeholder="Nama <?= strtolower($label) ?> baru" required maxlength="100">
    <button class="btn btn-primary">Tambah</button>
  </div>
  <?php if ($t === 'unit_spo'): ?><div class="form-text">Folder penyimpanan PDF dibuat otomatis sesuai nama unit SPO.</div><?php endif; ?>
</form>
<div class="card"><table class="table table-striped align-middle mb-0">
  <thead class="table-dark"><tr><th style="width:60px">No</th><th>Nama</th><th style="width:220px">Aksi</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $i => $r): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td>
        <form method="post" id="f<?= $r['id'] ?>" class="d-flex gap-2">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <input type="text" name="nama" value="<?= e($r['nama']) ?>" class="form-control form-control-sm" required>
          <?php if ($t === 'unit_spo'): ?><span class="badge text-bg-light align-self-center">uploads/<?= e($r['folder']) ?></span><?php endif; ?>
        </form>
      </td>
      <td class="text-nowrap">
        <button form="f<?= $r['id'] ?>" name="aksi" value="ubah" class="btn btn-sm btn-warning">Simpan</button>
        <button form="f<?= $r['id'] ?>" name="aksi" value="hapus" class="btn btn-sm btn-danger" onclick="return confirm('Hapus <?= strtolower($label) ?> ini?')">Hapus</button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php layout_bottom();
