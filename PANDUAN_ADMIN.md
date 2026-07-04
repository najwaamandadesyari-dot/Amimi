 **PANDUAN ADMIN - MANAJEMEN PRODUK & PESANAN**

## Akses Admin Dashboard

### URL: [http://localhost/Amimi/admin/](admin/)

**Catatan**: Admin harus login dengan akun admin terlebih dahulu

---

##  Admin Dashboard

Menampilkan statistik bisnis:
-  Total produk terjual
-  Jumlah produk di katalog
-  Total unit stok saat ini
-  Profit & Loss (Keuntungan & Kerugian)
-  Jumlah pesanan pending/menunggu
-  Pesanan terbaru dengan detail

---

##  Manajemen Produk

### Akses: [http://localhost/Amimi/admin/products.php](admin/products.php)

### Fitur:
- **Lihat Semua Produk**: Tabel lengkap dengan kategori, harga, stok
- **Tambah Produk Baru**: Klik tombol "+ Tambah Produk Baru"
- **Edit Produk**: Klik " Edit" di aksi
- **Hapus Produk**: Klik " Hapus" di aksi
- **Filter Kolom**: 
  - Gambar (dengan icon kategori)
  - Nama Produk
  - Kategori
  - Harga Beli (Modal)
  - Harga Jual
  - Stok
  - Ukuran (jika ada)
  - Status (Aktif/Nonaktif)

### Tambah/Edit Produk

Form akan meminta:
- **Nama Produk**: Nama lengkap produk
- **Kategori**: Pilih dari daftar kategori
- **Deskripsi**: Detail produk (multi-line)
- **Harga Beli**: Harga modal/cost (untuk perhitungan profit)
- **Harga Jual**: Harga yang ditampilkan ke pembeli
- **Stok**: Jumlah unit tersedia
- **Ukuran** (opsional): Daftar ukuran jika produk pakaian (pisahkan dengan koma: "S,M,L,XL")
- **Gambar** (opsional): Upload gambar produk
- **Status**: Aktif/Nonaktif (hanya produk aktif yang tampil di toko)

### Kategori Produk

Sistem mendukung kategori:
-  Kategori 1 (Fashion Wanita)
-  Kategori 2 (Fashion Pria)
-  Kategori 3 (Anak-anak)
-  Kategori 4 (Aksesoris)
-  Kategori 5 (Peralatan Rumah)
-  Lainnya

---

##  Manajemen Pesanan

### Akses: [http://localhost/Amimi/admin/orders.php](admin/orders.php)

### Status Pesanan
1. **Pending** ( Menunggu): Pesanan baru, belum diproses
2. **Processing** ( Diproses): Sedang disiapkan pengiriman
3. **Shipped** ( Dikirim): Sudah dalam perjalanan ke pembeli
4. **Delivered** ( Selesai): Sampai ke tangan pembeli
5. **Cancelled** ( Dibatalkan): Pesanan dibatalkan

### Status Pembayaran
- **Pending**: Belum membayar (khusus COD)
- **Confirmed**: Sudah membayar atau simulasi pembayaran selesai
- **Failed**: Pembayaran gagal

### Fitur Manajemen:
- **Filter Status**: Lihat pesanan berdasarkan status
- **Lihat Detail**: Klik nomor pesanan untuk detail lengkap
- **Update Status**: Ubah status pesanan (Pending → Processing → Shipped → Delivered)
- **Lihat Item**: Daftar produk yang dipesan
- **Informasi Pengiriman**: Nama, telepon, alamat penerima

### Tabel Pesanan Mencakup:
- No. Pesanan (ORD-YYYYMMDD-XXXX)
- Nama Pembeli
- Tanggal Pesanan
- Metode Pembayaran (COD, M-Banking, E-Wallet)
- Status Pembayaran
- Status Pesanan
- Total Harga
- Aksi (Lihat Detail)

---

##  Metode Pembayaran

### 1. COD (Bayar di Tempat)
- **Sistem**: Pre-Order
- **Proses**: Admin memproses → Pengiriman → Pembeli bayar saat barang tiba
- **Status**: Dimulai dari "Pending" payment hingga pembeli membayar

### 2. M-Banking / Transfer Bank
- **Simulasi VA**: BCA Virtual Account 8892 0812 3456 7890
- **Status**: Otomatis "Confirmed" setelah pesanan dibuat (untuk simulasi)
- **Atas Nama**: AMIMI SHOP INDONESIA

### 3. E-Wallet (QRIS)
- **Metode**: OVO, GoPay, Dana, LinkAja, ShopeePay
- **Simulasi QRIS**: Ditampilkan di halaman checkout
- **Status**: Otomatis "Confirmed" setelah pesanan dibuat (untuk simulasi)

---

##  Profit & Loss Calculation

Sistem menghitung keuntungan otomatis:

```
Profit = (Harga Jual - Harga Beli) × Jumlah Terjual

Contoh:
- Produk: Kemeja
- Harga Beli (Modal): Rp 50.000
- Harga Jual: Rp 120.000
- Terjual: 10 unit
- Profit: (120.000 - 50.000) × 10 = Rp 700.000
```

### Dashboard Profit:
- **Total Revenue**: Total penjualan dari pesanan "Delivered" + "Confirmed Payment"
- **Total COGS**: Total modal dari pesanan yang sudah terkirim
- **Net Profit**: Revenue - COGS

---

##  Workflow Pesanan (Admin)

1. **Pesanan Masuk** (Status: Pending)
   - Pembeli checkout dan membuat pesanan
   - Admin melihat di dashboard

2. **Proses Pesanan** (Update ke: Processing)
   - Admin verifikasi stok
   - Siapkan barang
   - Klik "Update Status" → "Processing"

3. **Pengiriman** (Update ke: Shipped)
   - Barang dikirim ke kurir
   - Admin update status → "Shipped"
   - Notifikasi otomatis ke pembeli

4. **Selesai** (Update ke: Delivered)
   - Barang sampai ke pembeli
   - Admin confirm → "Delivered"
   - Pesanan dihitung untuk profit reporting

5. **Pembatalan** (Update ke: Cancelled - Jika Perlu)
   - Jika ada masalah (stok habis, dll)
   - Stok otomatis dikembalikan

---

##  Tips Manajemen

### Stok Management:
- Pantau produk dengan stok < 5 (badge "Stok Terbatas")
- Pantau produk dengan stok 0 (badge "Habis")
- Update stok dari halaman edit produk atau bulk edit

### Pesanan Management:
- Proses pesanan dalam 24 jam untuk kepuasan pelanggan
- Update status pengiriman untuk transparansi pelanggan
- Hubungi pembeli jika ada kendala (gunakan kontak dari pesanan)

### Kategori Management:
- Gunakan kategori yang sesuai untuk filtering pembeli
- Jangan hapus kategori yang sudah memiliki produk (perlu migrasi dulu)

### Laporan:
- Dashboard menampilkan KPI bisnis real-time
- Update profit otomatis saat pesanan delivered
- Export data jika perlu analisis lebih lanjut

---

##  Akses Kontrol

### Admin Only:
- Dashboard statistik
- Manajemen produk (tambah, edit, hapus)
- Manajemen pesanan (view, update status)
- Manajemen kategori

### Customer (Pembeli) tidak bisa akses:
- Admin dashboard
- Halaman manajemen produk
- Update pesanan (hanya bisa lihat)

---

##  Penting

- **Backup Rutin**: Backup database `amimi_shop` secara berkala
- **Stock Accuracy**: Pastikan stok selalu akurat dengan inventory fisik
- **Payment Verification**: Untuk COD, verifikasi pembayaran saat barang diterima
- **Customer Service**: Respon cepat terhadap pertanyaan/komplain pembeli

---

**Selamat mengelola toko Amimi Shop! **
