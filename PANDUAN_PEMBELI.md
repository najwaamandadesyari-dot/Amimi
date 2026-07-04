 **PANDUAN BERBELANJA DI AMIMI SHOP**

## Akun Pembeli Test

### Data Login
- **Email**: `pembeli@amimi.com`
- **Password**: `password`
- **Customer ID**: AMM-00003
- **Nama**: Kim Dokja

---

##  Cara Berbelanja

### 1. **Login ke Akun Pembeli**
   - Buka halaman login: [http://localhost/Amimi/auth/login.php](auth/login.php)
   - Masukkan email: `pembeli@amimi.com`
   - Masukkan password: `password`
   - Klik tombol "Masuk"
   - Anda akan otomatis diarahkan ke Dashboard Pembeli

### 2. **Akses Halaman Toko**
   - Klik menu " Belanja" di navbar
   - Atau buka: [http://localhost/Amimi/shop.php](shop.php)
   - Anda akan melihat grid produk seperti Shopee

### 3. **Cari & Filter Produk**
   Gunakan fitur di halaman toko:
   - **Search**: Cari produk dengan mengetik nama di kotak pencarian
   - **Kategori**: Filter berdasarkan kategori produk di sidebar
   - **Sort**: Urutkan produk (Terbaru, Harga Terendah, Harga Tertinggi)

### 4. **Lihat Detail Produk**
   - Klik tombol "Lihat" pada kartu produk
   - Atau klik langsung pada nama/gambar produk
   - Anda akan melihat:
     - Deskripsi lengkap produk
     - Harga
     - Stok tersedia
     - Pilihan ukuran (jika ada)
     - Quantity selector

### 5. **Tambah ke Keranjang**
   Di halaman detail produk:
   - Pilih ukuran produk (jika ada)
   - Atur jumlah dengan tombol +/-
   - Klik "Tambah ke Keranjang Kuning "
   - Item akan ditambahkan ke keranjang

   **Atau** di halaman toko:
   - Langsung klik tombol " Beli" di kartu produk
   - Produk akan ditambahkan ke keranjang dengan jumlah 1

### 6. **Lihat Keranjang**
   - Klik icon  di navbar (menampilkan jumlah item)
   - Atau buka: [http://localhost/Amimi/cart/index.php](cart/index.php)
   - Anda dapat:
     - Melihat daftar item di keranjang
     - Mengubah jumlah setiap item (+/-)
     - Menghapus item yang tidak diinginkan
     - Melihat ringkasan total harga

### 7. **Checkout & Pembayaran**
   - Klik "Lanjut ke Checkout " di halaman keranjang
   - Isi informasi pengiriman:
     - Nama penerima (auto-fill dari profil)
     - Nomor telepon
     - Alamat pengiriman
     - Catatan pesanan (opsional)
   
   - Pilih metode pembayaran:
     -  **Bayar di Tempat (COD)**: Sistem pre-order, pembayaran saat barang tiba
     -  **M-Banking/Transfer Bank**: Simulasi transfer Virtual Account BCA
     -  **E-Wallet**: Simulasi scan QRIS untuk OVO/GoPay/Dana/etc
   
   - Klik "Buat Pesanan Sekarang "

### 8. **Tracking Pesanan**
   - Buka Dashboard atau menu "Pesanan Saya"
   - Lihat daftar pesanan terbaru dengan status:
     - Status Pesanan: Menunggu → Diproses → Dikirim → Selesai
     - Status Pembayaran: Belum Bayar → Sudah Bayar → Gagal
   - Klik pesanan untuk melihat detail lengkap

### 9. **Kelola Profil**
   - Klik avatar/nama di navbar → "Profil Saya"
   - Atau buka: [http://localhost/Amimi/customer/profile.php](customer/profile.php)
   - Update informasi:
     - Nama
     - Nomor telepon
     - Alamat
   - Perubahan akan digunakan untuk pesanan berikutnya

---

##  Dashboard Pembeli

Akses: [http://localhost/Amimi/customer/dashboard.php](customer/dashboard.php)

Menampilkan:
-  **Quick Stats**: Total pesanan, total belanja, pesanan aktif, item di keranjang
-  **Pesanan Terbaru**: 5 pesanan terakhir dengan status dan harga
-  **Profil Saya**: Kartu profil dengan nama, email, avatar
-  **Quick Links**: 
  - Keranjang belanja
  - Belanja produk
  - Semua pesanan

---

##  Menu Pembeli

Di navbar (setelah login):
- **Beranda** → Kembali ke halaman utama
- ** Belanja** → Halaman toko dengan grid produk
- **Dashboard** → Dashboard pembeli dengan statistik
- **Pesanan Saya** → Riwayat semua pesanan
- **Avatar dropdown**:
  -  Dashboard
  -  Profil Saya
  -  Pesanan Saya
  -  Keluar

---

##  Tips & Trik

1. **Gunakan Search**: Jika ingin cari produk spesifik, gunakan search bar lebih cepat daripada scroll
2. **Filter Kategori**: Cari produk per kategori untuk pengalaman lebih fokus
3. **Quick Buy**: Di halaman toko, langsung klik " Beli" tanpa perlu ke detail produk
4. **Stock Alert**: Perhatikan badge "Stok Terbatas" atau "Habis" untuk mengetahui ketersediaan
5. **Rating**: Lihat rating produk () untuk membantu keputusan membeli
6. **Edit Pesanan**: Untuk mengubah jumlah atau menghapus item, gunakan halaman keranjang sebelum checkout

---

##  Catatan Penting

- **Pre-Order COD**: Pesanan dengan metode COD adalah pre-order. Admin akan memproses dan mengirim ke alamat Anda
- **Simulasi Pembayaran**: Metode M-Banking dan E-Wallet adalah simulasi untuk demo. Status pembayaran otomatis disetujui
- **Pengiriman Gratis**: Semua pesanan mendapat pengiriman gratis ke seluruh Indonesia
- **Stok Real-time**: Stok produk diupdate secara real-time saat checkout diproses

---

##  Keamanan

- Password disimpan dengan aman menggunakan enkripsi BCRYPT
- Semua form dilindungi dengan CSRF token
- Session dibuat otomatis saat login
- Login otomatis ke-expire saat logout

---

**Selamat berbelanja di Amimi Shop! **
