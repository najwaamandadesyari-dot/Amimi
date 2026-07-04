/**
 * Amimi Shop Front-End Interaction Script
 */

// Dropdown Menu Toggle
function toggleDropdown() {
    const dropdown = document.getElementById('dropdownMenu');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// Close dropdowns if clicking outside
window.addEventListener('click', function(e) {
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown && !userDropdown.contains(e.target)) {
        const dropdownMenu = document.getElementById('dropdownMenu');
        if (dropdownMenu) {
            dropdownMenu.classList.remove('show');
        }
    }
});

// Mobile Nav Toggle
function toggleNav() {
    const navLinks = document.getElementById('navLinks');
    const navToggle = document.getElementById('navToggle');
    if (navLinks) {
        navLinks.classList.toggle('show');
    }
}

// Size Selection handler
document.addEventListener('DOMContentLoaded', function() {
    const sizeBtns = document.querySelectorAll('.size-btn');
    const sizeInput = document.getElementById('selectedSize');

    sizeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            sizeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            if (sizeInput) {
                sizeInput.value = this.dataset.size;
            }
        });
    });

    // Flash message auto close after 5 seconds
    const flashMessage = document.getElementById('flashMessage');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.transition = 'opacity 0.5s ease';
            flashMessage.style.opacity = '0';
            setTimeout(() => flashMessage.remove(), 500);
        }, 5000);
    }
});

/**
 * Cart AJAX helper functions
 */
function updateCartQty(cartId, action) {
    const input = document.getElementById(`qty-${cartId}`);
    if (!input) return;

    let qty = parseInt(input.value);
    if (action === 'plus') {
        qty++;
    } else if (action === 'minus') {
        qty--;
    }

    if (qty < 1) return;

    fetch('/Amimi/cart/update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}&quantity=${qty}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = qty;
            // Update items on page
            const price = parseFloat(document.getElementById(`price-${cartId}`).dataset.price);
            const subtotalEl = document.getElementById(`subtotal-${cartId}`);
            if (subtotalEl) {
                subtotalEl.innerText = formatRupiah(price * qty);
            }
            
            // Update total summary
            const summaryTotalEl = document.getElementById('summaryTotal');
            if (summaryTotalEl && data.total_amount) {
                summaryTotalEl.innerText = formatRupiah(data.total_amount);
            }
            
            // Update nav cart badge
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                if (data.cart_count > 0) {
                    cartBadge.innerText = data.cart_count;
                } else {
                    cartBadge.remove();
                }
            }
        } else {
            alert(data.message || 'Gagal mengubah jumlah');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan koneksi');
    });
}

function deleteCartItem(cartId) {
    if (!confirm('Apakah Anda yakin ingin menghapus produk ini dari keranjang?')) return;

    fetch('/Amimi/cart/remove.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Remove DOM element
            const itemRow = document.getElementById(`cart-item-${cartId}`);
            if (itemRow) {
                itemRow.remove();
            }

            // Check if cart is now empty
            const cartList = document.querySelector('.cart-items');
            if (cartList && cartList.children.length === 0) {
                location.reload(); // Reload to show empty state
            }

            // Update total summary
            const summaryTotalEl = document.getElementById('summaryTotal');
            if (summaryTotalEl && data.total_amount) {
                summaryTotalEl.innerText = formatRupiah(data.total_amount);
            }

            // Update nav cart badge
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                if (data.cart_count > 0) {
                    cartBadge.innerText = data.cart_count;
                } else {
                    cartBadge.remove();
                }
            }
        } else {
            alert(data.message || 'Gagal menghapus produk');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan koneksi');
    });
}

// Utility formatting
function formatRupiah(number) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
}
