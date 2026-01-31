// ==========================================
// GRUPO DESPO - Main JavaScript
// ==========================================

const GrupoDespo = (function () {
    'use strict';

    // ==========================================
    // CART MODULE
    // ==========================================
    const Cart = {
        // Add to cart
        initAdd: function (btn) {
            console.log('Init Add Clicked');
            const card = btn.closest('.product-card');
            const productId = card.dataset.id;
            const actionsDiv = btn.closest('.card-actions');
            const qtyControl = actionsDiv.querySelector('.quantity-control');
            const input = qtyControl.querySelector('.qty-input');

            btn.style.display = 'none';
            qtyControl.style.display = 'flex';
            input.value = 1;

            this._sendUpdate(productId, 1, null);
        },

        // Update quantity
        updateQty: function (btn, change) {
            const container = btn.closest('.quantity-control');
            const input = container.querySelector('.qty-input');
            const actionsDiv = container.closest('.card-actions');
            const initBtn = actionsDiv.querySelector('.btn-add-initial');
            const card = actionsDiv.closest('.product-card');
            const productId = card.dataset.id;

            let currentVal = parseInt(input.value);
            let newVal = currentVal + change;

            if (newVal < 1) {
                container.style.display = 'none';
                initBtn.style.display = 'flex';
                this._sendUpdate(productId, -1, null);
            } else {
                input.value = newVal;
                this._sendUpdate(productId, change, null);
            }
        },

        // Update cart page item
        updateCartItem: function (btn, change) {
            const container = btn.closest('.quantity-control');
            const input = container.querySelector('.qty-input');
            const productId = container.dataset.id;
            let newVal = parseInt(input.value) + change;

            if (newVal < 1) return;

            this._sendUpdate(productId, change, function () {
                location.reload();
            });
        },

        // Private: Send AJAX update
        _sendUpdate: function (productId, delta, callback) {
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
                        this._updateBadge(data.count);
                        if (callback) callback();
                    } else {
                        console.error(data.message);
                        if (callback) alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (callback) alert("Error de conexión");
                });
        },

        // Private: Update cart badge
        _updateBadge: function (count) {
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.innerText = count;
                cartBadge.style.transform = 'scale(1.2)';
                setTimeout(() => cartBadge.style.transform = 'scale(1)', 200);
            }
        }
    };

    // ==========================================
    // PRODUCTS MODULE
    // ==========================================
    const Products = {
        currentCategory: 'todas',
        currentQuery: '',
        currentOffset: 20,
        limit: 20,

        init: function () {
            this._bindEvents();
        },

        _bindEvents: function () {
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const searchInput = document.getElementById('mainSearchInput');
            const searchBtn = document.getElementById('mainSearchBtn');
            const navLinks = document.querySelectorAll('.nav-pills a');

            // Category links
            if (navLinks.length > 0) {
                navLinks.forEach(link => {
                    link.addEventListener('click', (e) => this._onCategoryClick(e, link));
                });
            }

            // Search
            if (searchInput && searchBtn) {
                searchBtn.addEventListener('click', () => this._performSearch());
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') this._performSearch();
                });
            }

            // Load more
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', () => this.loadProducts(false));
            }
        },

        _onCategoryClick: function (e, link) {
            e.preventDefault();

            const currentActive = document.querySelector('.nav-pills li.active');
            if (currentActive) currentActive.classList.remove('active');
            link.parentElement.classList.add('active');

            this.currentCategory = link.getAttribute('data-category');
            this.currentQuery = '';

            const searchInput = document.getElementById('mainSearchInput');
            if (searchInput) searchInput.value = '';

            this.loadProducts(true);
        },

        _performSearch: function () {
            const searchInput = document.getElementById('mainSearchInput');
            const val = searchInput.value.trim();

            if (val === this.currentQuery && val === '') return;

            this.currentQuery = val;
            this.currentCategory = 'todas';

            const currentActive = document.querySelector('.nav-pills li.active');
            if (currentActive) currentActive.classList.remove('active');

            this.loadProducts(true);
        },

        loadProducts: function (reset) {
            const productGrid = document.querySelector('.product-grid');
            const loadMoreBtn = document.getElementById('loadMoreBtn');

            if (!productGrid) return;

            if (reset) {
                this.currentOffset = 0;
                productGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 30px;"></i></div>';
                if (loadMoreBtn) loadMoreBtn.style.display = 'none';
            } else {
                if (loadMoreBtn) loadMoreBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cargando...';
            }

            const url = `ajax/filtrar_productos.php?categoria=${encodeURIComponent(this.currentCategory)}&q=${encodeURIComponent(this.currentQuery)}&offset=${this.currentOffset}`;

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    if (reset) {
                        productGrid.innerHTML = html;
                        this.currentOffset = this.limit;
                        if (loadMoreBtn) {
                            loadMoreBtn.style.display = html.includes('product-card') ? 'inline-block' : 'none';
                            loadMoreBtn.innerHTML = 'Ver más productos';
                        }
                    } else {
                        if (html.trim() === '' || html.includes('No se encontraron')) {
                            if (loadMoreBtn) {
                                loadMoreBtn.innerText = 'No hay más productos';
                                loadMoreBtn.style.backgroundColor = '#ccc';
                                loadMoreBtn.disabled = true;
                            }
                        } else {
                            productGrid.insertAdjacentHTML('beforeend', html);
                            this.currentOffset += this.limit;
                            if (loadMoreBtn) {
                                loadMoreBtn.innerHTML = 'Ver más productos';
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
    };

    // ==========================================
    // PUBLIC API (Global functions for onclick)
    // ==========================================
    window.initAddToCart = function (btn) {
        Cart.initAdd(btn);
    };

    window.updateQty = function (btn, change) {
        Cart.updateQty(btn, change);
    };

    window.updateCartItemQty = function (btn, change) {
        Cart.updateCartItem(btn, change);
    };

    // ==========================================
    // INIT ON DOM READY
    // ==========================================
    document.addEventListener('DOMContentLoaded', function () {
        console.log('Grupo Despo App Initialized');
        Products.init();
    });

    // Return public API
    return {
        Cart: Cart,
        Products: Products
    };

})();
