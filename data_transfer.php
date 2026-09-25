<?php
require 'config.php';
require 'auth.php';
requireLogin();

function download_file($filename, $content, $contentType) {
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('X-Content-Type-Options: nosniff');
    echo $content;
    exit;
}

$export = $_GET['export'] ?? '';
if ($export === 'backup') {
    $backup = [
        'app' => 'NetStock',
        'version' => 1,
        'exported_at' => date('Y-m-d H:i:s'),
        'categories' => read_data('categories'),
        'items' => read_data('items'),
        'transactions' => read_data('transactions')
    ];
    download_file('inventory-backup-' . date('Y-m-d-His') . '.json', json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'application/json; charset=utf-8');
}

if ($export === 'csv') {
    $items = read_data('items');
    $categories = read_data('categories');

    $categoryMap = [];
    foreach ($categories as $category) {
        $categoryMap[(int)$category['id']] = $category['name'];
    }

    // CSV dibuat khusus agar nyaman dibuka di Excel/Google Sheets:
    // UTF-8 BOM + pemisah titik koma + judul + ringkasan + kolom yang rapi.
    $totalItems = count($items);
    $totalUnits = 0;
    $lowStock = 0;

    foreach ($items as $item) {
        $stock = (int)($item['stock'] ?? 0);
        $totalUnits += $stock;
        if ($stock <= (int)($item['min_stock'] ?? 0)) {
            $lowStock++;
        }
    }

    $fp = fopen('php://temp', 'r+');
    if ($fp === false) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Export CSV gagal dibuat.'];
        redirect('index.php');
    }

    // UTF-8 BOM agar karakter Indonesia tampil benar di Microsoft Excel.
    fwrite($fp, "\xEF\xBB\xBF");

    $delimiter = ';';
    $writeRow = function (array $row) use ($fp, $delimiter) {
        fputcsv($fp, $row, $delimiter, '"', "\\");
    };

    $writeRow(['DATA INVENTARIS ALAT WIFI']);
    $writeRow(['Laporan Daftar Stok']);
    $writeRow(['Dibuat pada', date('d/m/Y H:i')]);
    $writeRow([]);
    $writeRow(['RINGKASAN']);
    $writeRow(['Total Jenis Barang', $totalItems]);
    $writeRow(['Total Unit', $totalUnits]);
    $writeRow(['Stok Menipis', $lowStock]);
    $writeRow([]);
    $writeRow(['DAFTAR STOK']);
    $writeRow([
        'No.',
        'SKU',
        'Nama Barang',
        'Kategori',
        'Satuan',
        'Stok Saat Ini',
        'Minimum Stok',
        'Status'
    ]);

    $no = 1;
    foreach ($items as $item) {
        $stock = (int)($item['stock'] ?? 0);
        $minStock = (int)($item['min_stock'] ?? 0);
        $status = $stock <= $minStock ? 'STOK MENIPIS' : 'AMAN';

        $writeRow([
            $no++, 
            (string)($item['sku'] ?? ''),
            (string)($item['name'] ?? ''),
            (string)($categoryMap[(int)($item['category_id'] ?? 0)] ?? '-'),
            (string)($item['unit'] ?? 'pcs'),
            $stock,
            $minStock,
            $status
        ]);
    }

    rewind($fp);
    $csv = stream_get_contents($fp);
    fclose($fp);

    download_file(
        'laporan-stok-' . date('Y-m-d-His') . '.csv',
        $csv,
        'text/csv; charset=UTF-8'
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['backup_file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash'] = ['type'=>'error','message'=>'File backup gagal diunggah.'];
        redirect('index.php');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $_SESSION['flash'] = ['type'=>'error','message'=>'Ukuran file backup maksimal 5 MB.'];
        redirect('index.php');
    }
    $raw = file_get_contents($file['tmp_name']);
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['items'], $data['categories'], $data['transactions']) || !is_array($data['items']) || !is_array($data['categories']) || !is_array($data['transactions'])) {
        $_SESSION['flash'] = ['type'=>'error','message'=>'Format backup tidak valid. Gunakan file JSON hasil Export Backup dari aplikasi ini.'];
        redirect('index.php');
    }

    $items = [];
    foreach ($data['items'] as $row) {
        if (!isset($row['id'], $row['sku'], $row['name'], $row['stock'], $row['min_stock'])) continue;
        $items[] = [
            'id'=>(int)$row['id'], 'sku'=>trim((string)$row['sku']), 'name'=>trim((string)$row['name']),
            'category_id'=>isset($row['category_id']) && $row['category_id'] !== '' ? (int)$row['category_id'] : null,
            'unit'=>trim((string)($row['unit'] ?? 'pcs')) ?: 'pcs', 'stock'=>max(0,(int)$row['stock']), 'min_stock'=>max(0,(int)$row['min_stock'])
        ];
    }
    $categories = [];
    foreach ($data['categories'] as $row) {
        if (!isset($row['id'], $row['name'])) continue;
        $categories[] = ['id'=>(int)$row['id'], 'name'=>trim((string)$row['name']), 'description'=>trim((string)($row['description'] ?? ''))];
    }
    $validItemIds = array_flip(array_map(fn($r)=>(int)$r['id'], $items));
    $transactions = [];
    foreach ($data['transactions'] as $row) {
        if (!isset($row['id'], $row['item_id'], $row['type'], $row['quantity'], $row['created_at'])) continue;
        $type = strtoupper((string)$row['type']);
        if (!isset($validItemIds[(int)$row['item_id']]) || !in_array($type, ['IN','OUT'], true)) continue;
        $transactions[] = ['id'=>(int)$row['id'],'item_id'=>(int)$row['item_id'],'type'=>$type,'quantity'=>max(0,(int)$row['quantity']),'note'=>trim((string)($row['note'] ?? '')) ?: null,'created_at'=>(string)$row['created_at']];
    }
    write_data('categories', $categories);
    write_data('items', $items);
    write_data('transactions', $transactions);
    $_SESSION['flash'] = ['type'=>'success','message'=>'Backup berhasil diimport. Data stok dan riwayat sudah diperbarui.'];
    redirect('index.php');
}

redirect('index.php');
