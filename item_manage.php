<?php require 'config.php'; require 'auth.php'; requireLogin(); $pageTitle='Kelola Alat'; $items=read_data('items'); usort($items,fn($a,$b)=>strcmp($a['name'],$b['name'])); include 'header.php'; ?>
<section class="page-heading"><div><h1>Kelola Alat</h1><p>Cari dan hapus alat yang sudah tidak diperlukan.</p></div><a href="item_add.php" class="btn btn-primary">＋ Tambah Alat</a></section>
<section class="panel table-panel"><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Stok</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($items as $row): ?>
<tr>
<td><span class="sku-tag"><?= e($row['sku']) ?></span></td>
<td><?= e($row['name']) ?></td>
<td><?= e(category_name($row['category_id']) ?? '-') ?></td>
<td><?= number_format($row['stock']) ?> <?= e($row['unit']) ?></td>
<td>
<a class="btn btn-secondary btn-sm" href="item_edit.php?id=<?= $row['id'] ?>">Edit</a>
<a class="btn btn-danger btn-sm" href="item_delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Hapus alat &quot;<?= e($row['name']) ?>&quot;? Riwayat transaksinya juga akan terhapus dan tidak bisa dikembalikan.')">Hapus</a>
</td>
</tr>
<?php endforeach; ?>
<?php if (empty($items)): ?>
<tr><td colspan="5" class="text-muted">Belum ada alat.</td></tr>
<?php endif; ?>
</tbody></table></div></section>
<?php include 'footer.php'; ?>
