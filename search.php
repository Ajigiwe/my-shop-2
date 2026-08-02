<?php
// Include database connection and start session
require_once 'includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Search Results';

// Get search query (raw for SQL, escaped for display)
$raw_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_query = htmlspecialchars($raw_query);

$products = [];
$message = '';
$image_subquery = "(SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC)
                   FROM product_images WHERE product_id = p.product_id) as all_images";

if (!empty($raw_query)) {
    try {
        $sql = "SELECT p.*, c.category_name, $image_subquery,
                       MATCH(p.name, p.description) AGAINST(? IN BOOLEAN MODE) as relevance,
                       (p.name LIKE ?) * 2 + (p.description LIKE ?) as partial_match
                FROM products p 
                JOIN categories c ON p.category_id = c.category_id 
                WHERE p.status = 'published'
                  AND (MATCH(p.name, p.description) AGAINST(? IN BOOLEAN MODE)
                   OR p.name LIKE ? 
                   OR p.description LIKE ?
                   OR c.category_name LIKE ?)
                ORDER BY relevance DESC, partial_match DESC, p.name";

        $stmt = $pdo->prepare($sql);

        $fulltext_terms = '';
        $terms = preg_split('/\s+/', $raw_query);
        foreach ($terms as $term) {
            if (strlen(trim($term)) >= 3) {
                $fulltext_terms .= '+' . $term . '* ';
            }
        }
        $fulltext_terms = trim($fulltext_terms);

        $like_param = "%$raw_query%";
        $partial_param = "%" . str_replace(' ', '%', $raw_query) . "%";

        $stmt->execute([
            $fulltext_terms,
            $partial_param,
            $partial_param,
            $fulltext_terms,
            $partial_param,
            $partial_param,
            $like_param
        ]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
            $message = "No products found matching '{$search_query}'.";
        }
    } catch(PDOException $e) {
        error_log("Search Error: " . $e->getMessage());
        // Fallback to simple search if full-text search fails
        try {
            $fallback_sql = "SELECT p.*, c.category_name, $image_subquery 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.category_id 
                           WHERE p.status = 'published'
                             AND (p.name LIKE ? 
                             OR p.description LIKE ?
                             OR c.category_name LIKE ?)
                           ORDER BY p.name";

            $stmt = $pdo->prepare($fallback_sql);
            $search_param = "%$raw_query%";
            $stmt->execute([$search_param, $search_param, $search_param]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($products)) {
                $message = "No products found matching '{$search_query}'.";
            }
        } catch(PDOException $e2) {
            $message = "Error performing search. Please try a different search term.";
            error_log("Fallback Search Error: " . $e2->getMessage());
        }
    }
} else {
    $message = "Please enter a search term.";
}

// Wishlist ids for card hearts
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    try {
        $w_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $w_stmt->execute([$_SESSION['user_id']]);
        $user_wishlist = $w_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

include 'includes/header.php';
?>

<main>
    <?php
    $pagination = [
        'page'       => 1,
        'totalPages' => 1,
        'total'      => count($products),
        'hasPrev'    => false,
        'hasNext'    => false,
    ];
    $section_title = !empty($search_query) ? 'Results for &ldquo;' . $search_query . '&rdquo;' : 'Search the drop';
    $section_eyebrow = 'Search';
    $empty_msg = !empty($message) ? $message : 'No products found. Try a different term or browse the full collection.';
    require 'includes/shop-section.php';
    ?>
</main>

<?php include 'includes/footer.php'; ?>
