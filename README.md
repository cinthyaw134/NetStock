# NetStock

NetStock adalah aplikasi web sederhana untuk mengelola inventaris alat/perangkat jaringan (WiFi, fiber, dan aksesoris instalasi). Dibuat dengan PHP native tanpa framework, dan **tanpa database** — semua data disimpan sebagai JSON di dalam file `.txt`.

## ✨ Fitur

- Login & registrasi akun (password di-hash dengan bcrypt)
- Dashboard ringkas: total alat, alat stok menipis, riwayat transaksi hari ini
- Manajemen kategori alat (tambah/edit/hapus)
- Tambah & edit data alat, dengan batas stok minimum
- Catat stok masuk & stok keluar, termasuk backdate tanggal transaksi
- Riwayat transaksi lengkap, bisa dihapus dengan otomatis mengembalikan (reversal) stok
- Mode gelap (dark mode), tersimpan di browser
- Export/backup seluruh data ke file

## 🧱 Teknologi

- PHP native (tanpa framework)
- Penyimpanan data: file JSON (`.txt`) di folder `data/` — tidak memakai MySQL/phpMyAdmin
- Autentikasi sesi PHP + `password_hash()` (bcrypt)
- HTML/CSS/JS vanilla di sisi tampilan

## 📂 Struktur Data

Semua data aplikasi tersimpan sebagai JSON di folder `data/`:

| File | Isi |
|---|---|
| `data/items.txt` | Daftar alat dan stoknya |
| `data/categories.txt` | Kategori alat |
| `data/transactions.txt` | Riwayat stok masuk/keluar |
| `data/users.txt` | Akun pengguna |

Folder `data/` dilindungi `.htaccess` agar isi file `.txt` tidak bisa dibuka langsung lewat browser.

> File data asli (`items.txt`, `categories.txt`, dll) **tidak disertakan** di repo ini (lihat `.gitignore`) karena berisi data pribadi. Repo ini menyediakan file `*.example.txt` sebagai contoh format & data awal.

## 🚀 Instalasi (XAMPP / Windows)

1. Clone atau download repo ini ke folder `htdocs`, misal: `C:\xampp\htdocs\netstock`
2. Salin file contoh jadi file data aktif:
   ```
   data/items.example.txt        -> data/items.txt
   data/categories.example.txt   -> data/categories.txt
   data/transactions.example.txt -> data/transactions.txt
   data/users.example.txt        -> data/users.txt
   ```
3. Pastikan folder `data/` bisa ditulis oleh web server (biasanya sudah oke di XAMPP Windows)
4. Jalankan Apache dari XAMPP Control Panel
5. Buka `http://localhost/netstock/`
6. Login dengan akun contoh:
   - **Username:** `admin`
   - **Password:** `admin123`

   > Segera ganti password ini setelah login pertama, atau daftar akun baru lewat halaman Register.

## 🔒 Catatan Keamanan

Proyek ini dibuat untuk keperluan belajar/portofolio. Beberapa hal yang belum diimplementasikan dan sebaiknya ditambahkan sebelum dipakai di lingkungan produksi/publik:
- Proteksi CSRF pada form
- Rate limiting untuk percobaan login
- Validasi & sanitasi input yang lebih ketat

## 📜 Lisensi

Lihat file [LICENSE](LICENSE).
