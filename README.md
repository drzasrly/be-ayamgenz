# 🍗 Fried Chicken - Laravel Web Application

Aplikasi web berbasis Laravel 12. Panduan ini menjelaskan cara menginstal, menjalankan aplikasi secara lokal, serta mengeksposnya ke internet menggunakan **Ngrok** agar bisa diakses oleh pihak luar atau diuji secara online.

---

## 🛠️ Prasyarat (Prerequisites)

Sebelum memulai, pastikan perangkat Anda telah terinstal beberapa tools berikut:
*   **PHP** (Minimal versi 8.2)
*   **Composer** (Untuk mengelola dependensi PHP)
*   **Node.js & NPM** (Untuk kompilasi frontend Vite)
*   **Laragon** atau web server lokal lainnya (Direkomendasikan karena folder proyek ini berada di direktori Laragon `C:\laragon\www\friedchicken`)
*   **Akun Ngrok** & **Ngrok CLI** (Untuk mempublikasikan server lokal ke internet)

---

## 🚀 Langkah Instalasi & Setup Pertama Kali

Ikuti langkah-langkah di bawah ini untuk mempersiapkan aplikasi di perangkat lokal Anda:

### 1. Pindahkan Proyek ke Folder Laragon
Pastikan folder proyek berada di direktori root web server Laragon:
`C:\laragon\www\friedchicken`

### 2. Setup Otomatis (Direkomendasikan)
Proyek ini sudah dilengkapi dengan script instalasi otomatis di [composer.json](file:///c:/laragon/www/friedchicken/composer.json). Jalankan perintah berikut di terminal (dalam folder proyek):
```bash
composer run setup
```
Perintah di atas akan otomatis melakukan:
*   Instalasi dependensi PHP via Composer (`composer install`).
*   Menyalin `.env.example` menjadi `.env`.
*   Membuat Application Key (`php artisan key:generate`).
*   Membuat file SQLite dan menjalankan migrasi database (`php artisan migrate --force`).
*   Instalasi dependensi JavaScript via NPM (`npm install`).
*   Membuat build aset produksi (`npm run build`).

### 3. Setup Manual (Alternatif jika Setup Otomatis Gagal)
Jika Anda ingin melakukannya secara manual satu per satu:
```bash
# 1. Install dependensi PHP
composer install

# 2. Salin konfigurasi environment
copy .env.example .env

# 3. Generate Application Key
php artisan key:generate

# 4. Jalankan Migrasi Database (Secara default menggunakan SQLite)
php artisan migrate

# 5. Install dependensi NPM & build assets
npm install
npm run build
```

---

## 💻 Menjalankan Aplikasi secara Lokal

Ada dua cara untuk menjalankan aplikasi ini secara lokal:

### Metode A: Menggunakan Laragon (Sangat Direkomendasikan)
1.  Buka aplikasi **Laragon**.
2.  Klik tombol **Start All**.
3.  Buka browser Anda dan akses melalui alamat virtual host default Laragon:
    `http://friedchicken.test`
4.  Jika ingin melakukan pengembangan frontend (Vite Hot Reload):
    ```bash
    npm run dev
    ```

### Metode B: Menggunakan Laravel Artisan Serve (Port 8000)
Proyek ini memiliki script khusus untuk menyalakan server, antrean queue, log server, dan Vite secara bersamaan dalam satu baris perintah:
```bash
composer run dev
```
Atau jika ingin menjalankan server web Laravel saja:
```bash
php artisan serve
```
Aplikasi Anda akan berjalan di `http://127.0.0.1:8000`.

---

## 🌐 Menjalankan & Berbagi Menggunakan Ngrok

Ngrok digunakan untuk membuat terowongan aman (tunnel) dari localhost Anda ke internet agar orang lain dapat mengakses web Anda secara online.

### Langkah 1: Persiapan Ngrok
1.  Daftar akun gratis di [ngrok.com](https://ngrok.com/).
2.  Unduh dan instal **Ngrok CLI** di PC Anda.
3.  Hubungkan token autentikasi akun Anda dengan menjalankan perintah berikut di terminal:
    ```bash
    ngrok config add-authtoken <TOKEN_AUTENTIKASI_ANDA>
    ```
    *(Ganti `<TOKEN_AUTENTIKASI_ANDA>` dengan token yang ada di dashboard Ngrok Anda).*

---

### Langkah 2: Menghubungkan Ngrok ke Aplikasi

Pilih salah satu metode di bawah ini yang sesuai dengan cara Anda menjalankan server lokal:

#### Opsi 1: Jika Menggunakan Virtual Host Laragon (`friedchicken.test`)
Jika Anda menggunakan Laragon, ada dua cara mudah:

*   **Cara Otomatis Laragon (Paling Mudah):**
    1. Klik kanan pada jendela utama Laragon atau icon Laragon di System Tray (kanan bawah Windows).
    2. Arahkan kursor ke **Quick Share** -> **friedchicken**.
    3. Laragon akan otomatis membuka terminal Ngrok dan menghubungkannya. Anda akan mendapatkan URL publik HTTPS yang siap dibagikan.

*   **Cara Manual lewat Terminal (CMD / PowerShell):**
    ```bash
    ngrok http --host-header=friedchicken.test 80
    ```

#### Opsi 2: Jika Menggunakan `php artisan serve` (Port 8000)
1. Pastikan server lokal Anda menyala (`php artisan serve` atau `composer run dev`).
2. Jalankan perintah Ngrok berikut di terminal baru:
   ```bash
   ngrok http 8000
   ```
3. Ngrok akan menampilkan informasi di terminal seperti berikut:
   ```text
   Session Status                online
   Account                       Nama Anda (User ID)
   Forwarding                    https://xxxx-xxxx.ngrok-free.app -> http://localhost:8000
   ```
4. Salin URL HTTPS (`https://xxxx-xxxx.ngrok-free.app`) dan bagikan ke pengguna lain.

---

### ⚠️ PENTING: Solusi Jika Tampilan Web / Asset (CSS/JS) Rusak di Ngrok

Ketika mengakses web Laravel lewat HTTPS Ngrok, terkadang browser memblokir asset CSS dan JS lokal karena masalah **Mixed Content** atau **CORS** (karena Laravel mencoba me-load asset dari URL `http://` lokal, sedangkan Anda membukanya lewat `https://` ngrok).

Berikut solusi untuk mengatasinya:

#### Solusi A: Lakukan Build Aset Statis (Direkomendasikan untuk Demo)
Sebelum menyalakan Ngrok, jalankan perintah ini di folder proyek untuk mengompilasi semua aset menjadi statis:
```bash
npm run build
```
Dengan melakukan build, Laravel akan memuat aset langsung dari folder `public/build` secara lokal tanpa bergantung pada Vite Development Server (`npm run dev`) yang biasanya berjalan di port `5173`.

#### Solusi B: Sesuaikan Konfigurasi `.env`
Buka file `.env` di proyek Anda, lalu ubah variabel `APP_URL` menjadi URL HTTPS Ngrok yang Anda dapatkan:
```env
APP_URL=https://xxxx-xxxx.ngrok-free.app
```

#### Solusi C: Paksa Skema HTTPS pada Laravel (Force HTTPS)
Agar Laravel selalu menghasilkan URL aset dan rute menggunakan `https` saat diakses via Ngrok, Anda dapat menambahkan kode berikut di file [AppServiceProvider.php](file:///c:/laragon/www/friedchicken/app/Providers/AppServiceProvider.php):

1. Buka file [AppServiceProvider.php](file:///c:/laragon/www/friedchicken/app/Providers/AppServiceProvider.php).
2. Ubah kodenya pada bagian method `boot()` menjadi seperti berikut:

```php
use Illuminate\Support\Facades\URL;

public function boot(): void
{
    // Paksa HTTPS jika diakses melalui ngrok
    if (str_contains(request()->header('host'), 'ngrok')) {
        URL::forceScheme('https');
    }
}
```

---

## 🔍 Perintah Bermanfaat Lainnya

*   **Menjalankan queue (antrean email/notifikasi):** `php artisan queue:listen`
*   **Melihat log sistem secara real-time:** `php artisan pail`
*   **Menghapus cache aplikasi:** `php artisan optimize:clear`
*   **Reset & migrasi ulang database:** `php artisan migrate:fresh`
