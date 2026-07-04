#  AMIMI SHOP - Sistem E-Commerce Modern

Sistem e-commerce lengkap dengan tampilan **Shopee-like** yang responsif dan profesional. Mendukung manajemen produk, keranjang belanja, checkout dengan multiple payment methods, dan tracking pesanan.

---

##  Fitur Utama

### Untuk Pembeli (Customer)
 Dashboard pembeli dengan statistik
 Toko produk dengan grid layout modern  
 Filter & search produk
 Sorting (terbaru, harga terendah/tertinggi)
 Keranjang belanja interaktif
 Checkout dengan 3 metode pembayaran
 Pre-order & tracking pesanan
 Profil & edit data pribadi
 Responsive design (mobile-friendly)

### Untuk Admin
 Dashboard dengan KPI bisnis
 Manajemen produk (CRUD lengkap)
 Kategori produk
 Manajemen pesanan & status
 Tracking profit & loss
 Update stok otomatis

### Keamanan
 Password hashing BCRYPT
 CSRF protection di semua form
 Session management
 Input sanitization
 Validasi data

---

##  Struktur File

```
Amimi/
├── index.php                    # Halaman utama (routing berdasarkan role)
├── shop.php                     # 🆕 Halaman toko untuk pembeli (Shopee-like)
├── product_detail.php           # Detail produk
├── products.php                 # Halaman admin (kelola produk)
│
├── admin/                       # Folder admin
│   ├── index.php               # Dashboard admin
│   ├── products.php            # Kelola produk
│   ├── product_form.php        # Form tambah/edit produk
│   ├── product_delete.php      # Hapus produk
│   ├── orders.php              # Kelola pesanan
│   ├── order_detail.php        # Detail pesanan
│   └── ...
│
├── customer/                    # Folder pembeli
│   ├── dashboard.php           # 🆕 Dashboard pembeli
│   ├── profile.php             # Edit profil
│   ├── orders.php              # Riwayat pesanan
│   └── order_detail.php        # Detail pesanan
│
├── cart/                        # Folder keranjang
│   ├── index.php               # Halaman keranjang
│   ├── add.php                 # Tambah ke keranjang
│   ├── update.php              # Update jumlah
│   └── remove.php              # Hapus item
│
├── checkout/                    # Folder checkout
│   ├── index.php               # Form checkout
│   ├── process.php             # Proses pesanan
│   └── success.php             # Konfirmasi sukses
│
├── auth/                        # Folder autentikasi
│   ├── login.php               # Login pembeli
│   ├── register.php            # Register pembeli
│   ├── logout.php              # Logout
│   └── google_callback.php     # Google OAuth callback
│
├── config/                      # Konfigurasi
│   ├── database.php            # Koneksi database
│   └── google_auth.php         # Google OAuth config
│
├── includes/                    # Include files
│   ├── header.php              # Header & CSS
│   ├── footer.php              # Footer
│   ├── navbar.php              # Navigasi
│   └── functions.php           # Helper functions
│
├── css/                         # Stylesheet
│   ├── style.css               # Style utama
│   └── shopee-style.css        # 🆕 Shopee-like styling
│
├── js/                          # JavaScript
│   └── app.js                  # Script umum
│
├── uploads/                     # Upload files
│   └── products/               # Gambar produk
│
├── setup.sql                    # Database setup script
├── PANDUAN_PEMBELI.md          # 🆕 Panduan untuk pembeli
├── PANDUAN_ADMIN.md            # 🆕 Panduan untuk admin
└── README.md                    # File ini
```

---

##  Quick Start

### 1. Setup Database

```sql
-- Run di phpMyAdmin atau MySQL CLI
-- Import file: setup.sql
```

Atau buat database baru:
- Database name: `amimi_shop`
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`

### 2. Konfigurasi Database

Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'amimi_shop');
```

### 3. Buat Akun Pembeli

**Opsi A: Menggunakan Script**
```php
// Jalankan di browser atau terminal:
php create_buyer_account.php
```

**Opsi B: Manual Registration**
- Buka: `http://localhost/Amimi/auth/register.php`
- Isi form dan daftar

### 4. Akses Aplikasi

**Pembeli:**
- Login: `http://localhost/Amimi/auth/login.php`
- Dashboard: `http://localhost/Amimi/customer/dashboard.php`
- Toko: `http://localhost/Amimi/shop.php`

**Admin:**
- Dashboard: `http://localhost/Amimi/admin/`
- Manajemen Produk: `http://localhost/Amimi/admin/products.php`
- Manajemen Pesanan: `http://localhost/Amimi/admin/orders.php`

---

##  Akun Test

### Pembeli
- **Email**: `pembeli@amimi.com`
- **Password**: `password`
- **Customer ID**: `AMM-00003`
- **Status**: Aktif

### Admin
- Hubungi developer untuk credential admin
- Atau buat via direct database query:
```sql
INSERT INTO users (customer_id, name, email, password, phone, address, role)
VALUES ('ADM-00001', 'Admin Amimi', 'admin@amimi.com', PASSWORD_BCRYPT('password'), '081234567890', 'Jl. Amimi', 'admin');
```

---

##  Alur Berbelanja

```
1. Login/Register
   ↓
2. Browse Produk (Filter & Search)
   ↓
3. Lihat Detail Produk
   ↓
4. Tambah ke Keranjang
   ↓
5. Review Keranjang
   ↓
6. Checkout
   ├─ Isi Alamat Pengiriman
   ├─ Pilih Metode Pembayaran
   └─ Konfirmasi Pesanan
   ↓
7. Tracking Pesanan
   ├─ Status Processing
   ├─ Status Shipped
   └─ Status Delivered
```

---

##  Metode Pembayaran

### 1. **COD (Bayar di Tempat)**
- Sistem Pre-Order
- Pembayaran saat barang tiba
- Status: Pending → Delivered

### 2. **M-Banking / Transfer Bank**
- Virtual Account BCA
- Simulasi: `8892 0812 3456 7890`
- Status: Otomatis Confirmed

### 3. **E-Wallet (QRIS)**
- Support: OVO, GoPay, Dana, LinkAja, ShopeePay
- Simulasi QR Code
- Status: Otomatis Confirmed

---

##  UI/UX Features

### Shopee-like Design
- Modern dark theme dengan accent gold
- Grid layout responsif
- Smooth transitions & animations
- Product cards dengan rating & stock badge
- Quick action buttons

### Mobile Responsive
- Breakpoint tablet & mobile
- Touch-friendly buttons
- Optimized layout untuk small screens

### User Experience
- Auto-complete form dari profil
- Real-time cart count badge
- Instant visual feedback
- Search bar prominent di toko
- Filter & sorting built-in

---

##  Teknologi

### Backend
- **PHP 7.4+**
- **MySQL 5.7+** / MariaDB
- **Sessions** untuk autentikasi

### Frontend
- **HTML5**
- **CSS3** (Dark theme, Grid, Flexbox)
- **Vanilla JavaScript** (No framework)
- **Responsive Design**

### Database
- **Users**: Pembeli & Admin
- **Products**: Katalog produk
- **Categories**: Kategori produk
- **Cart**: Keranjang belanja
- **Orders**: Riwayat pesanan
- **Order Items**: Detail item pesanan

---

##  Database Schema

### Users Table
```sql
- id (PK)
- customer_id (Unique)
- name
- email (Unique)
- password
- phone
- address
- role (admin/customer)
- google_id
- avatar
- created_at, updated_at
```

### Products Table
```sql
- id (PK)
- category_id (FK)
- name
- description
- price
- cost_price
- stock
- sizes
- image
- is_active
- created_at, updated_at
```

### Orders Table
```sql
- id (PK)
- user_id (FK)
- order_number (Unique)
- total_amount
- payment_method
- payment_status
- order_status
- shipping_address
- shipping_phone
- notes
- created_at, updated_at
```

### Cart Table
```sql
- id (PK)
- user_id (FK)
- product_id (FK)
- size
- quantity
- created_at
```

---

##  Security

### Best Practices Implemented
 Password hashing dengan BCRYPT
 CSRF token di semua form
 Session validation
 Input sanitization (htmlspecialchars)
 SQL injection prevention (prepared statements)
 SQL charset UTF-8MB4
 Secure HTTP headers

### Rekomendasi Produksi
- [ ] Enable HTTPS
- [ ] Setup rate limiting
- [ ] Setup backup otomatis
- [ ] Monitor error logs
- [ ] Setup CDN untuk static assets
- [ ] Enable gzip compression

---

##  Dokumentasi Lengkap

- **[PANDUAN_PEMBELI.md](PANDUAN_PEMBELI.md)** - Cara berbelanja untuk pelanggan
- **[PANDUAN_ADMIN.md](PANDUAN_ADMIN.md)** - Manajemen untuk admin

---

##  Helper Functions (functions.php)

```php
// Autentikasi
isLoggedIn()           // Cek user login
isAdmin()              // Cek role admin
requireLogin()         // Force login redirect
requireAdmin()         // Force admin redirect

// Data
getUserById()          // Get user info
formatRupiah()         // Format currency
getCartCount()         // Get cart item count
getProductImage()      // Get product image URL

// Status Labels
getOrderStatusLabel()       // Order status badge
getPaymentStatusLabel()     // Payment status badge
getPaymentMethodLabel()     // Payment method label

// Security
sanitize()             // Input sanitization
csrfField()            // CSRF hidden input
validateCsrfToken()    // CSRF validation
generateCsrfToken()    // Generate CSRF token

// Utilities
generateCustomerId()   // Generate AMM-XXXXX
generateOrderNumber()  // Generate ORD-YYYYMMDD-XXXX
setFlash()             // Set flash message
getFlash()             // Get & clear flash message
redirect()             // Redirect to URL
```

---

##  Troubleshooting

### Database Connection Error
**Solusi**:
1. Pastikan MySQL/MariaDB running
2. Check `config/database.php` settings
3. Verify database & user exist
4. Run `setup.sql` untuk create tables

### Produk tidak tampil di toko
**Solusi**:
1. Pastikan produk `is_active = 1`
2. Pastikan kategori exist
3. Clear browser cache
4. Check browser console untuk JS errors

### Cart items hilang
**Solusi**:
1. Check session timeout di php.ini
2. Pastikan cookies enabled
3. Check database cart table

### Admin tidak bisa akses dashboard
**Solusi**:
1. Verify user role di database = 'admin'
2. Clear session & login ulang
3. Check browser console

---

##  Support

Untuk pertanyaan atau masalah:
1. Check file PANDUAN_PEMBELI.md atau PANDUAN_ADMIN.md
2. Check database untuk data consistency
3. Check browser console untuk JavaScript errors
4. Check server logs untuk PHP errors

---

##  License

Sistem ini dibuat khusus untuk Amimi Shop.

---

**Version**: 1.0  
**Last Updated**: 2026-07-01  
**Status**: Production Ready 

---

** Terima kasih telah menggunakan Amimi Shop!**
