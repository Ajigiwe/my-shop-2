<?php
// Include database connection and start session
require_once 'includes/db.php';
session_start();

// Set page title
$page_title = 'Search Results';

// Get search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_query = htmlspecialchars($search_query);

// Initialize variables
$products = [];
$message = '';

// Search products if query is not empty
if (!empty($search_query)) {
    try {
        // Debug: Log the search query
        error_log("Search Query: " . $search_query);
        
        // Prepare search query with full-text search and LIKE for partial matches
        $sql = "SELECT p.*, c.category_name,
                       MATCH(p.name, p.description) AGAINST(? IN BOOLEAN MODE) as relevance,
                       (p.name LIKE ?) * 2 + (p.description LIKE ?) as partial_match
                FROM products p 
                JOIN categories c ON p.category_id = c.category_id 
                WHERE MATCH(p.name, p.description) AGAINST(? IN BOOLEAN MODE)
                   OR p.name LIKE ? 
                   OR p.description LIKE ?
                   OR c.category_name LIKE ?
                ORDER BY relevance DESC, partial_match DESC, p.name";
                
        // Debug: Log the SQL query
        error_log("SQL Query: " . $sql);
        
        $stmt = $pdo->prepare($sql);
        
        // Prepare search terms for full-text search
        $fulltext_terms = '';
        $terms = preg_split('/\s+/', $search_query);
        foreach ($terms as $term) {
            if (strlen(trim($term)) >= 3) { // Only include terms with 3 or more characters
                $fulltext_terms .= '+' . $term . '* ';
            }
        }
        $fulltext_terms = trim($fulltext_terms);
        
        // Prepare LIKE parameters
        $like_param = "%$search_query%";
        $partial_param = "%" . str_replace(' ', '%', $search_query) . "%";
        
        // Debug: Log the search parameters
        error_log("Fulltext Terms: " . $fulltext_terms);
        error_log("Like Parameter: " . $like_param);
        error_log("Partial Parameter: " . $partial_param);
        
        // Execute query with parameters
        $stmt->execute([
            $fulltext_terms,  // Full-text search
            $partial_param,   // Name LIKE
            $partial_param,   // Description LIKE
            $fulltext_terms,  // Full-text search in WHERE
            $partial_param,   // Name LIKE in WHERE
            $partial_param,   // Description LIKE in WHERE
            $like_param       // Category name LIKE
        ]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the number of results
        error_log("Number of results: " . count($products));
        
        if (empty($products)) {
            $message = "No products found matching '{$search_query}'";
            error_log("No products found for query: " . $search_query);
        }
    } catch(PDOException $e) {
        $error_message = "Search Error: " . $e->getMessage() . "\n";
        $error_message .= "SQL Error Code: " . $e->getCode() . "\n";
        $error_message .= "Search Query: " . $search_query . "\n";
        $error_message .= "Full SQL: " . ($sql ?? 'Not set') . "\n";
        
        error_log($error_message);
        
        // Fallback to simple search if full-text search fails
        try {
            $fallback_sql = "SELECT p.*, c.category_name 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.category_id 
                           WHERE p.name LIKE ? 
                           OR p.description LIKE ?
                           OR c.category_name LIKE ?
                           ORDER BY p.name";
            
            $stmt = $pdo->prepare($fallback_sql);
            $search_param = "%$search_query%";
            $stmt->execute([$search_param, $search_param, $search_param]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($products)) {
                $message = "No products found matching '{$search_query}'";
            }
        } catch(PDOException $e2) {
            $message = "Error performing search. Please try a different search term.";
            error_log("Fallback Search Error: " . $e2->getMessage());
        }
    }
} else {
    $message = "Please enter a search term.";
}

// Include header
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <!-- Search Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Search Results for "<?php echo $search_query; ?>"</h2>
                <div class="text-muted">
                    <?php echo count($products); ?> result<?php echo count($products) != 1 ? 's' : ''; ?> found
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Search Results -->
            <?php if (!empty($products)): ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-5th col-md-4 col-sm-6 mb-4">
                            <div class="card product-card h-100">
                                <a href="product.php?id=<?php echo $product['product_id']; ?>">
                                    <img src="assets/images/<?php echo htmlspecialchars($product['image'] ?? 'placeholder.jpg'); ?>"
                                         class="card-img-top product-image" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title product-title">
                                        <a href="product.php?id=<?php echo $product['product_id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                    <p class="product-price mb-3"><?php echo formatCurrency($product['price']); ?></p>

                                    <div class="mt-auto">
                                        <?php if (($product['stock_quantity'] ?? 0) > 0): ?>
                                            <button class="btn btn-primary w-100 home-add-to-cart-btn"
                                                    data-product-id="<?php echo $product['product_id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-times me-2"></i>Out of Stock
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
