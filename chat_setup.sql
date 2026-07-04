-- ============================================
-- CHAT SYSTEM TABLES
-- Run this to add chat feature to Amimi Shop
-- ============================================

USE amimi_shop;

-- ============================================
-- MESSAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT,
    subject VARCHAR(150),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    message_type ENUM('user_message', 'auto_reply', 'admin_reply') DEFAULT 'user_message',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (sender_id),
    INDEX (receiver_id),
    INDEX (is_read),
    INDEX (created_at)
) ENGINE=InnoDB;

-- ============================================
-- AUTO-REPLY TEMPLATES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS auto_reply_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    trigger_keywords VARCHAR(255),
    reply_message TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- INSERT DEFAULT AUTO-REPLY TEMPLATES
-- ============================================
INSERT INTO auto_reply_templates (name, trigger_keywords, reply_message, is_active, priority) VALUES
('Greeting Default', '', ' Terima kasih telah menghubungi Amimi Shop!\n\nTim customer service kami akan merespons pesan Anda dalam waktu 1-2 jam. Untuk pertanyaan umum, berikut informasi yang mungkin membantu:\n\n Status Pesanan: Hubungi kami dengan nomor order Anda\n Pengiriman: Pengiriman gratis ke seluruh Indonesia\n Pembayaran: Terima COD, Transfer Bank, E-Wallet\n Kebijakan Retur: 7 hari jika barang tidak sesuai\n\nTunggu balasan dari admin kami ya! ', 1, 1),
('Jam Operasional', 'jam operasional|jam berapa|buka jam berapa|tutup jam berapa', ' Jam Operasional Amimi Shop:\n\nSenin - Jumat: 09:00 - 18:00 WIB\nSabtu: 10:00 - 17:00 WIB\nMinggu: Libur\n\nUntuk pertanyaan mendesak di luar jam operasional, Anda bisa meninggalkan pesan dan kami akan merespons di hari kerja berikutnya.\n\nTerima kasih! ', 1, 2),
('Info Pengiriman', 'pengiriman|ongkir|gratis ongkir|kurir|ongkos kirim|ekspedisi', ' Informasi Pengiriman Amimi Shop:\n\n Pengiriman Gratis ke Seluruh Indonesia\n⏱ Estimasi Pengiriman: 1-7 Hari Kerja\n Proses: Pesanan Diproses → Dikemas → Dikirim → Tiba\n\n Tracking: Nomor resi akan dikirim via SMS setelah barang dikirim\n Cek Status: Hubungi kami dengan nomor resi atau order\n\nUntuk info lebih detail, admin kami siap membantu! ', 1, 3),
('Metode Pembayaran', 'pembayaran|bayar|cicilan|transfer|cod|e-wallet|kartu kredit', ' Metode Pembayaran yang Tersedia:\n\n1⃣ COD (Cash on Delivery) - Bayar saat barang tiba\n2⃣ Transfer Bank - BCA, Mandiri, BNI, BRI\n3⃣ E-Wallet - GCash, OVO, Dana, LinkAja\n\n Diskon 5% untuk transfer sebelum jam 12 siang\n Semua transaksi aman dan terjamin\n\nAda kendala pembayaran? Hubungi admin kami! ', 1, 4);
