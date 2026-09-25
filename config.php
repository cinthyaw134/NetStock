<?php
session_start();

define('DATA_DIR', __DIR__ . '/data');

function redirect($url) { header("Location: $url"); exit; }
function rupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

// Semua data aplikasi disimpan sebagai JSON di file .txt. PHP hanya bertugas membaca/menulisnya.
function data_file($name) { return DATA_DIR . '/' . $name . '.txt'; }

function read_data($name) {
    $file = data_file($name);
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function write_data($name, array $data) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0775, true);
    $file = data_file($name);
    $tmp = $file . '.tmp';
    $content = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($content === false) throw new RuntimeException('Gagal membuat data JSON.');
    if (file_put_contents($tmp, $content . PHP_EOL, LOCK_EX) === false) throw new RuntimeException('Gagal menulis file data.');
    if (!rename($tmp, $file)) throw new RuntimeException('Gagal menyimpan file data.');
}

function next_id(array $rows) { return empty($rows) ? 1 : max(array_column($rows, 'id')) + 1; }
function find_index(array $rows, $id) { foreach ($rows as $i=>$row) if ((int)$row['id']===(int)$id) return $i; return null; }
function find_item($id) { foreach (read_data('items') as $row) if ((int)$row['id']===(int)$id) return $row; return null; }
function category_name($id) { foreach (read_data('categories') as $row) if ((int)$row['id']===(int)$id) return $row['name']; return null; }
function save_transaction($itemId,$type,$quantity,$note='',$createdAt=null) {
    $tx=read_data('transactions');
    $tx[]=['id'=>next_id($tx),'item_id'=>(int)$itemId,'type'=>$type,'quantity'=>(int)$quantity,'note'=>$note ?: null,'created_at'=>$createdAt ?: date('Y-m-d H:i:s')];
    write_data('transactions',$tx);
}
