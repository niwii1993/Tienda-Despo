// Main JavaScript for Grupo Despo Arcor E-commerce

// 1. Define helper functions GLOBALLY so they can be called via onclick="" attributes
window.initAddToCart = function (btn) {
    console.log('Init Add Clicked');
    const card = btn.closest('.product-card');
    const productId = card.dataset.id;
    const actionsDiv = btn.closest('.card-actions');
    const qtyControl = actionsDiv.querySelector('.quantity-control');
    const input = qtyControl.querySelector('.qty-input');

    // Optimistic UI update
    btn.style.display = 'none';
    qtyControl.style.display = 'flex';
    input.value = 1; // Reset to 1

    sendCartUpdate(productId, 1, null);
};

window.updateQty = function (btn, change) {
    const container = btn.closest('.quantity-control');
    const input = container.querySelector('.qty-input');
    const actionsDiv = container.closest('.card-actions');
    const initBtn = actionsDiv.querySelector('.btn-add-initial');
    const card = actionsDiv.closest('.product-card');
    const productId = card.dataset.id;

    let currentVal = parseInt(input.value);
    let newVal = currentVal + change;

    if (newVal < 1) {
        // Remove item logic -> Revert to "Add" button
        container.style.display = 'none';
        initBtn.style.display = 'flex';
        // Send -1 to remove (since backend checks quantity <=0)
        sendCartUpdate(productId, -1, null);
    } else {
        // Update input immediately
        input.value = newVal;
        sendCartUpdate(productId, change, null);
    }
};

window.updateCartItemQty = function (btn, change) {
    const container = btn.closest('.quantity-control');
    const input = container.querySelector('.qty-input');
    const productId = container.dataset.id;
    let newVal = parseInt(input.value) + change;

    // Minimum 1, to remove user must click trash icon
    if (newVal < 1) return;

    // Send update and reload to recalculate totals
    sendCartUpdate(productId, change, function () {
        location.reload();
    });
};

// Helper to execute AJAX logic
function sendCartUpdate(productId, delta, callback) {
    const formData = new FormData();
    formData.append('id', productId);
    formData.append('cantidad', delta);

    fetch('ajax/agregar_carrito.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update badge globally
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    cartBadge.innerText = data.count;
                    // Animation
                    cartBadge.style.transform = 'scale(1.2)';
                    setTimeout(() => cartBadge.style.transform = 'scale(1)', 200);
                }
                if (callback) callback();
            } else {
                console.error(data.message);
                // Alert user if on cart page (callback present usually implies we need feedback)
                if (callback) alert("Error: " + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (callback) alert("Error de conexión");
        });
}


// 2. DOM Ready Logic
document.addEventListener('DOMContentLoaded', () => {
    console.log('Grupo Despo App Initialized');

    // Cart Navigation (Backup listener if onclick fails)
    const cartBtn = document.querySelector('.cart-btn');
    if (cartBtn) {
        cartBtn.addEventListener('click', () => {
            window.location.href = 'carrito.php';
        });
    }

    // 3. Product Manager Logic (Search, Filter, Pagination)
    let currentCategory = 'todas';
    let currentQuery = '';
    let currentOffset = 20; // Matches initial PHP load
    const limit = 20;

    const productGrid = document.querySelector('.product-grid');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const searchInput = document.getElementById('mainSearchInput');
    const searchBtn = document.getElementById('mainSearchBtn');
    const navLinks = document.querySelectorAll('.nav-pills a');

    // Function to Load Products
    function loadProducts(reset = false) {
        if (!productGrid) return;

        if (reset) {
            currentOffset = 0;
            productGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 30px; color: var(--primary-blue);"></i></div>';
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
        } else {
            if (loadMoreBtn) loadMoreBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cargando...';
        }

        const url = `ajax/filtrar_productos.php?categoria=${encodeURIComponent(currentCategory)}&q=${encodeURIComponent(currentQuery)}&offset=${currentOffset}`;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                if (reset) {
                    productGrid.innerHTML = html;
                    currentOffset = limit;
                    // Check if we should show load more (naive check: if HTML seems substantial)
                    if (loadMoreBtn) {
                        loadMoreBtn.style.display = html.includes('product-card') ? 'inline-block' : 'none';
                        loadMoreBtn.innerHTML = 'Ver más productos';
                    }
                } else {
                    // Append mode
                    if (html.trim() === '' || html.includes('No se encontraron')) {
                        if (loadMoreBtn) {
                            // User reached the end
                            loadMoreBtn.innerText = 'No hay más productos';
                            loadMoreBtn.style.backgroundColor = '#ccc';
                            loadMoreBtn.style.borderColor = '#ccc';
                            loadMoreBtn.style.color = '#666';
                            loadMoreBtn.style.display = 'inline-block';
                            loadMoreBtn.style.cursor = 'default';
                            loadMoreBtn.disabled = true; // Prevent further clicks
                            // console.log("End of list reached");
                        }
                    } else {
                        productGrid.insertAdjacentHTML('beforeend', html);
                        currentOffset += limit;
                        if (loadMoreBtn) {
                            loadMoreBtn.innerHTML = 'Ver más productos';
                            loadMoreBtn.style.display = 'inline-block'; // Ensure it stays visible
                            loadMoreBtn.disabled = false;
                        }
                    }
                }
            })
            .catch(err => {
                console.error('Error loading products:', err);
                if (reset) productGrid.innerHTML = '<p style="text-align:center;">Error cargando productos.</p>';
            });
    }

    // Bind Category Links
    if (navLinks.length > 0) {
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                // UI Toggle
                const currentActive = document.querySelector('.nav-pills li.active');
                if (currentActive) currentActive.classList.remove('active');
                link.parentElement.classList.add('active');

                // Logic
                currentCategory = link.getAttribute('data-category');
                currentQuery = ''; // Reset search
                if (searchInput) searchInput.value = ''; // Clear input

                loadProducts(true);
            });
        });
    }

    // Bind Search
    if (searchInput && searchBtn) {
        const performSearch = () => {
            const val = searchInput.value.trim();
            if (val === currentQuery && val === '') return; // No change

            currentQuery = val;
            currentCategory = 'todas'; // Search usually resets category context or searches globally. Let's reset.

            // Visual reset of pills
            const currentActive = document.querySelector('.nav-pills li.active');
            if (currentActive) currentActive.classList.remove('active');
            // Optionally highlight "Todo" or nothing? Let's leave pills inactive to show we are searching custom.

            loadProducts(true);
        };

        searchBtn.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performSearch();
        });
    }

    // Bind Load More
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            loadProducts(false);
        });
    }
});
