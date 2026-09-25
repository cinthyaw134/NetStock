<?php
/**
 * auth.php
 * Helper untuk proteksi halaman - panggil requireLogin() di halaman yang wajib login.
 * File ini harus di-include SETELAH config.php (karena butuh session yang sudah aktif).
 */

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Silakan login terlebih dahulu.'];
        redirect('login.php');
    }
}

function currentUsername() {
    return $_SESSION['username'] ?? '';
}
