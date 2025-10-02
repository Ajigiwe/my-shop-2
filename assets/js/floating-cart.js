document.addEventListener('DOMContentLoaded', function() {
    // Function to update cart count
    function updateCartCount() {
        // First try to get count from server-side session
        fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.count > 0) {
                    updateCartUI(data.count);
                } else {
                    // Fallback to client-side storage if server count is 0 or error
                    checkClientSideCart();
                }
            })
            .catch(() => {
                // If fetch fails, fall back to client-side storage
                checkClientSideCart();
            });
    }

    // Check client-side storage for cart items
    function checkClientSideCart() {
        let cart = [];
        if (sessionStorage.getItem('cart')) {
            cart = JSON.parse(sessionStorage.getItem('cart'));
        } else if (localStorage.getItem('cart')) {
            cart = JSON.parse(localStorage.getItem('cart'));
        }

        // Calculate total items in cart
        let totalItems = 0;
        if (Array.isArray(cart)) {
            totalItems = cart.reduce((total, item) => total + (item.quantity || 1), 0);
        }
        
        updateCartUI(totalItems);
    }
    
    // Update the cart UI with the count
    function updateCartUI(count) {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = count;
            cartCount.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    // Initial update
    updateCartCount();

    // Listen for custom event when cart is updated
    document.addEventListener('cartUpdated', updateCartCount);

    // Also update on page load in case cart was modified in another tab
    window.addEventListener('storage', function(e) {
        if (e.key === 'cart') {
            updateCartCount();
        }
    });
});
