<nav class="navbar" id="navbar">
    <div class="container nav-container">
        <a href="/Amimi/" class="nav-logo">
            <img src="/Amimi/assets/img/logo.jpeg" alt="Amimi Logo" style="height: 32px; border-radius: 6px;">
        </a>

        <div class="nav-links" id="navLinks">
            <a href="/Amimi/" class="nav-link">Beranda</a>
            <?php if (isAdmin()): ?>
                <a href="/Amimi/products.php" class="nav-link">Kelola Produk</a>
            <?php else: ?>
                <a href="/Amimi/shop.php" class="nav-link"> Belanja</a>
            <?php endif; ?>
            <?php if (isLoggedIn() && !isAdmin()): ?>
                <a href="/Amimi/customer/dashboard.php" class="nav-link">Dashboard</a>
                <a href="/Amimi/customer/orders.php" class="nav-link">Pesanan Saya</a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
                <a href="/Amimi/admin/" class="nav-link">Dashboard</a>
                <a href="/Amimi/admin/orders.php" class="nav-link">Pesanan</a>
            <?php endif; ?>
        </div>

        <div class="nav-actions">
            <?php if (!isAdmin()): ?>
            <!-- Chat Icon for Customers -->
            <a href="/Amimi/customer/chat.php" class="nav-chat" title="Hubungi Kami">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <?php 
                    $customerUnread = getUserUnreadCount($_SESSION['user_id'] ?? 0);
                    if ($customerUnread > 0): 
                ?>
                    <span class="chat-badge" id="chatBadge"><?= $customerUnread ?></span>
                <?php endif; ?>
            </a>

            <!-- Cart Icon -->
            <a href="/Amimi/cart/" class="nav-cart" id="navCart" title="Keranjang Belanja">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge" id="cartBadge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <?php else: ?>
            <!-- Chat Icon for Admin -->
            <a href="/Amimi/admin/chat.php" class="nav-chat" title="Pesan Pelanggan">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <?php 
                    $unreadMessages = getUnreadMessageCount();
                    if ($unreadMessages > 0): 
                ?>
                    <span class="chat-badge" id="chatBadge"><?= $unreadMessages ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                <div class="nav-dropdown" id="userDropdown">
                    <button class="nav-user-btn" onclick="toggleDropdown()">
                        <?php if (!empty($_SESSION['user_avatar'])): ?>
                            <img src="<?= $_SESSION['user_avatar'] ?>" alt="Avatar" class="user-avatar-small">
                        <?php else: ?>
                            <div class="user-avatar-placeholder"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <span class="user-name-nav"><?= sanitize($_SESSION['user_name']) ?></span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <?php if (!isAdmin()): ?>
                            <div class="dropdown-header">
                                <small class="text-muted">ID: <?= $_SESSION['customer_id'] ?? '' ?></small>
                            </div>
                            <a href="/Amimi/customer/dashboard.php" class="dropdown-item"> Dashboard</a>
                            <a href="/Amimi/customer/profile.php" class="dropdown-item"> Profil Saya</a>
                            <a href="/Amimi/customer/orders.php" class="dropdown-item"> Pesanan Saya</a>
                        <?php endif; ?>
                        <hr class="dropdown-divider">
                        <a href="/Amimi/auth/logout.php" class="dropdown-item text-danger"> Keluar</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/Amimi/auth/login.php" class="btn btn-outline btn-sm">Masuk</a>
                <a href="/Amimi/auth/register.php" class="btn btn-gold btn-sm">Daftar</a>
            <?php endif; ?>

            <button class="nav-toggle" id="navToggle" onclick="toggleNav()">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>
