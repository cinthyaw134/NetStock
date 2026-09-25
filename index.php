<?php
require 'config.php';
require 'auth.php';
requireLogin();

$pageTitle = 'Dashboard';
$all = read_data('items');
$tx = read_data('transactions');
$search = trim($_GET['q'] ?? '');
$transactionErrors = [];

/* Transaksi langsung dari Dashboard: MASUK / KELUAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'transaction') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $type = strtoupper(trim($_POST['type'] ?? 'IN'));
    $qty = (int)($_POST['quantity'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $txDate = trim($_POST['tx_date'] ?? '');

    if (!in_array($type, ['IN', 'OUT'], true)) {
        $transactionErrors[] = 'Jenis transaksi tidak valid.';
    }
    if ($qty <= 0) {
        $transactionErrors[] = 'Jumlah harus lebih dari 0.';
    }

    $idx = find_index($all, $itemId);
    if ($idx === null) {
        $transactionErrors[] = 'Pilih alat terlebih dahulu.';
    } elseif ($type === 'OUT' && $qty > (int)$all[$idx]['stock']) {
        $transactionErrors[] = 'Jumlah keluar melebihi stok yang tersedia.';
    }

    if (!$transactionErrors) {
        if ($type === 'IN') {
            $all[$idx]['stock'] += $qty;
        } else {
            $all[$idx]['stock'] -= $qty;
        }

        write_data('items', $all);
        $createdAt = $txDate ? str_replace('T', ' ', $txDate) . ':00' : date('Y-m-d H:i:s');
        save_transaction($itemId, $type, $qty, $note, $createdAt);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => $type === 'IN' ? 'Stok masuk berhasil dicatat.' : 'Stok keluar berhasil dicatat.'
        ];
        redirect('index.php' . ($search !== '' ? '?q=' . urlencode($search) : ''));
    }
}

/* Data dashboard */
$items = $all;
if ($search !== '') {
    $items = array_values(array_filter($items, function ($i) use ($search) {
        $category = category_name($i['category_id'] ?? 0) ?? '';
        return stripos($i['name'] ?? '', $search) !== false
            || stripos($i['sku'] ?? '', $search) !== false
            || stripos($category, $search) !== false;
    }));
}
usort($items, fn($a, $b) => strcmp($a['name'], $b['name']));

usort($tx, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

$totalItems = count($all);
$totalStock = array_sum(array_column($all, 'stock'));
$totalLow = count(array_filter($all, fn($i) => $i['stock'] <= $i['min_stock']));
$today = date('Y-m-d');
$inToday = count(array_filter($tx, fn($t) => $t['type'] === 'IN' && substr($t['created_at'], 0, 10) === $today));
$outToday = count(array_filter($tx, fn($t) => $t['type'] === 'OUT' && substr($t['created_at'], 0, 10) === $today));
$recent = array_slice($tx, 0, 6);

include 'header.php';
?>

<section class="page-heading dashboard-heading">
    <div>
        <span class="eyebrow">INVENTORY CONTROL</span>
        <h1>Dashboard Alat</h1>
        <p>Kelola stok, cari alat, dan catat transaksi dari satu halaman.</p>
    </div>
    <div class="dashboard-heading-actions">
        <a class="btn btn-secondary" href="data_transfer.php?export=csv">⇩ Export CSV</a>
        <a class="btn btn-secondary" href="data_transfer.php?export=backup">⇩ Backup</a>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('import-modal').classList.add('show');document.body.classList.add('modal-open')">⇧ Import</button>
        <a class="btn btn-primary" href="item_add.php">＋ Tambah Alat</a>
    </div>
</section>

<?php if ($transactionErrors): ?>
    <div class="alert alert-error transaction-alert">
        <?php foreach ($transactionErrors as $err): ?>
            <div>• <?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="cards">
    <div class="stat-card blue">
        <div class="stat-icon">▣</div>
        <div><span>Total Barang</span><strong><?= $totalItems ?></strong><small>Jenis barang</small></div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon">▤</div>
        <div><span>Total Unit</span><strong><?= number_format($totalStock, 0, ',', '.') ?></strong><small>Jumlah unit saat ini</small></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">⇩</div>
        <div><span>Masuk Hari Ini</span><strong><?= $inToday ?></strong><small>Transaksi stok masuk</small></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">⇧</div>
        <div><span>Keluar Hari Ini</span><strong><?= $outToday ?></strong><small>Transaksi stok keluar</small></div>
    </div>
</section>

<div class="dashboard-main-grid">
    <section class="panel stock-panel">
        <div class="panel-head stock-panel-head">
            <div>
                <h2>Daftar Stok</h2>
                <p><?= $search !== '' ? 'Hasil pencarian untuk “' . e($search) . '”' : 'Semua alat yang tersimpan' ?></p>
            </div>
            <div class="result-count"><?= count($items) ?> alat</div>
        </div>

        <form action="index.php" method="get" class="dashboard-search">
            <span class="search-symbol">⌕</span>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari nama alat, SKU, atau kategori..." autocomplete="off">
            <?php if ($search !== ''): ?><a href="index.php" class="clear-search">×</a><?php endif; ?>
            <button type="submit">Cari</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>SKU</th><th>Barang</th><th>Stok</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="5" class="empty-state"><div>⌕</div><strong>Barang tidak ditemukan</strong><span>Coba gunakan nama alat, SKU, atau kategori lain.</span></td></tr>
                <?php endif; ?>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><span class="sku-tag"><?= e($row['sku']) ?></span></td>
                        <td><strong><?= e($row['name']) ?></strong><small><?= e(category_name($row['category_id']) ?? '-') ?></small></td>
                        <td><strong><?= number_format($row['stock']) ?></strong> <?= e($row['unit']) ?></td>
                        <td><span class="badge <?= $row['stock'] <= $row['min_stock'] ? 'low' : 'ok' ?>"><?= $row['stock'] <= $row['min_stock'] ? 'Menipis' : 'Aman' ?></span></td>
                        <td class="actions">
                            <button type="button" class="mini-action plus" title="Stok masuk" onclick="openTransaction(<?= (int)$row['id'] ?>, 'IN')">＋</button>
                            <button type="button" class="mini-action minus" title="Stok keluar" onclick="openTransaction(<?= (int)$row['id'] ?>, 'OUT')">−</button>
                            <a href="item_edit.php?id=<?= (int)$row['id'] ?>" class="mini-action" title="Edit">⋮</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="side-column">
        <section class="panel recent-panel">
            <div class="panel-head">
                <div><h2>Riwayat Terbaru</h2><p>Aktivitas masuk & keluar</p></div>
                <a href="stock_history.php">Lihat semua</a>
            </div>
            <div class="activity-list">
                <?php if (!$recent): ?><p class="text-muted">Belum ada aktivitas.</p><?php endif; ?>
                <?php foreach ($recent as $t): $item = find_item($t['item_id']); ?>
                    <div class="activity">
                        <div class="activity-icon <?= $t['type'] === 'IN' ? 'in' : 'out' ?>"><?= $t['type'] === 'IN' ? '↓' : '↑' ?></div>
                        <div class="activity-body">
                            <div><strong><?= $t['type'] === 'IN' ? 'Stok Masuk' : 'Stok Keluar' ?></strong><time><?= date('d/m H:i', strtotime($t['created_at'])) ?></time></div>
                            <span><?= e($item['name'] ?? 'Barang dihapus') ?></span>
                            <small class="<?= $t['type'] === 'IN' ? 'positive' : 'negative' ?>"><?= $t['type'] === 'IN' ? '+' : '-' ?><?= number_format($t['quantity']) ?> <?= e($item['unit'] ?? 'unit') ?><?= $t['note'] ? ' · ' . e($t['note']) : '' ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </aside>
</div>

<!-- Modal import data -->
<div class="transaction-modal" id="import-modal" aria-hidden="true">
    <div class="transaction-backdrop" onclick="document.getElementById('import-modal').classList.remove('show');document.body.classList.remove('modal-open')"></div>
    <section class="transaction-dialog import-dialog" role="dialog" aria-modal="true">
        <button type="button" class="transaction-close" onclick="document.getElementById('import-modal').classList.remove('show');document.body.classList.remove('modal-open')">×</button>
        <div class="transaction-dialog-head">
            <div class="transaction-dialog-icon">⇧</div>
            <div><span class="eyebrow">IMPORT DATA</span><h2>Import Backup</h2><p>Gunakan file backup JSON dari aplikasi ini. Data lama akan diganti.</p></div>
        </div>
        <form method="post" action="data_transfer.php" enctype="multipart/form-data">
            <div class="form-group"><label for="backup_file">File Backup (.json)</label><input id="backup_file" type="file" name="backup_file" accept=".json,application/json" required></div>
            <div class="import-warning">⚠ Import akan mengganti data alat, kategori, dan riwayat saat ini. Sebaiknya lakukan Backup terlebih dahulu.</div>
            <div class="transaction-dialog-actions"><button type="button" class="btn btn-secondary" onclick="document.getElementById('import-modal').classList.remove('show');document.body.classList.remove('modal-open')">Batal</button><button class="btn btn-primary" type="submit">Import Data</button></div>
        </form>
    </section>
</div>

<!-- Modal transaksi: hanya muncul setelah tombol + atau - diklik -->
<div class="transaction-modal" id="transaction-modal" aria-hidden="true">
    <div class="transaction-backdrop" onclick="closeTransaction()"></div>
    <section class="transaction-dialog" role="dialog" aria-modal="true" aria-labelledby="transaction-modal-title">
        <button type="button" class="transaction-close" onclick="closeTransaction()" aria-label="Tutup">×</button>
        <div class="transaction-dialog-head">
            <div class="transaction-dialog-icon" id="transaction-dialog-icon">↓</div>
            <div>
                <span class="eyebrow" id="transaction-eyebrow">STOK MASUK</span>
                <h2 id="transaction-modal-title">Tambah Stok</h2>
                <p id="transaction-modal-subtitle">Catat penambahan stok untuk alat yang dipilih.</p>
            </div>
        </div>

        <form method="post" class="quick-form transaction-modal-form">
            <input type="hidden" name="action" value="transaction">
            <input type="hidden" name="type" id="transaction-type" value="IN">

            <div class="form-group">
                <label for="quick-item">Alat</label>
                <select name="item_id" id="quick-item" required>
                    <option value="">Pilih alat...</option>
                    <?php foreach ($all as $i): ?>
                        <option value="<?= (int)$i['id'] ?>" data-stock="<?= (int)$i['stock'] ?>" data-unit="<?= e($i['unit']) ?>">
                            <?= e($i['sku'] . ' · ' . $i['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="stock-hint" id="stock-hint">Pilih alat untuk melihat stok tersedia.</small>
            </div>

            <div class="quick-form-row">
                <div class="form-group">
                    <label for="quick-qty">Jumlah</label>
                    <input type="number" name="quantity" id="quick-qty" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label for="quick-date">Tanggal & Jam</label>
                    <input type="datetime-local" name="tx_date" id="quick-date" value="<?= date('Y-m-d\TH:i') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="quick-note">Catatan <span>(opsional)</span></label>
                <input name="note" id="quick-note" placeholder="Contoh: Pembelian / pemasangan baru">
            </div>

            <div class="transaction-dialog-actions">
                <button type="button" class="btn btn-secondary" onclick="closeTransaction()">Batal</button>
                <button type="submit" class="btn transaction-submit submit-in" id="transaction-submit">↓ Simpan Stok Masuk</button>
            </div>
        </form>
    </section>
</div>

<script>
const transactionModal = document.getElementById('transaction-modal');
const transactionType = document.getElementById('transaction-type');
const transactionSubmit = document.getElementById('transaction-submit');
const transactionTabs = document.querySelectorAll('.transaction-tab');
const quickItem = document.getElementById('quick-item');
const quickQty = document.getElementById('quick-qty');
const stockHint = document.getElementById('stock-hint');

function setTransactionType(type) {
    transactionType.value = type;
    transactionSubmit.textContent = type === 'IN' ? '↓ Simpan Stok Masuk' : '↑ Simpan Stok Keluar';
    transactionSubmit.classList.toggle('submit-in', type === 'IN');
    transactionSubmit.classList.toggle('submit-out', type === 'OUT');
    document.getElementById('transaction-eyebrow').textContent = type === 'IN' ? 'STOK MASUK' : 'STOK KELUAR';
    document.getElementById('transaction-modal-title').textContent = type === 'IN' ? 'Tambah Stok' : 'Kurangi Stok';
    document.getElementById('transaction-modal-subtitle').textContent = type === 'IN'
        ? 'Catat penambahan stok untuk alat yang dipilih.'
        : 'Catat pengeluaran stok untuk alat yang dipilih.';
    const icon = document.getElementById('transaction-dialog-icon');
    icon.textContent = type === 'IN' ? '↓' : '↑';
    icon.classList.toggle('out', type === 'OUT');
    updateStockHint();
}

function updateStockHint() {
    const option = quickItem.selectedOptions[0];
    if (!option || !option.value) {
        stockHint.textContent = 'Pilih alat untuk melihat stok tersedia.';
        quickQty.removeAttribute('max');
        return;
    }
    const stock = option.dataset.stock || '0';
    const unit = option.dataset.unit || 'unit';
    stockHint.textContent = 'Stok saat ini: ' + Number(stock).toLocaleString('id-ID') + ' ' + unit;
    if (transactionType.value === 'OUT') quickQty.max = stock;
    else quickQty.removeAttribute('max');
}

function openTransaction(itemId, type) {
    quickItem.value = String(itemId);
    setTransactionType(type);
    updateStockHint();
    transactionModal.classList.add('show');
    transactionModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    setTimeout(() => quickQty.focus(), 120);
}

function closeTransaction() {
    transactionModal.classList.remove('show');
    transactionModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
}

quickItem.addEventListener('change', updateStockHint);
document.addEventListener('keydown', event => { if (event.key === 'Escape') closeTransaction(); });
</script>

<?php include 'footer.php'; ?>
