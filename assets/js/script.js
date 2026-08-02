// Toast Notification System
if (typeof toastContainer === 'undefined') {
    var toastContainer = null;
}

// Dropdown functionality - DISABLED to prevent conflicts with Bootstrap
/*
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        // Close all other dropdowns first
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu.id !== dropdownId) {
                menu.classList.remove('show');
            }
        });

        // Toggle current dropdown
        dropdown.classList.toggle('show');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.matches('.dropdown-toggle')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});
*/

// Custom dropdown functionality - DISABLED to prevent conflicts with Bootstrap
/*
function toggleCustomDropdown(event, menuId) {
    event.preventDefault();
    event.stopPropagation();

    const menu = document.getElementById(menuId);
    if (menu) {
        // Close all other custom dropdowns first
        document.querySelectorAll('.dropdown-menu-custom').forEach(dropdown => {
            if (dropdown.id !== menuId) {
                dropdown.classList.remove('show');
            }
        });

        // Toggle current dropdown
        menu.classList.toggle('show');
    }
}

// Close custom dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-container')) {
        document.querySelectorAll('.dropdown-menu-custom').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});
*/

// Initialize toast container
function initializeToastContainer() {
    if (!document.getElementById('shop-toast-container')) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'shop-toast-container';
        toastContainer.className = 'fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] flex flex-col gap-2 pointer-events-none w-auto max-w-[90%]';
        document.body.appendChild(toastContainer);
    } else {
        toastContainer = document.getElementById('shop-toast-container');
    }
}

// Show toast notification
function showToast(message, type = 'info', duration = 3000) {
    initializeToastContainer();

    const toast = document.createElement('div');
    
    // Type-based colors
    const colors = {
        success: 'bg-[#1A1A1A] border-green-500',
        danger: 'bg-[#1A1A1A] border-red-500',
        warning: 'bg-[#1A1A1A] border-yellow-500',
        info: 'bg-[#1A1A1A] border-blue-500'
    };

    toast.className = `flex items-center gap-2.5 py-2.5 px-4 rounded-xl shadow-2xl border-l-4 text-white pointer-events-auto transition-all duration-500 translate-y-10 opacity-0 ${colors[type] || colors.info} min-w-[200px]`;
    
    const icons = {
        success: 'check_circle',
        danger: 'error',
        warning: 'warning',
        info: 'info'
    };

    toast.innerHTML = `
        <span class="material-symbols-outlined text-[20px] ${type === 'success' ? 'text-green-500' : ''}">${icons[type] || 'info'}</span>
        <div class="text-[13px] font-bold tracking-tight">${message}</div>
    `;

    toastContainer.appendChild(toast);
    
    // Animate in
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-10', 'opacity-0');
    });

    // Auto remove
    setTimeout(() => {
        toast.classList.add('translate-y-[-10px]', 'opacity-0');
        setTimeout(() => toast.remove(), 500);
    }, duration);
}

// Modern features: Dark mode, improved animations, lazy loading, better UX

document.addEventListener('DOMContentLoaded', function() {
    console.log('Script.js loaded and DOM ready');

    // Initialize theme
    initializeTheme();

    // Initialize lazy loading
    initializeLazyLoading();

    // Initialize smooth scrolling
    initializeSmoothScrolling();

    // Initialize loading states
    initializeLoadingStates();

    // Auto-hide old alerts after 5 seconds (for any remaining inline alerts)
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Update cart quantity
    const updateCartButtons = document.querySelectorAll('.update-cart-btn');
    updateCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const productId = this.dataset.productId;
            const quantity = document.querySelector(`#cart-quantity-${productId}`).value;

            updateCartItem(productId, quantity);
        });
    });

    // Remove from cart
    const removeFromCartButtons = document.querySelectorAll('.remove-from-cart-btn');
    removeFromCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const productId = this.dataset.productId;

            if (confirm('Are you sure you want to remove this item from your cart?')) {
                removeFromCart(productId);
            }
        });
    });

    // Enhanced search with autocomplete - Handle both navbar and main search
    const searchInputs = document.querySelectorAll('#searchInput, #mainSearchInput');
    console.log('🔍 Found search inputs:', searchInputs.length);

    searchInputs.forEach(searchInput => {
        if (searchInput) {
            console.log('✅ Setting up search for:', searchInput.id);
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                console.log('📝 Search input changed:', query);

                if (query.length < 2) {
                    hideSearchResults();
                    return;
                }

                // Show loading state
                showSearchLoading();

                searchTimeout = setTimeout(() => {
                    console.log('🔍 Making search request for:', query);
                    searchProducts(query);
                }, 300);
            });

            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                const searchResults = document.querySelector('#searchResults, #mainSearchResults');
                if (searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    hideSearchResults();
                }
            });

            // Keyboard navigation for search results
            searchInput.addEventListener('keydown', function(e) {
                handleSearchKeyNavigation(e);
            });
        }
    });

    // Dark mode toggle
    const themeToggle = document.querySelector('#themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // Password confirmation
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        if (input.name === 'confirm_password') {
            input.addEventListener('input', function() {
                const password = document.querySelector('input[name="password"]').value;
                const confirmPassword = this.value;

                if (password !== confirmPassword) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    });

    // Wishlist Toggle Functionality
    document.addEventListener('click', function(e) {
        const wishlistBtn = e.target.closest('.wishlist-btn');
        if (wishlistBtn) {
            e.preventDefault();
            const productId = wishlistBtn.dataset.productId;
            
            // Add loading state
            const icon = wishlistBtn.querySelector('.material-symbols-outlined');
            const originalColor = wishlistBtn.className;
            
            fetch(window.SHOP_URL + 'ajax/toggle_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'added') {
                        wishlistBtn.classList.add('active');
                        wishlistBtn.classList.remove('text-[#1A1A1A]');
                        wishlistBtn.classList.add('text-red-500');
                        if (icon) icon.classList.add('fill-1');
                    } else {
                        wishlistBtn.classList.remove('active');
                        wishlistBtn.classList.remove('text-red-500');
                        wishlistBtn.classList.add('text-[#1A1A1A]');
                        if (icon) icon.classList.remove('fill-1');
                    }
                    if (typeof window.refreshWishlistBadge === 'function' && data.wishlist_count) {
                        window.refreshWishlistBadge(data.wishlist_count);
                    }
                    showToast(data.message, 'success', 2000);
                } else if (data.login_required) {
                    showToast(data.message, 'warning', 3000);
                    // Optional: redirect to login
                    // window.location.href = 'login.php';
                } else {
                    showToast(data.message, 'danger', 3000);
                }
            })
            .catch(err => {
                console.error('Wishlist error:', err);
                showToast('Something went wrong', 'danger', 3000);
            });
        }
    });
});

// Theme Management
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const themeIcon = document.querySelector('#themeIcon');

    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if (themeIcon) themeIcon.className = 'fas fa-sun';
    }
}

function toggleTheme() {
    const body = document.body;
    const themeIcon = document.querySelector('#themeIcon');
    const isDark = body.classList.contains('dark-mode');

    if (isDark) {
        body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        if (themeIcon) themeIcon.className = 'fas fa-moon';
        showToast('Light mode activated', 'info', 2000);
    } else {
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        if (themeIcon) themeIcon.className = 'fas fa-sun';
        showToast('Dark mode activated', 'info', 2000);
    }
}

// Lazy Loading
function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

// Smooth Scrolling
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Loading States
function initializeLoadingStates() {
    // Add loading states to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                submitBtn.disabled = true;
            }
        });
    });
}

// Update cart item
function updateCartItem(productId, quantity) {
    fetch('ajax/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to update totals
        } else {
            showAlert(data.message || 'Error updating cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating cart', 'danger');
    });
}

// Remove from cart
function removeFromCart(productId) {
    fetch('ajax/remove_from_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to update cart
        } else {
            showAlert(data.message || 'Error removing item from cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error removing item from cart', 'danger');
    });
}

// Search products - Enhanced debugging
function searchProducts(query) {
    console.log('🔍 Searching for:', query);

    // Use a simple fetch to the search endpoint
    fetch('ajax/search.php?query=' + encodeURIComponent(query))
    .then(response => {
        console.log('📡 Response status:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Search results received:', data);
        if (Array.isArray(data)) {
            console.log('📊 Results count:', data.length);
            displaySearchResults(data);
        } else {
            console.error('❌ Invalid response format:', data);
        }
    })
    .catch(error => {
        console.error('💥 Search error:', error);
    });
}

// Enhanced search results display
function displaySearchResults(results) {
    console.log('Displaying search results for containers'); // Debug log
    const searchContainers = ['#searchResults', '#mainSearchResults'];
    searchContainers.forEach(containerId => {
        const searchContainer = document.querySelector(containerId);
        console.log(`Processing container: ${containerId}, found:`, !!searchContainer); // Debug log
        if (!searchContainer) return;

        if (results.length === 0) {
            searchContainer.innerHTML = '<div class="search-result-item text-muted">No products found</div>';
            searchContainer.style.display = 'block';
            console.log('No results found, showing message'); // Debug log
            return;
        }

        console.log(`Displaying ${results.length} results`); // Debug log
        let html = '';
        results.forEach((product, index) => {
            const availabilityBadge = product.in_stock ?
                '<span class="badge bg-success ms-2">In Stock</span>' :
                '<span class="badge bg-danger ms-2">Out of Stock</span>';

            html += `
                <div class="search-result-item ${containerId === '#mainSearchResults' ? 'search-result-item-main' : ''}" data-index="${index}" onclick="selectSearchResult(${product.id}, '${product.name.replace(/'/g, "\\'")}')">
                    <div class="d-flex align-items-center">
                        <img src="${product.image}" alt="${product.name}"
                             class="me-3 rounded" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #eee;" onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${highlightSearchTerm(product.name, getCurrentSearchTerm())}</strong><br>
                                    <small class="text-muted">${product.category_name}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success">${formatCurrency(product.price)}</div>
                                    ${product.review_count > 0 ? `<small class="text-warning">★ ${product.average_rating?.toFixed(1) || '0.0'} (${product.review_count})</small>` : ''}
                                </div>
                            </div>
                            ${availabilityBadge}
                        </div>
                    </div>
                </div>
            `;
        });

        searchContainer.innerHTML = html;
        searchContainer.style.display = 'block';
        console.log(`Results displayed in ${containerId}`); // Debug log
    });
}

// Show search loading state
function showSearchLoading() {
    const searchContainers = ['#searchResults', '#mainSearchResults'];
    searchContainers.forEach(containerId => {
        const searchContainer = document.querySelector(containerId);
        if (searchContainer) {
            searchContainer.innerHTML = '<div class="search-result-item text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Searching...</div>';
            searchContainer.style.display = 'block';
        }
    });
}

// Get current search term
function getCurrentSearchTerm() {
    const searchInput = document.querySelector('#searchInput') || document.querySelector('#mainSearchInput');
    return searchInput ? searchInput.value.trim() : '';
}

// Hide search results
function hideSearchResults() {
    const searchContainers = ['#searchResults', '#mainSearchResults'];
    searchContainers.forEach(containerId => {
        const searchContainer = document.querySelector(containerId);
        if (searchContainer) {
            searchContainer.style.display = 'none';
        }
    });
}

// Select search result - Enhanced with better navigation
function selectSearchResult(productId, productName) {
    // Hide search results immediately
    hideSearchResults();

    // Navigate to product page
    window.location.href = `product.php?id=${productId}`;
}

// Update cart count
function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
        element.style.display = count > 0 ? 'inline' : 'none';
    });
}

// Show alert (now uses toast notifications) - 2 seconds for consistency
function showAlert(message, type = 'info') {
    showToast(message, type, 2000); // Show for 2 seconds consistently
}

// Validate form
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }

        field.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });

    return isValid;
}

// Enhanced price formatting for Ghana Cedis
function formatCurrency(price) {
    const num = parseFloat(price) || 0;
    return 'GH₵' + num.toLocaleString('en-GH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Animate elements on scroll
function animateOnScroll() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
}

// Initialize animations when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', animateOnScroll);
} else {
    animateOnScroll();
}

// Update product quantity
function updateQuantity(productId, change) {
    const quantityInput = document.querySelector(`#quantity-${productId}`);
    if (quantityInput) {
        let currentQuantity = parseInt(quantityInput.value);
        currentQuantity += change;

        if (currentQuantity < 1) currentQuantity = 1;
        if (currentQuantity > 99) currentQuantity = 99;

        quantityInput.value = currentQuantity;
    }

     // Hero Slider Functionality (add this with your existing code)
const slides = document.querySelectorAll('.hero-slide');
let currentSlide = 0;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (i === index) {
            slide.classList.add('active');
        }
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
}

// Auto-advance slides every 5 seconds
setInterval(nextSlide, 5000);

// Optional: Add click navigation
const heroSlider = document.querySelector('.hero-slider');
if (heroSlider) {
    heroSlider.addEventListener('click', nextSlide);
}
    

}
