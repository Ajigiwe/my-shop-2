<?php
/**
 * includes/shop-section.php
 * Shared Avazonia-style shop listing section.
 * Expects: $section_eyebrow, $section_title, $products, $pagination (page/totalPages/total/hasPrev/hasNext), $empty_msg
 */
$section_title = $section_title ?? 'ALL PRODUCTS';
$empty_msg     = $empty_msg ?? 'No products found.';
$total_items   = $pagination['total'] ?? count($products ?? []);
$base_page_url = $page_base_url ?? 'shop.php';
?>
<section class="shop-content" style="padding: 120px 0 80px;">
    <div class="container">
        <div class="sec-head reveal">
            <div>
                <div class="sec-over"><?= htmlspecialchars($section_eyebrow ?? 'THE DROP') ?></div>
                <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;">
                    <?= $section_title ?>
                </h2>
            </div>
            <div style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; letter-spacing: 0.1em;">
                Showing <?= $total_items ?> items
            </div>
        </div>

        <!-- Product Grid -->
        <div class="products-grid">
            <?php foreach (($products ?? []) as $p): ?>
                <?php require __DIR__ . '/product-card.php'; ?>
            <?php endforeach; ?>

            <?php if (empty($products)): ?>
                <p style="grid-column: 1 / -1; color: var(--mid-gray); font-family: var(--f-body); font-size: 14px; padding: 40px 0; text-align: center;"><?= htmlspecialchars($empty_msg) ?></p>
            <?php endif; ?>
        </div>

        <?php if (($pagination['totalPages'] ?? 1) > 1): ?>
        <div class="shop-pagination">
            <?php
            $queryParams = $_GET;
            unset($queryParams['page']);
            $baseQuery = http_build_query($queryParams);
            $baseUrl = $base_page_url . ($baseQuery ? '?' . $baseQuery : '');
            $sep = $baseQuery ? '&' : '?';
            ?>
            <?php if ($pagination['hasPrev']): ?>
                <a href="<?= $baseUrl . $sep . 'page=' . ($pagination['page'] - 1) ?>" class="page-btn">&laquo; Prev</a>
            <?php endif; ?>

            <?php
            $start = max(1, $pagination['page'] - 2);
            $end = min($pagination['totalPages'], $pagination['page'] + 2);
            if ($start > 1) echo '<span class="page-dots">...</span>';
            for ($i = $start; $i <= $end; $i++):
                $isActive = $i === $pagination['page'];
            ?>
                <a href="<?= $baseUrl . $sep . 'page=' . $i ?>" class="page-btn <?= $isActive ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor;
            if ($end < $pagination['totalPages']) echo '<span class="page-dots">...</span>';
            ?>

            <?php if ($pagination['hasNext']): ?>
                <a href="<?= $baseUrl . $sep . 'page=' . ($pagination['page'] + 1) ?>" class="page-btn">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
