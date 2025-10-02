document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="q"]');
    const searchForm = document.querySelector('form[action*="search.php"]');
    let searchTimeout;

    if (!searchInput) return;

    // Create autocomplete dropdown
    const autocompleteDropdown = document.createElement('div');
    autocompleteDropdown.className = 'autocomplete-dropdown';
    autocompleteDropdown.style.display = 'none';
    searchForm.appendChild(autocompleteDropdown);

    // Handle search input
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Hide dropdown if query is empty
        if (query.length === 0) {
            autocompleteDropdown.style.display = 'none';
            return;
        }

        // Only search after user stops typing for 300ms
        searchTimeout = setTimeout(() => {
            fetchAutocompleteResults(query);
        }, 300);
    });

    // Handle form submission
    searchForm.addEventListener('submit', function(e) {
        if (searchInput.value.trim() === '') {
            e.preventDefault();
            searchInput.focus();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            autocompleteDropdown.style.display = 'none';
        }
    });

    // Fetch autocomplete results
    function fetchAutocompleteResults(query) {
        fetch(`api/search_autocomplete.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    showAutocompleteResults(data);
                } else {
                    autocompleteDropdown.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching autocomplete results:', error);
                autocompleteDropdown.style.display = 'none';
            });
    }

    // Display autocomplete results
    function showAutocompleteResults(results) {
        autocompleteDropdown.innerHTML = '';
        
        results.slice(0, 5).forEach(item => {
            const itemElement = document.createElement('a');
            itemElement.href = `product.php?id=${item.id}`;
            itemElement.className = 'autocomplete-item d-block p-2 text-decoration-none text-dark';
            itemElement.style.borderBottom = '1px solid #eee';
            itemElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="assets/images/${item.image || 'placeholder.jpg'}" 
                         alt="${item.name}" 
                         style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px; margin-right: 10px;">
                    <div>
                        <div class="small">${item.name}</div>
                        <div class="text-muted small">${formatCurrency(item.price)}</div>
                    </div>
                </div>
            `;
            autocompleteDropdown.appendChild(itemElement);
        });

        autocompleteDropdown.style.display = 'block';
    }

    // Helper function to format currency
    function formatCurrency(amount) {
        return '₵' + parseFloat(amount).toFixed(2);
    }
});
