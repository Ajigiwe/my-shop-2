<?php
// Load environment variables
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

/**
 * Storefront: Home
 * - Welcomes users and highlights featured products
 */

// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = 'Home';

// Categories for navbar/etc are already fetched in header if needed, 
// but we might need some for specific sections.

// Get settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$grid_cols = isset($settings['products_per_row']) ? (int)$settings['products_per_row'] : 4;

// Fetch user's wishlist ids
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {}
}

include 'includes/header.php';
?>

<main>
    <?php
    $hero_style = $settings['homepage_hero_style'] ?? 'carousel';
    
    // Fetch active slides from database once
    try {
        $stmt = $pdo->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC");
        $hero_slides = $stmt->fetchAll();
    } catch (PDOException $e) {
        $hero_slides = [];
    }

    $is_facing_style = in_array($hero_style, ['circular', 'sidebyside', 'stacked', 'orbit']);
    ?>
    
    <?php if ($is_facing_style): ?>
        <!-- GORGEOUS FACING HERO CARDS SECTION -->
        <section class="relative bg-gradient-to-br from-[#f5f7fa] to-[#c3cfe2] pt-2 pb-6 px-4 overflow-hidden border-b border-[#EEEEEE] flex flex-col items-center w-full z-20 -mt-2 md:-mt-4">
            <div class="max-w-[1200px] w-full mx-auto relative mt-0">

                <!-- HERO CAROUSEL WRAPPER -->
                <div class="facing-carousel-wrapper">
                    
                    <!-- TYPE 1: CIRCULAR ROTATE -->
                    <div id="facing-circular" class="facing-carousel" style="display: <?php echo ($hero_style === 'circular') ? 'flex' : 'none'; ?>;">
                        <div class="facing-circular-container w-full h-full relative">
                            <div class="facing-card-container-circular w-full h-full absolute">
                                <?php foreach ($hero_slides as $idx => $slide): 
                                    $emoji = '✨';
                                    $title_lower = strtolower($slide['title_black']);
                                    if (strpos($title_lower, 'phone') !== false || strpos($title_lower, 'electro') !== false) { $emoji = '📱'; }
                                    elseif (strpos($title_lower, 'fashion') !== false || strpos($title_lower, 'cloth') !== false || strpos($title_lower, 'wear') !== false) { $emoji = '👕'; }
                                    elseif (strpos($title_lower, 'watch') !== false || strpos($title_lower, 'accessor') !== false) { $emoji = '⌚'; }
                                    elseif (strpos($title_lower, 'home') !== false || strpos($title_lower, 'living') !== false) { $emoji = '🏠'; }
                                    elseif (strpos($title_lower, 'sport') !== false || strpos($title_lower, 'outdoors') !== false) { $emoji = '⚽'; }
                                    elseif (strpos($title_lower, 'book') !== false) { $emoji = '📚'; }
                                    elseif (strpos($title_lower, 'beauty') !== false || strpos($title_lower, 'cosmetic') !== false) { $emoji = '💄'; }
                                    
                                    $grad_class = 'card-gradient-' . (($idx % 4) + 1);
                                    
                                    // Initial positioning
                                    $pos_class = 'hidden';
                                    if ($idx === 0) $pos_class = 'spotlight';
                                    elseif ($idx === 1) $pos_class = 'right';
                                    elseif ($idx === count($hero_slides) - 1 || ($idx === 2 && count($hero_slides) === 3)) $pos_class = 'left';

                                    $image_src = '';
                                    if (!empty($slide['image_path'])) {
                                        $image_src = (strpos($slide['image_path'], 'assets/') === 0) ? $slide['image_path'] : 'assets/images/' . $slide['image_path'];
                                    }
                                ?>
                                    <div class="facing-card group <?php echo $pos_class; ?> <?php echo $grad_class; ?>" data-slide-index="<?php echo $idx; ?>">
                                        <?php if ($image_src): ?>
                                            <img class="absolute inset-0 w-full h-full object-cover z-0 pointer-events-none transition-transform duration-700 group-hover:scale-110" src="<?php echo htmlspecialchars($image_src); ?>" alt="" />
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-black/20 z-10 pointer-events-none"></div>
                                        <?php endif; ?>
                                        
                                        <div class="relative z-20 w-full h-full flex flex-col items-center justify-center pointer-events-none">
                                            <?php if (!$image_src): ?>
                                                <div class="facing-card-icon pointer-events-auto"><?php echo $emoji; ?></div>
                                            <?php endif; ?>
                                            <h3 class="font-black text-xl mb-2 text-white pointer-events-auto"><?php echo htmlspecialchars($slide['title_black']); ?></h3>
                                            <p class="text-xs opacity-90 mb-4 line-clamp-2 text-white/90 pointer-events-auto"><?php echo htmlspecialchars($slide['description']); ?></p>
                                            <a href="<?php echo htmlspecialchars($slide['button_link'] ?: 'shop.php'); ?>" class="facing-card-cta text-decoration-none pointer-events-auto">
                                                <?php echo htmlspecialchars($slide['button_text'] ?: 'Shop Now'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- TYPE 2: SIDE BY SIDE FLIP -->
                    <div id="facing-sidebyside" class="facing-carousel" style="display: <?php echo ($hero_style === 'sidebyside') ? 'flex' : 'none'; ?>;">
                        <div class="facing-sidebyside-container w-full h-full flex gap-4 items-center justify-center">
                            <div class="facing-card-left flex-1 h-full flex items-center justify-end">
                                <?php foreach ($hero_slides as $idx => $slide): 
                                    $emoji = '✨';
                                    $title_lower = strtolower($slide['title_black']);
                                    if (strpos($title_lower, 'phone') !== false || strpos($title_lower, 'electro') !== false) { $emoji = '📱'; }
                                    elseif (strpos($title_lower, 'fashion') !== false || strpos($title_lower, 'cloth') !== false || strpos($title_lower, 'wear') !== false) { $emoji = '👕'; }
                                    elseif (strpos($title_lower, 'watch') !== false || strpos($title_lower, 'accessor') !== false) { $emoji = '⌚'; }
                                    elseif (strpos($title_lower, 'home') !== false || strpos($title_lower, 'living') !== false) { $emoji = '🏠'; }
                                    elseif (strpos($title_lower, 'sport') !== false || strpos($title_lower, 'outdoors') !== false) { $emoji = '⚽'; }
                                    elseif (strpos($title_lower, 'book') !== false) { $emoji = '📚'; }
                                    elseif (strpos($title_lower, 'beauty') !== false || strpos($title_lower, 'cosmetic') !== false) { $emoji = '💄'; }
                                    
                                    $grad_class = 'card-gradient-' . (($idx % 4) + 1);
                                    $active_class = ($idx === 0) ? 'active-left' : 'inactive';

                                    $image_src = '';
                                    if (!empty($slide['image_path'])) {
                                        $image_src = (strpos($slide['image_path'], 'assets/') === 0) ? $slide['image_path'] : 'assets/images/' . $slide['image_path'];
                                    }
                                ?>
                                    <div class="facing-sidebyside-card group <?php echo $active_class; ?> <?php echo $grad_class; ?>" data-slide-index="<?php echo $idx; ?>">
                                        <?php if ($image_src): ?>
                                            <img class="absolute inset-0 w-full h-full object-cover z-0 pointer-events-none transition-transform duration-700 group-hover:scale-110" src="<?php echo htmlspecialchars($image_src); ?>" alt="" />
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-black/20 z-10 pointer-events-none"></div>
                                        <?php endif; ?>
                                        
                                        <div class="relative z-20 w-full h-full flex flex-col items-center justify-center pointer-events-none">
                                            <?php if (!$image_src): ?>
                                                <div class="facing-card-icon text-4xl mb-3 pointer-events-auto"><?php echo $emoji; ?></div>
                                            <?php endif; ?>
                                            <h3 class="font-black text-lg mb-2 text-white pointer-events-auto"><?php echo htmlspecialchars($slide['title_black']); ?></h3>
                                            <p class="text-xs opacity-90 mb-4 line-clamp-2 text-white/90 pointer-events-auto"><?php echo htmlspecialchars($slide['description']); ?></p>
                                            <a href="<?php echo htmlspecialchars($slide['button_link'] ?: 'shop.php'); ?>" class="facing-card-cta text-decoration-none pointer-events-auto">
                                                <?php echo htmlspecialchars($slide['button_text'] ?: 'Shop Now'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="facing-card-right flex-1 h-full flex items-center justify-start">
                                <?php foreach ($hero_slides as $idx => $slide): 
                                    $emoji = '✨';
                                    $title_lower = strtolower($slide['title_black']);
                                    if (strpos($title_lower, 'phone') !== false || strpos($title_lower, 'electro') !== false) { $emoji = '📱'; }
                                    elseif (strpos($title_lower, 'fashion') !== false || strpos($title_lower, 'cloth') !== false || strpos($title_lower, 'wear') !== false) { $emoji = '👕'; }
                                    elseif (strpos($title_lower, 'watch') !== false || strpos($title_lower, 'accessor') !== false) { $emoji = '⌚'; }
                                    elseif (strpos($title_lower, 'home') !== false || strpos($title_lower, 'living') !== false) { $emoji = '🏠'; }
                                    elseif (strpos($title_lower, 'sport') !== false || strpos($title_lower, 'outdoors') !== false) { $emoji = '⚽'; }
                                    elseif (strpos($title_lower, 'book') !== false) { $emoji = '📚'; }
                                    elseif (strpos($title_lower, 'beauty') !== false || strpos($title_lower, 'cosmetic') !== false) { $emoji = '💄'; }
                                    
                                    $grad_class = 'card-gradient-' . (($idx % 4) + 1);
                                    $active_class = ($idx === 0) ? 'active-right' : 'inactive';

                                    $image_src = '';
                                    if (!empty($slide['image_path'])) {
                                        $image_src = (strpos($slide['image_path'], 'assets/') === 0) ? $slide['image_path'] : 'assets/images/' . $slide['image_path'];
                                    }
                                ?>
                                    <div class="facing-sidebyside-card group <?php echo $active_class; ?> <?php echo $grad_class; ?>" data-slide-index="<?php echo $idx; ?>">
                                        <?php if ($image_src): ?>
                                            <img class="absolute inset-0 w-full h-full object-cover z-0 pointer-events-none transition-transform duration-700 group-hover:scale-110" src="<?php echo htmlspecialchars($image_src); ?>" alt="" />
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-black/20 z-10 pointer-events-none"></div>
                                        <?php endif; ?>
                                        
                                        <div class="relative z-20 w-full h-full flex flex-col items-center justify-center pointer-events-none">
                                            <?php if (!$image_src): ?>
                                                <div class="facing-card-icon text-4xl mb-3 pointer-events-auto"><?php echo $emoji; ?></div>
                                            <?php endif; ?>
                                            <h3 class="font-black text-lg mb-2 text-white pointer-events-auto"><?php echo htmlspecialchars($slide['title_black']); ?></h3>
                                            <p class="text-xs opacity-90 mb-4 line-clamp-2 text-white/90 pointer-events-auto"><?php echo htmlspecialchars($slide['description']); ?></p>
                                            <a href="<?php echo htmlspecialchars($slide['button_link'] ?: 'shop.php'); ?>" class="facing-card-cta text-decoration-none pointer-events-auto">
                                                <?php echo htmlspecialchars($slide['button_text'] ?: 'Shop Now'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- TYPE 3: STACKED CARDS -->
                    <div id="facing-stacked" class="facing-carousel" style="display: <?php echo ($hero_style === 'stacked') ? 'flex' : 'none'; ?>;">
                        <div class="facing-stacked-container w-full h-full flex items-center justify-center">
                            <div class="facing-stacked-cards-inner relative">
                                <?php foreach ($hero_slides as $idx => $slide): 
                                    $emoji = '✨';
                                    $title_lower = strtolower($slide['title_black']);
                                    if (strpos($title_lower, 'phone') !== false || strpos($title_lower, 'electro') !== false) { $emoji = '📱'; }
                                    elseif (strpos($title_lower, 'fashion') !== false || strpos($title_lower, 'cloth') !== false || strpos($title_lower, 'wear') !== false) { $emoji = '👕'; }
                                    elseif (strpos($title_lower, 'watch') !== false || strpos($title_lower, 'accessor') !== false) { $emoji = '⌚'; }
                                    elseif (strpos($title_lower, 'home') !== false || strpos($title_lower, 'living') !== false) { $emoji = '🏠'; }
                                    elseif (strpos($title_lower, 'sport') !== false || strpos($title_lower, 'outdoors') !== false) { $emoji = '⚽'; }
                                    elseif (strpos($title_lower, 'book') !== false) { $emoji = '📚'; }
                                    elseif (strpos($title_lower, 'beauty') !== false || strpos($title_lower, 'cosmetic') !== false) { $emoji = '💄'; }
                                    
                                    $grad_class = 'card-gradient-' . (($idx % 4) + 1);
                                    
                                    // Stack layout calculation
                                    $z_index = 30 - ($idx * 10);
                                    $opacity = 1 - ($idx * 0.3);
                                    $transform = "translate(-50%, -50%) translateY(" . ($idx * 20) . "px) scale(" . (1 - $idx * 0.05) . ") rotateX(" . ($idx * 10) . "deg)";

                                    $image_src = '';
                                    if (!empty($slide['image_path'])) {
                                        $image_src = (strpos($slide['image_path'], 'assets/') === 0) ? $slide['image_path'] : 'assets/images/' . $slide['image_path'];
                                    }
                                ?>
                                    <div class="facing-stacked-card group <?php echo $grad_class; ?>" style="z-index: <?php echo $z_index; ?>; opacity: <?php echo $opacity; ?>; transform: <?php echo $transform; ?>;" data-slide-index="<?php echo $idx; ?>">
                                        <?php if ($image_src): ?>
                                            <img class="absolute inset-0 w-full h-full object-cover z-0 pointer-events-none transition-transform duration-700 group-hover:scale-110" src="<?php echo htmlspecialchars($image_src); ?>" alt="" />
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-black/20 z-10 pointer-events-none"></div>
                                        <?php endif; ?>
                                        
                                        <div class="relative z-20 w-full h-full flex flex-col items-center justify-center pointer-events-none">
                                            <?php if (!$image_src): ?>
                                                <div class="facing-card-icon pointer-events-auto"><?php echo $emoji; ?></div>
                                            <?php endif; ?>
                                            <h3 class="font-black text-xl mb-2 text-white pointer-events-auto"><?php echo htmlspecialchars($slide['title_black']); ?></h3>
                                            <p class="text-xs opacity-90 mb-4 line-clamp-2 text-white/90 pointer-events-auto"><?php echo htmlspecialchars($slide['description']); ?></p>
                                            <a href="<?php echo htmlspecialchars($slide['button_link'] ?: 'shop.php'); ?>" class="facing-card-cta text-decoration-none pointer-events-auto">
                                                <?php echo htmlspecialchars($slide['button_text'] ?: 'Shop Now'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- TYPE 4: ORBIT -->
                    <div id="facing-orbit" class="facing-carousel" style="display: <?php echo ($hero_style === 'orbit') ? 'flex' : 'none'; ?>;">
                        <div class="facing-orbit-container w-full h-full flex items-center justify-center relative">
                            <div class="facing-orbit-center"></div>
                            <?php foreach ($hero_slides as $idx => $slide): 
                                $emoji = '✨';
                                $title_lower = strtolower($slide['title_black']);
                                if (strpos($title_lower, 'phone') !== false || strpos($title_lower, 'electro') !== false) { $emoji = '📱'; }
                                elseif (strpos($title_lower, 'fashion') !== false || strpos($title_lower, 'cloth') !== false || strpos($title_lower, 'wear') !== false) { $emoji = '👕'; }
                                elseif (strpos($title_lower, 'watch') !== false || strpos($title_lower, 'accessor') !== false) { $emoji = '⌚'; }
                                elseif (strpos($title_lower, 'home') !== false || strpos($title_lower, 'living') !== false) { $emoji = '🏠'; }
                                elseif (strpos($title_lower, 'sport') !== false || strpos($title_lower, 'outdoors') !== false) { $emoji = '⚽'; }
                                elseif (strpos($title_lower, 'book') !== false) { $emoji = '📚'; }
                                elseif (strpos($title_lower, 'beauty') !== false || strpos($title_lower, 'cosmetic') !== false) { $emoji = '💄'; }
                                
                                $grad_class = 'card-gradient-' . (($idx % 4) + 1);
                                
                                // Orbit layout calculation
                                $positions = [
                                    0 => ['x' => 0, 'y' => -190, 'scale' => 1, 'z' => 10, 'op' => 1],
                                    1 => ['x' => 200, 'y' => 90, 'scale' => 0.8, 'z' => 5, 'op' => 0.6],
                                    2 => ['x' => -200, 'y' => 90, 'scale' => 0.8, 'z' => 5, 'op' => 0.6],
                                    3 => ['x' => 0, 'y' => 190, 'scale' => 0.6, 'z' => 3, 'op' => 0.4]
                                ];
                                
                                $pos = $positions[$idx % 4] ?? ['x' => 0, 'y' => 240, 'scale' => 0.5, 'z' => 2, 'op' => 0.2];

                                $image_src = '';
                                if (!empty($slide['image_path'])) {
                                    $image_src = (strpos($slide['image_path'], 'assets/') === 0) ? $slide['image_path'] : 'assets/images/' . $slide['image_path'];
                                }
                            ?>
                                <div class="facing-orbit-card group <?php echo $grad_class; ?>" style="transform: translate(<?php echo $pos['x']; ?>px, <?php echo $pos['y']; ?>px) scale(<?php echo $pos['scale']; ?>); z-index: <?php echo $pos['z']; ?>; opacity: <?php echo $pos['op']; ?>;" data-slide-index="<?php echo $idx; ?>">
                                    <?php if ($image_src): ?>
                                        <img class="absolute inset-0 w-full h-full object-cover z-0 pointer-events-none transition-transform duration-700 group-hover:scale-110" src="<?php echo htmlspecialchars($image_src); ?>" alt="" />
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-black/20 z-10 pointer-events-none"></div>
                                    <?php endif; ?>
                                    
                                    <div class="relative z-20 w-full h-full flex flex-col items-center justify-center pointer-events-none">
                                        <?php if (!$image_src): ?>
                                            <div class="facing-card-icon text-4xl mb-3 pointer-events-auto"><?php echo $emoji; ?></div>
                                        <?php endif; ?>
                                        <h3 class="font-black text-lg mb-2 text-white pointer-events-auto"><?php echo htmlspecialchars($slide['title_black']); ?></h3>
                                        <p class="text-xs opacity-90 mb-4 line-clamp-2 text-white/90 pointer-events-auto"><?php echo htmlspecialchars($slide['description']); ?></p>
                                        <a href="<?php echo htmlspecialchars($slide['button_link'] ?: 'shop.php'); ?>" class="facing-card-cta text-decoration-none pointer-events-auto">
                                            <?php echo htmlspecialchars($slide['button_text'] ?: 'Shop Now'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>


            </div>
        </section>
    <?php else: ?>
        <!-- Hero Slider Section -->
        <section class="relative bg-[#F9F9F9] overflow-hidden border-b border-[#EEEEEE] -mt-2 md:-mt-4">
            <div class="swiper heroSwiper pb-0">
                <div class="swiper-wrapper">
                    <?php foreach ($hero_slides as $slide): ?>
                        <div class="swiper-slide overflow-visible pt-0 pb-6 md:pt-0 md:pb-8 bg-transparent px-4">
                            <?php if ($hero_style === 'split'): ?>
                                <!-- SPLIT SCREEN LAYOUT SLIDE -->
                                <div class="max-w-[1200px] mx-auto grid grid-cols-1 lg:grid-cols-2 rounded-[2rem] overflow-hidden bg-white border border-[#EEEEEE] shadow-2xl min-h-[420px] w-full">
                                    <!-- Content Column -->
                                    <div class="flex flex-col justify-center px-6 py-10 md:py-12 lg:p-14 order-2 lg:order-1 text-left w-full">
                                        <?php if ($slide['badge_text']): ?>
                                        <span class="inline-flex items-center gap-1 bg-primary/10 text-primary font-black text-[10px] uppercase tracking-widest px-3 py-1 rounded-full mb-4 w-fit shadow-sm">
                                            <span class="material-symbols-outlined text-[13px]">verified</span> <?php echo htmlspecialchars($slide['badge_text']); ?>
                                        </span>
                                        <?php endif; ?>
                                        
                                        <h2 class="text-[28px] md:text-[36px] lg:text-[44px] font-black leading-[1.1] tracking-tight text-[#1A1A1A] mb-3">
                                            <?php echo htmlspecialchars($slide['title_black']); ?>
                                        </h2>
                                        <?php if ($slide['title_gray']): ?>
                                        <div class="text-[15px] md:text-[17px] font-bold text-[#666666] mb-3">
                                            <?php echo htmlspecialchars($slide['title_gray']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($slide['description']): ?>
                                        <p class="text-[13px] md:text-[14px] text-[#666666] font-medium leading-relaxed mb-6 max-w-lg">
                                            <?php echo htmlspecialchars($slide['description']); ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <?php if ($slide['button_text']): ?>
                                            <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="bg-[#004225] text-white hover:bg-black font-black px-6 py-3 rounded-xl text-[12px] uppercase tracking-widest transition-all duration-300 w-fit shadow-lg shadow-black/10 text-center">
                                                <?php echo htmlspecialchars($slide['button_text']); ?>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($slide['secondary_button_text']): ?>
                                            <a href="<?php echo htmlspecialchars($slide['secondary_button_link']); ?>" class="bg-[#F9F9F9] border-2 border-[#EEEEEE] text-[#1A1A1A] font-bold px-6 py-3 rounded-xl hover:bg-white transition-colors text-[12px] text-center">
                                                <?php echo htmlspecialchars($slide['secondary_button_text']); ?>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Graphic / Image Column -->
                                    <div class="flex items-center justify-center p-8 order-1 lg:order-2 min-h-[250px] lg:min-h-[420px] relative w-full" style="background-color: <?php echo $slide['card_bg'] ?? '#004225'; ?>;">
                                        <div class="absolute inset-0 bg-white/5 opacity-40 mix-blend-overlay"></div>
                                        <div class="absolute w-40 h-40 bg-white/10 rounded-full blur-2xl top-5 right-5"></div>
                                        <div class="absolute w-48 h-48 bg-black/10 rounded-full blur-3xl bottom-5 left-5"></div>
                                        
                                        <!-- Product frame -->
                                        <div class="relative max-w-[280px] md:max-w-[320px] w-full aspect-square bg-white rounded-[2rem] p-5 shadow-2xl hover:scale-105 transition-transform duration-700">
                                            <img class="w-full h-full object-contain rounded-xl" src="<?php echo (strpos($slide['image_path'], 'assets/') === 0) ? $slide['image_path'] : 'assets/images/' . $slide['image_path']; ?>" alt="Slide Image" />
                                        </div>
                                    </div>
                                </div>
                                
                            <?php elseif ($hero_style === 'gradient'): ?>
                                <!-- BRAND GRADIENT LAYOUT SLIDE -->
                                <div class="max-w-[1200px] mx-auto rounded-[2rem] overflow-hidden bg-gradient-hero py-12 md:py-16 px-6 text-center relative z-10 text-white shadow-2xl min-h-[420px] flex flex-col justify-center items-center w-full">
                                    <div class="floating-shapes">
                                        <div class="bg-white/5 w-64 h-64 rounded-full absolute -top-16 -left-16 blur-2xl animate-float"></div>
                                        <div class="bg-white/5 w-96 h-96 rounded-full absolute -bottom-32 -right-32 blur-3xl animate-float-reverse"></div>
                                    </div>
                                    
                                    <div class="relative z-10 max-w-3xl w-full">
                                        <?php if ($slide['badge_text']): ?>
                                        <span class="inline-flex items-center gap-1.5 bg-white/20 backdrop-blur-md px-3.5 py-1 rounded-full text-[10px] font-black tracking-widest uppercase mb-4 border border-white/10 shadow-sm">
                                            <span class="material-symbols-outlined text-[13px] animate-pulse">campaign</span> <?php echo htmlspecialchars($slide['badge_text']); ?>
                                        </span>
                                        <?php endif; ?>
                                        
                                        <h2 class="text-[30px] md:text-[44px] lg:text-[52px] font-black leading-[1.1] tracking-tight mb-3 text-white">
                                            <?php echo htmlspecialchars($slide['title_black']); ?>
                                        </h2>
                                        <?php if ($slide['title_gray']): ?>
                                        <div class="text-[15px] md:text-[17px] font-bold text-white/80 mb-3">
                                            <?php echo htmlspecialchars($slide['title_gray']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($slide['description']): ?>
                                        <p class="text-[13px] md:text-[14px] text-white/95 font-medium max-w-xl mx-auto leading-relaxed mb-6">
                                            <?php echo htmlspecialchars($slide['description']); ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                            <?php if ($slide['button_text']): ?>
                                            <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="bg-white text-[#004225] hover:bg-[#A3E635] hover:text-[#004225] font-black px-8 py-3 rounded-xl text-[12px] uppercase tracking-widest transition-all duration-300 inline-block shadow-2xl hover:scale-105 text-center">
                                                <?php echo htmlspecialchars($slide['button_text']); ?>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($slide['secondary_button_text']): ?>
                                            <a href="<?php echo htmlspecialchars($slide['secondary_button_link']); ?>" class="bg-white/10 border border-white/20 text-white font-bold px-8 py-3 rounded-xl hover:bg-white/20 transition-colors text-[12px] text-center">
                                                <?php echo htmlspecialchars($slide['secondary_button_text']); ?>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <!-- CAROUSEL HERO (DEFAULT) -->
                                <div class="hero-card max-w-[1200px] mx-auto rounded-[2rem] shadow-2xl border border-[#EEEEEE] overflow-hidden p-6 md:p-10 lg:p-14 flex flex-col lg:flex-row items-center gap-10 lg:gap-16 transition-colors duration-300 hover:border-primary/10 w-full" style="background-color: <?php echo $slide['card_bg'] ?? '#FFFFFF'; ?>; color: <?php echo $slide['text_color'] ?? '#1A1A1A'; ?>;">
                                     <div class="flex-1 text-center lg:text-left order-2 lg:order-1">
                                         <?php if ($slide['badge_text']): ?>
                                        <span class="inline-block bg-white/50 backdrop-blur-sm border border-black/5 text-inherit text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest mb-4 shadow-sm">
                                             <?php echo htmlspecialchars($slide['badge_text']); ?>
                                         </span>
                                         <?php endif; ?>
                                        <h1 class="text-[28px] md:text-[36px] lg:text-[42px] font-black leading-[1.1] mb-2 tracking-tighter" style="color: inherit;">
                                             <?php echo htmlspecialchars($slide['title_black']); ?>
                                         </h1>
                                        <?php if ($slide['title_gray']): ?>
                                         <div class="hero-subtitle text-[16px] md:text-[18px] font-bold text-[#666666] mb-4">
                                             <?php echo htmlspecialchars($slide['title_gray']); ?>
                                         </div>
                                         <?php endif; ?>
                                         <?php if ($slide['description']): ?>
                                         <p class="hero-desc text-[13px] md:text-[15px] text-[#666666] font-medium leading-relaxed mb-5 max-w-lg mx-auto lg:mx-0">
                                             <?php echo htmlspecialchars($slide['description']); ?>
                                         </p>
                                         <?php endif; ?>
                                         <div class="hero-buttons flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                                             <?php if ($slide['button_text']): ?>
                                             <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="bg-primary text-white font-bold px-6 py-2.5 rounded-full hover:scale-105 transition-transform shadow-xl hover:shadow-primary/10 text-[12px]">
                                                 <?php echo htmlspecialchars($slide['button_text']); ?>
                                             </a>
                                             <?php endif; ?>
                                             <?php if ($slide['secondary_button_text']): ?>
                                             <a href="<?php echo htmlspecialchars($slide['secondary_button_link']); ?>" class="bg-[#F9F9F9] border-2 border-[#EEEEEE] text-[#1A1A1A] font-bold px-6 py-2.5 rounded-full hover:bg-white transition-colors text-[12px]">
                                                 <?php echo htmlspecialchars($slide['secondary_button_text']); ?>
                                             </a>
                                             <?php endif; ?>
                                         </div>
                                     </div>
                                     <div class="hero-image-wrapper flex-1 order-1 lg:order-2 w-full max-w-[450px]">
                                        <div class="relative group">
                                            <div class="absolute -inset-3 bg-[#EEEEEE] rounded-[1.5rem] blur-2xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
                                            <img class="relative w-full aspect-[4/3] object-cover rounded-[1rem] shadow-2xl transition-transform duration-700 group-hover:scale-[1.02]" 
                                                 src="<?php echo (strpos($slide['image_path'], 'assets/') === 0) ? $slide['image_path'] : 'assets/images/' . $slide['image_path']; ?>" 
                                                 alt="Hero Image" />
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Navigation Buttons -->
                <div class="swiper-button-prev-custom absolute left-8 top-1/2 -translate-y-1/2 w-14 h-14 bg-white rounded-full shadow-2xl flex items-center justify-center cursor-pointer z-40 transition-all hover:scale-110 active:scale-95 border border-[#EEEEEE]">
                    <span class="material-symbols-outlined text-[#1A1A1A] text-[24px]">arrow_back_ios_new</span>
                </div>
                <div class="swiper-button-next-custom absolute right-8 top-1/2 -translate-y-1/2 w-14 h-14 bg-white rounded-full shadow-2xl flex items-center justify-center cursor-pointer z-40 transition-all hover:scale-110 active:scale-95 border border-[#EEEEEE]">
                    <span class="material-symbols-outlined text-[#1A1A1A] text-[24px]">arrow_forward_ios</span>
                </div>

            </div>
        </section>
    <?php endif; ?>

    <style>
        /* Animated Gradient Hero styling */
        .bg-gradient-hero {
            background: linear-gradient(-45deg, #004225, #0D5C34, #1E3F2E, #2d5a27);
            background-size: 400% 400%;
            animation: gradient-shift 15s ease infinite;
        }
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .animate-float {
            animation: float-shape 6s ease-in-out infinite;
        }
        .animate-float-reverse {
            animation: float-shape 8s ease-in-out infinite reverse;
        }
        @keyframes float-shape {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        .heroSwiper {
            opacity: 0;
            transition: opacity 0.5s;
            overflow: visible !important;
        }
        .heroSwiper.swiper-initialized {
            opacity: 1;
        }
        .heroSwiper .swiper-pagination-bullet {
            width: 40px;
            height: 4px;
            border-radius: 2px;
            background: #EEEEEE;
            opacity: 1;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .heroSwiper .swiper-pagination-bullet-active {
            background: <?php echo $primary_color; ?>;
            width: 60px;
        }
        
        .heroSwiper .swiper-slide {
            transition: all 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-card {
            width: 100%;
            height: 100%;
            display: flex;
            backface-visibility: hidden;
            will-change: transform, opacity;
        }

        /* Content Reveal Animations */
        .swiper-slide-active h1,
        .swiper-slide-active p,
        .swiper-slide-active .flex,
        .swiper-slide-active img {
            animation: slideUpFade 1s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        }
        
        .swiper-slide-active h1 { animation-delay: 0.1s; opacity: 0; }
        .swiper-slide-active p { animation-delay: 0.2s; opacity: 0; }
        .swiper-slide-active .flex { animation-delay: 0.3s; opacity: 0; }
        .swiper-slide-active img { animation: premiumScale 1.5s cubic-bezier(0.23, 1, 0.32, 1) forwards; }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes premiumScale {
            from { opacity: 0; transform: scale(1.1); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Custom Navigation Styling */
        .swiper-button-prev-custom, .swiper-button-next-custom {
            opacity: 0.8;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .swiper-button-prev-custom:hover, .swiper-button-next-custom:hover {
            opacity: 1;
            transform: translateY(-50%) scale(1.15);
            background-color: <?php echo $primary_color; ?>;
            color: white;
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .swiper-button-prev-custom:hover span, .swiper-button-next-custom:hover span {
            color: white;
        }
        
        @media (min-width: 1025px) {
            .heroSwiper .swiper-slide {
                width: 100% !important;
                opacity: 1 !important;
                visibility: visible !important;
            }
        }
        
        @media (max-width: 1024px) {
            .heroSwiper {
                padding: 0 0 20px 0;
            }
            .heroSwiper .swiper-slide {
                opacity: 1 !important;
                visibility: visible !important;
            }
            .swiper-button-prev-custom, .swiper-button-next-custom {
                width: 48px !important;
                height: 48px !important;
                left: 12px !important;
                right: auto !important;
            }
            .swiper-button-next-custom {
                right: 12px !important;
                left: auto !important;
            }
        }

        @media (max-width: 640px) {
            .heroSwiper {
                padding: 0 0 10px 0 !important;
            }
            .heroSwiper .swiper-slide {
                padding: 0 8px !important;
            }
            .hero-card {
                padding: 16px 20px !important;
                border-radius: 1.25rem !important;
                gap: 16px !important;
            }
            .hero-card h1 {
                font-size: 20px !important;
                margin-bottom: 4px !important;
            }
            .hero-subtitle {
                font-size: 13px !important;
                margin-bottom: 8px !important;
            }
            .hero-desc {
                font-size: 11px !important;
                margin-bottom: 12px !important;
                line-height: 1.4 !important;
            }
            .hero-buttons {
                gap: 8px !important;
            }
            .hero-buttons a {
                padding: 8px 16px !important;
                font-size: 10px !important;
            }
            .hero-image-wrapper {
                max-width: 180px !important;
                margin: 0 auto;
            }
            .hero-image-wrapper img {
                border-radius: 0.75rem !important;
            }
            /* Hide navigation arrows on mobile to prevent overlap */
            .swiper-button-prev-custom, .swiper-button-next-custom {
                display: none !important;
            }
        }

        /* ========== FACING HERO DECK LAYOUTS ========== */
        .facing-carousel-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .facing-carousel {
            position: relative;
            width: 100%;
            height: 480px;
            perspective: 1000px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .facing-carousel {
                height: 400px;
            }
        }

        /* TYPE 1: CIRCULAR ROTATE */
        .facing-circular-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .facing-card-container-circular {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .facing-card, .facing-sidebyside-card, .facing-stacked-card, .facing-orbit-card {
            overflow: hidden;
        }

        .facing-card {
            position: absolute;
            width: 320px;
            height: 400px;
            border-radius: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 30px;
            cursor: pointer;
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            transform-origin: center;
        }

        @media (max-width: 768px) {
            .facing-card {
                width: 260px;
                height: 330px;
                padding: 20px;
            }
        }

        .facing-card.spotlight {
            z-index: 10;
            transform: scale(1) translateX(0) rotateY(0deg);
            opacity: 1;
        }

        .facing-card.left {
            z-index: 5;
            transform: scale(0.75) translateX(-240px) rotateY(30deg);
            opacity: 0.6;
            pointer-events: none;
        }

        .facing-card.right {
            z-index: 5;
            transform: scale(0.75) translateX(240px) rotateY(-30deg);
            opacity: 0.6;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .facing-card.left {
                transform: scale(0.75) translateX(-160px) rotateY(25deg);
            }
            .facing-card.right {
                transform: scale(0.75) translateX(160px) rotateY(-25deg);
            }
        }

        .facing-card.hidden {
            z-index: 1;
            transform: scale(0.5) rotateY(90deg);
            opacity: 0;
            pointer-events: none;
        }

        .card-gradient-1 {
            background: linear-gradient(135deg, #004225 0%, #082F1D 100%);
        }

        .card-gradient-2 {
            background: linear-gradient(135deg, #002C18 0%, #00170C 100%);
        }

        .card-gradient-3 {
            background: linear-gradient(135deg, #10B981 0%, #047857 100%);
        }

        .card-gradient-4 {
            background: linear-gradient(135deg, #6EE7B7 0%, #34D399 100%);
        }

        .facing-card-icon, .facing-card-img-container {
            font-size: 56px;
            margin-bottom: 20px;
            animation: facingFloat 3s ease-in-out infinite;
        }

        @keyframes facingFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .facing-card h3, .facing-sidebyside-card h3, .facing-stacked-card h3, .facing-orbit-card h3 {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .facing-card p, .facing-sidebyside-card p, .facing-stacked-card p, .facing-orbit-card p {
            font-weight: 500;
            line-height: 1.5;
        }

        .facing-card-cta {
            background: rgba(255,255,255,0.15);
            color: white !important;
            border: 2px solid rgba(255,255,255,0.8);
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 900;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .facing-card-cta:hover {
            background: white;
            color: #1A1A1A !important;
            transform: scale(1.05);
        }

        /* TYPE 2: SIDE BY SIDE FLIP */
        .facing-sidebyside-container {
            perspective: 1000px;
        }

        .facing-card-left, .facing-card-right {
            position: relative;
            height: 100%;
            perspective: 1000px;
        }

        .facing-sidebyside-card {
            position: absolute;
            width: 290px;
            height: 370px;
            border-radius: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .facing-sidebyside-card {
                width: 180px;
                height: 260px;
                padding: 18px;
            }
        }

        .facing-sidebyside-card.inactive {
            transform: rotateY(90deg) scale(0.8);
            opacity: 0;
            pointer-events: none;
        }

        .facing-sidebyside-card.active-left {
            transform: rotateY(0deg) scale(1);
            opacity: 1;
            z-index: 10;
        }

        .facing-sidebyside-card.active-right {
            transform: rotateY(0deg) scale(1);
            opacity: 1;
            z-index: 10;
        }

        /* TYPE 3: STACKED CARDS */
        .facing-stacked-cards-inner {
            width: 340px;
            height: 400px;
            perspective: 1200px;
        }

        @media (max-width: 768px) {
            .facing-stacked-cards-inner {
                width: 270px;
                height: 330px;
            }
        }

        .facing-stacked-card {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            transition: all 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            left: 50%;
            top: 50%;
            transform-origin: center;
        }

        /* TYPE 4: ORBIT */
        .facing-orbit-center {
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(0, 66, 37, 0.05);
            border: 2px dashed rgba(0, 66, 37, 0.15);
            z-index: 1;
        }

        .facing-orbit-card {
            position: absolute;
            width: 270px;
            height: 340px;
            border-radius: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 28px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            z-index: 5;
        }

        @media (max-width: 768px) {
            .facing-orbit-card {
                width: 200px;
                height: 260px;
                padding: 18px;
            }
        }

        /* CONTROLS */
        .facing-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 24px;
        }

        .facing-control-btn {
            background: white;
            border: 2px solid #004225;
            color: #004225;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-weight: 900;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .facing-control-btn:hover {
            background: #004225;
            color: white;
        }

        .facing-control-btn:active {
            transform: scale(0.9);
        }

        .facing-control-dots {
            display: flex;
            gap: 8px;
        }

        .facing-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(0, 66, 37, 0.2);
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .facing-dot.active {
            background: #004225;
            width: 24px;
            border-radius: 6px;
        }
    </style>



    <!-- Top Categories Section -->
    <section class="bg-[#F9F9F9]">
        <div class="max-w-[1400px] mx-auto px-6 py-6">
            <h2 class="text-[11px] font-black text-[#888888] mb-8 uppercase tracking-[0.2em] px-2 text-center">Explore Categories</h2>
            <div class="grid grid-cols-3 md:flex md:items-center md:justify-center gap-4 md:gap-16 px-2">
                <?php 
                $category_icons = [
                    'Smart TV' => 'tv',
                    'Speaker' => 'speaker_group',
                    'Tablets' => 'tablet_mac',
                    'Airpods' => 'headphones',
                    'Smartwatches' => 'watch',
                    'Smart Phones' => 'smartphone',
                    'Headphones' => 'headset',
                    'Laptops' => 'laptop_mac',
                    'Bluetooth' => 'bluetooth'
                ];
                $all_categories = [];
                try {
                    $stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as item_count FROM categories c LIMIT 10");
                    $all_categories = $stmt->fetchAll();
                } catch(PDOException $e) {}
                
                foreach ($all_categories as $cat): 
                    $image_path = !empty($cat['image']) ? 'assets/images/categories/' . $cat['image'] : null;
                    $icon = $category_icons[$cat['category_name']] ?? 'category';
                ?>
                    <a href="shop.php?category=<?php echo urlencode($cat['category_name']); ?>" class="flex flex-col items-center group min-w-[90px]">
                        <div class="w-12 h-12 flex items-center justify-center mb-2 group-hover:scale-110 transition-all duration-500 relative">
                            <?php if ($image_path && file_exists($image_path)): ?>
                                <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($cat['category_name']); ?>" class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-110">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center group-hover:text-primary transition-all duration-300">
                                    <span class="material-symbols-outlined text-[28px]"><?php echo $icon; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="text-[11px] font-black text-[#1A1A1A] text-center uppercase tracking-tighter"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                        <span class="text-[9px] text-[#888888] font-bold mt-0.5"><?php echo $cat['item_count']; ?> Items</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Flash Sale Section -->
    <?php
    $flash_products = [];
    $flash_enabled = $settings['flash_sale_enabled'] ?? '1';
    $flash_remaining = 0;

    if ($flash_enabled === '1') {
        $flash_end = $settings['flash_sale_end_time'] ?? '';
        if (!empty($flash_end)) {
            $flash_remaining = strtotime($flash_end) - time();
        }
        
        // Show only if not expired (or if no end time set, default to active)
        if (empty($flash_end) || $flash_remaining > 0) {
            $flash_pids_str = $settings['flash_sale_product_ids'] ?? '';
            $flash_pids = !empty($flash_pids_str) ? array_map('intval', array_filter(explode(',', $flash_pids_str))) : [];
            
            try {
                if (!empty($flash_pids)) {
                    $placeholders = implode(',', array_fill(0, count($flash_pids), '?'));
                    $stmt = $pdo->prepare("SELECT p.*, 
                                        (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC) 
                                         FROM product_images 
                                         WHERE product_id = p.product_id) as all_images 
                                        FROM products p 
                                        WHERE p.product_id IN ($placeholders)");
                    $stmt->execute($flash_pids);
                    $flash_products = $stmt->fetchAll();
                } else {
                    $stmt = $pdo->query("SELECT p.*, 
                                        (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC) 
                                         FROM product_images 
                                         WHERE product_id = p.product_id) as all_images 
                                        FROM products p 
                                        WHERE p.original_price IS NOT NULL AND p.original_price > p.price 
                                        LIMIT 6");
                    $flash_products = $stmt->fetchAll();
                }
            } catch(PDOException $e) {}
        }
    }

    if (!empty($flash_products)):
    ?>
    <section class="bg-white py-xl border-b border-[#EEEEEE]">
        <div class="max-w-[1400px] mx-auto px-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1 bg-[#FF3B30] text-white font-black text-[11px] uppercase tracking-widest px-3 py-1 rounded-full">
                        <span class="material-symbols-outlined text-[14px] animate-pulse">bolt</span> Flash Sale
                    </span>
                    <div class="text-[12px] font-bold text-[#888888]">
                        Ends in <span id="flash-timer" data-remaining="<?php echo $flash_remaining; ?>" class="font-black text-[#FF3B30] tracking-wider bg-red-50 px-2 py-0.5 rounded">--:--:--</span>
                    </div>
                </div>
                <a href="shop.php?sale=1" class="text-[12px] font-black text-[#1A1A1A] uppercase tracking-wider hover:underline flex items-center gap-1">
                    View All Sales <span class="material-symbols-outlined text-[16px]">east</span>
                </a>
            </div>

            <!-- Horizontal Swipeable Container -->
            <div class="flex gap-6 overflow-x-auto pb-4 scrollbar-none snap-x snap-mandatory">
                <?php foreach ($flash_products as $index => $prod): 
                    $images = !empty($prod['all_images']) ? explode(',', $prod['all_images']) : [];
                    $img_src = !empty($images[0]) ? 'assets/images/' . $images[0] : ($prod['image'] ?? 'placeholder.jpg');
                    $discount = round((($prod['original_price'] - $prod['price']) / $prod['original_price']) * 100);
                    
                    // Progressive stock indicators for urgency
                    $stock_remaining = [3, 5, 2, 4, 7, 1][$index % 6];
                    $percent_sold = round((10 - $stock_remaining) * 10);
                ?>
                    <div class="flex-shrink-0 w-[290px] sm:w-[320px] bg-[#F9F9F9] rounded-[2rem] p-4 border border-[#EEEEEE] flex gap-4 snap-start hover:shadow-lg transition-all duration-300 relative group">
                        <!-- Discount Badge -->
                        <span class="absolute top-3 left-3 bg-[#FF3B30] text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full z-10">-<?php echo $discount; ?>%</span>
                        
                        <!-- Image Column -->
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-[1.5rem] bg-white overflow-hidden p-2 flex-shrink-0 relative">
                            <a href="product.php?id=<?php echo $prod['product_id']; ?>" class="block w-full h-full">
                                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500" />
                            </a>
                        </div>
                        
                        <!-- Details Column -->
                        <div class="flex flex-col justify-between flex-1 min-w-0">
                            <div>
                                <h4 class="text-[12px] sm:text-[13px] font-black text-[#1A1A1A] truncate tracking-tight mb-1">
                                    <a href="product.php?id=<?php echo $prod['product_id']; ?>" class="hover:text-primary">
                                        <?php echo htmlspecialchars($prod['name']); ?>
                                    </a>
                                </h4>
                                
                                <?php if (isset($prod['review_count']) && $prod['review_count'] > 0): ?>
                                    <div class="flex items-center gap-0.5 mb-2 scale-75 origin-left">
                                        <div class="flex text-[#FFB800]">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <span class="material-symbols-outlined text-[16px] <?php echo $i <= round($prod['average_rating'] ?? 0) ? 'fill-1' : ''; ?>">star</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-baseline mb-2 flex-wrap min-w-0">
                                    <span class="text-[16px] font-black text-[#FF3B30] inline-block mr-2 whitespace-nowrap"><?php echo formatCurrency($prod['price']); ?></span>
                                    <span class="text-[11px] text-[#888888] line-through font-bold inline-block whitespace-nowrap"><?php echo formatCurrency($prod['original_price']); ?></span>
                                </div>
                            </div>

                            <!-- Urgency Progress Bar -->
                            <div>
                                <div class="flex items-center justify-between text-[9px] font-bold text-[#888888] mb-1">
                                    <span>Only <?php echo $stock_remaining; ?> left in stock</span>
                                    <span><?php echo $percent_sold; ?>% sold</span>
                                </div>
                                <div class="w-full h-1 bg-[#EEEEEE] rounded-full overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#FF3B30] to-orange-500 h-full rounded-full" style="width: <?php echo $percent_sold; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    $promos = [];
    try {
        $stmt = $pdo->query("SELECT pc.*, p.name as prod_name, p.price as prod_price, p.image as prod_image 
                            FROM promo_cards pc 
                            LEFT JOIN products p ON pc.product_id = p.product_id 
                            WHERE pc.is_active = 1 
                            ORDER BY pc.display_order ASC LIMIT 6");
        $promos = $stmt->fetchAll();
    } catch(PDOException $e) {}

    if (!empty($promos)):
    ?>
    <!-- Featured Grid Section -->
    <section class="max-w-[1400px] mx-auto px-md py-xl">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            foreach ($promos as $promo):
                // Use product data if linked, otherwise use promo data
                $title = !empty($promo['title']) ? $promo['title'] : ($promo['prod_name'] ?? '');
                $price = !empty($promo['price_text']) ? $promo['price_text'] : (isset($promo['prod_price']) ? formatCurrency($promo['prod_price']) : '');
                $link = !empty($promo['product_id']) ? 'product.php?id='.$promo['product_id'] : $promo['button_link'];
                $img = !empty($promo['prod_image']) ? 'assets/images/'.$promo['prod_image'] : ((strpos($promo['image_path'], 'assets/') === 0) ? $promo['image_path'] : 'assets/images/' . $promo['image_path']);
            ?>
                <div class="relative group rounded-[2.5rem] p-7 md:p-8 overflow-hidden min-h-[240px] flex flex-col justify-between transition-all duration-500 hover:shadow-2xl hover:shadow-black/5" style="background-color: <?php echo $promo['card_bg']; ?>;">
                    <!-- Subtle Glow Effect -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/20 blur-3xl rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-700"></div>
                    
                    <div class="relative z-10">
                        <?php if ($promo['badge_text']): ?>
                            <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/40 backdrop-blur-md border border-white/20 mb-4 transition-transform group-hover:translate-x-1">
                                <span class="text-[9px] font-black uppercase tracking-[0.15em]" style="color: <?php echo $promo['badge_color']; ?>;"><?php echo htmlspecialchars($promo['badge_text']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="text-[22px] md:text-[24px] font-black text-[#1A1A1A] leading-[1.1] tracking-tight mb-2 max-w-[180px]" style="color: <?php echo $promo['text_color']; ?>;">
                             <?php echo htmlspecialchars($title); ?>
                        </h3>
                        
                        <div class="flex items-center gap-2 mb-6">
                            <span class="text-[13px] font-bold opacity-60" style="color: <?php echo $promo['text_color']; ?>;"><?php echo htmlspecialchars($promo['subtitle']); ?></span>
                            <span class="text-[16px] font-black" style="color: <?php echo $promo['text_color']; ?>;"><?php echo htmlspecialchars($price); ?></span>
                        </div>
                    </div>

                    <div class="relative z-10 mt-auto">
                        <?php if ($promo['is_button']): ?>
                            <a href="<?php echo htmlspecialchars($link); ?>" class="inline-flex items-center gap-2 bg-[#1A1A1A] text-white font-bold px-6 py-3 rounded-full text-[12px] hover:scale-105 active:scale-95 transition-all shadow-lg shadow-black/10">
                                <?php echo htmlspecialchars($promo['button_text']); ?>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($link); ?>" class="group/link inline-flex items-center gap-1.5 text-[13px] font-black transition-all" style="color: <?php echo $promo['text_color']; ?>;">
                                <?php echo htmlspecialchars($promo['button_text']); ?>
                                <span class="material-symbols-outlined text-[18px] group-hover/link:translate-x-1 transition-transform">east</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Large Overlapping Image -->
                    <div class="absolute right-[-15px] bottom-[-15px] w-[160px] md:w-[180px] h-auto pointer-events-none transition-all duration-700 ease-out group-hover:scale-110 group-hover:-translate-x-2 group-hover:-translate-y-2">
                        <div class="relative">
                            <!-- Image Shadow Halo -->
                            <div class="absolute inset-0 bg-black/5 blur-2xl rounded-full scale-75 group-hover:scale-100 transition-transform"></div>
                            <img class="relative z-10 w-full h-full object-contain" src="<?php echo $img; ?>" alt="" />
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Trending Now: Mixed Grid -->
    <section class="bg-surface-container-low py-xl">
        <div class="max-w-container-max mx-auto px-md">
            <div class="flex items-center justify-between mb-lg">
                <h2 class="font-headline-lg text-headline-lg text-on-background">Trending Now</h2>
                <a href="shop.php" class="text-[#1A1A1A] font-label-lg flex items-center gap-xs hover:underline">View All <span class="material-symbols-outlined">chevron_right</span></a>
            </div>
            <?php
            // Fetch trending products with multiple images
            $trending_products = [];
            try {
                $stmt = $pdo->query("SELECT p.*, c.category_name, 
                                    (SELECT GROUP_CONCAT(image_path ORDER BY is_primary DESC, image_id ASC) 
                                     FROM product_images 
                                     WHERE product_id = p.product_id) as all_images 
                                    FROM products p 
                                    JOIN categories c ON p.category_id = c.category_id 
                                    ORDER BY p.created_at DESC LIMIT 12");
                $trending_products = $stmt->fetchAll();
            } catch(PDOException $e) {
                error_log("Error fetching trending products: " . $e->getMessage());
            }
            ?>
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-<?php echo $grid_cols; ?> gap-3 sm:gap-4 md:gap-6">
                <?php if (empty($trending_products)): ?>
                    <p class="col-span-full text-center py-lg text-on-surface-variant font-body-lg">No products trending at the moment.</p>
                <?php else: ?>
                    <?php foreach ($trending_products as $product): 
                        $images = !empty($product['all_images']) ? explode(',', $product['all_images']) : [];
                        $main_img = !empty($images[0]) ? $images[0] : ($product['image'] ?? 'placeholder.jpg');
                        $hover_img = !empty($images[1]) ? $images[1] : null;
                    ?>
                        <div class="bg-white rounded-[1.5rem] p-3 border border-[#EEEEEE] shadow-sm hover:shadow-xl transition-all group relative flex flex-col justify-between h-full">
                            <!-- Image Section -->
                            <div class="relative aspect-square rounded-[1rem] overflow-hidden bg-[#F9F9F9] mb-4">
                                <a href="product.php?id=<?php echo $product['product_id']; ?>" class="block w-full h-full relative">
                                    <img class="w-full h-full object-contain p-4 transition-all duration-700 <?php echo $hover_img ? 'group-hover:opacity-0' : 'group-hover:scale-110'; ?>" 
                                         src="assets/images/<?php echo htmlspecialchars($main_img); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                    <?php if ($hover_img): ?>
                                        <img class="absolute inset-0 w-full h-full object-contain p-4 opacity-0 group-hover:opacity-100 scale-110 group-hover:scale-100 transition-all duration-700" 
                                             src="assets/images/<?php echo htmlspecialchars($hover_img); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Floating Actions -->
                                <div class="absolute left-2 top-2 flex flex-col gap-2 translate-x-0 lg:translate-x-[-3rem] lg:group-hover:translate-x-0 transition-transform duration-500 z-20">
                                    <?php 
                                    $in_wishlist = in_array($product['product_id'], $user_wishlist);
                                    ?>
                                    <button class="w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center <?php echo $in_wishlist ? 'text-red-500' : 'text-[#1A1A1A]'; ?> hover:bg-red-500 hover:text-white transition-colors wishlist-btn"
                                            data-product-id="<?php echo $product['product_id']; ?>">
                                        <span class="material-symbols-outlined text-[18px] <?php echo $in_wishlist ? 'fill-1' : ''; ?>">favorite</span>
                                    </button>
                                </div>

                                <?php if (($product['original_price'] ?? 0) > $product['price']): ?>
                                    <div class="absolute right-0 top-0 z-30 pointer-events-none">
                                        <div class="bg-[#FF3B30] text-white text-[9px] font-black px-2 py-1 rounded-bl-xl shadow-md uppercase tracking-tighter">Sale</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Info Section -->
                            <div class="px-1 flex-1 flex flex-col">
                                <h3 class="text-[13px] font-black text-[#1A1A1A] mb-1 truncate leading-tight">
                                    <a href="product.php?id=<?php echo $product['product_id']; ?>" class="hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                
                                <!-- Rating -->
                                <?php if (isset($product['review_count']) && $product['review_count'] > 0): ?>
                                <div class="flex items-center gap-1 mb-2">
                                    <div class="flex text-[#FFB800] scale-[0.7] origin-left">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <span class="material-symbols-outlined text-[16px] <?php echo $i <= round($product['average_rating'] ?? 0) ? 'fill-1' : ''; ?>">star</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-[9px] md:text-[10px] font-bold text-[#888888] -ml-2">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                                </div>
                                <?php endif; ?>

                                <!-- Price & Cart Row -->
                                <div class="flex items-center justify-between mt-auto pt-2">
                                    <div class="flex flex-col min-w-0">
                                        <?php 
                                        $original_price = $product['original_price'] ?? 0;
                                        $discount_percentage = 0;
                                        if ($original_price > $product['price']) {
                                            $discount_percentage = round((($original_price - $product['price']) / $original_price) * 100);
                                        }
                                        ?>
                                        <div class="flex items-center gap-1.5 mb-0.5 flex-wrap">
                                            <?php if ($original_price > $product['price']): ?>
                                                <span class="text-[10px] sm:text-[11px] text-[#888888] line-through font-bold whitespace-nowrap"><?php echo formatCurrency($original_price); ?></span>
                                                <?php if ($discount_percentage > 0): ?>
                                                    <span class="text-[9px] font-black text-primary bg-primary/10 px-1.5 py-0.5 rounded">-<?php echo $discount_percentage; ?>%</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-[13px] sm:text-[15px] md:text-[18px] font-black text-[#1A1A1A] tracking-tighter"><?php echo formatCurrency($product['price']); ?></span>
                                    </div>
                                    
                                    <button class="flex-shrink-0 w-9 h-9 rounded-xl bg-[#004225] text-white flex items-center justify-center hover:scale-105 active:scale-95 transition-all add-to-cart-btn shadow-md"
                                             data-product-id="<?php echo $product['product_id']; ?>"
                                             data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="bg-white py-12 border-t border-[#EEEEEE]">
        <div class="max-w-[1200px] mx-auto px-6">
            <div class="rounded-3xl bg-[#004225] text-white p-8 md:p-12 flex flex-col lg:flex-row items-center justify-between gap-8">
                <div class="flex flex-col md:flex-row items-center gap-6 max-w-2xl text-center md:text-left">
                    <!-- Logo -->
                    <div class="w-24 h-24 bg-white rounded-2xl p-4 flex items-center justify-center shrink-0 shadow-lg">
                        <img src="assets/images/logo-v3.png" alt="<?php echo htmlspecialchars($site_name); ?>" class="w-full h-full object-contain" />
                    </div>
                    <div>
                        <h2 class="text-[20px] md:text-[24px] font-black tracking-tight mb-1">Subscribe to our newsletter</h2>
                        <p class="text-[13px] text-[#A3E635] font-bold opacity-90">Get notifications about new arrivals, special promotions, and store announcements.</p>
                    </div>
                </div>
                
                <div class="w-full lg:max-w-md">
                    <form id="newsletter-form" class="flex flex-col sm:flex-row gap-2 w-full">
                        <input type="email" id="newsletter-email" placeholder="Your email address" required class="flex-grow px-4 py-3 rounded-lg text-black placeholder-[#888888] font-bold text-[13px] bg-white border-0 focus:outline-none focus:ring-2 focus:ring-[#A3E635]" />
                        <button type="submit" id="newsletter-submit" class="bg-[#1A1A1A] hover:bg-black text-white font-black px-6 py-3 rounded-lg text-[12px] uppercase tracking-wider transition-all duration-200 flex items-center justify-center gap-2 shrink-0">
                            <span>Subscribe</span>
                        </button>
                    </form>
                    <p id="newsletter-msg" class="text-[12px] font-bold mt-2 text-[#A3E635] hidden"></p>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Add to Cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            
            // Show loading state
            const originalContent = this.innerHTML;
            const isMobile = window.innerWidth < 640;
            if (isMobile) {
                this.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span> Adding...';
            } else {
                this.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span>';
            }
            this.disabled = true;

            // Make request to add to cart
            fetch(window.SHOP_URL + 'ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count globally
                    if (window.refreshCartCounter) {
                        window.refreshCartCounter(data.cart_count);
                    }
                    // Visual feedback
                    if (isMobile) {
                        this.innerHTML = '<span class="material-symbols-outlined text-[18px]">check</span> Added';
                    } else {
                        this.innerHTML = '<span class="material-symbols-outlined text-[18px]">check</span>';
                    }
                    this.classList.add('bg-green-600');
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.classList.remove('bg-green-600');
                        this.disabled = false;
                    }, 2000);
                } else {
                    alert(data.message || 'Error adding to cart');
                    this.innerHTML = originalContent;
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = originalContent;
                this.disabled = false;
            });
        });
    });

    // ===== FACING HERO SLIDER INTEGRATION =====
    const isFacingStyle = <?php echo $is_facing_style ? 'true' : 'false'; ?>;
    if (isFacingStyle) {
        const facingSlidesCount = <?php echo count($hero_slides); ?>;
        let facingCurrentIndex = 0;
        const facingStyle = '<?php echo $hero_style; ?>';

        const typeDescriptions = {
            circular: {
                title: 'Circular Rotate Deck',
                desc: 'Cards rotate dynamically in a three-dimensional ring. The spotlight card is centered, with adjacent items beautifully angled on the left and right.'
            },
            sidebyside: {
                title: 'Side by Side Flip Deck',
                desc: 'Dual matching perspective layouts that mirror and flip in sync. Highly symmetric, clean and interactive presentation of collections.'
            },
            stacked: {
                title: 'Stacked Slide Deck',
                desc: 'A gorgeous stacked layout showcasing high-depth cards. The active card smoothly slides off the deck to reveal the cards waiting underneath.'
            },
            orbit: {
                title: 'Orbit Motion Deck',
                desc: 'Cards gracefully rotate and orbit around a core anchor point. Smooth interactive paths create a dynamic, physical feel.'
            }
        };

        function nextFacingCard() {
            facingCurrentIndex = (facingCurrentIndex + 1) % facingSlidesCount;
            updateFacingCarousel();
        }

        function prevFacingCard() {
            facingCurrentIndex = (facingCurrentIndex - 1 + facingSlidesCount) % facingSlidesCount;
            updateFacingCarousel();
        }

        function goToFacingCard(index) {
            facingCurrentIndex = index;
            updateFacingCarousel();
        }

        function updateFacingCarousel() {
            // Update dots
            document.querySelectorAll('.facing-dot').forEach((dot, idx) => {
                dot.classList.toggle('active', idx === facingCurrentIndex);
            });

            // Update details box
            const info = typeDescriptions[facingStyle] || { title: '', desc: '' };
            const titleEl = document.getElementById('facing-title');
            const descEl = document.getElementById('facing-desc');
            if (titleEl) titleEl.textContent = info.title;
            if (descEl) descEl.textContent = info.desc;

            // Render style specific transform
            if (facingStyle === 'circular') {
                updateFacingCircular();
            } else if (facingStyle === 'sidebyside') {
                updateFacingSideBySide();
            } else if (facingStyle === 'stacked') {
                updateFacingStacked();
            } else if (facingStyle === 'orbit') {
                updateFacingOrbit();
            }
        }

        function updateFacingCircular() {
            const cards = document.querySelectorAll('#facing-circular .facing-card');
            cards.forEach((card, i) => {
                card.className = card.className.replace(/\b(spotlight|left|right|hidden)\b/g, '').trim();
                const position = (i - facingCurrentIndex + facingSlidesCount) % facingSlidesCount;
                if (position === 0) {
                    card.classList.add('spotlight');
                } else if (position === 1) {
                    card.classList.add('right');
                } else if (position === facingSlidesCount - 1 || (position === 2 && facingSlidesCount === 3)) {
                    card.classList.add('left');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        function updateFacingSideBySide() {
            const leftCards = document.querySelectorAll('.facing-card-left .facing-sidebyside-card');
            const rightCards = document.querySelectorAll('.facing-card-right .facing-sidebyside-card');
            leftCards.forEach((card, i) => {
                card.classList.remove('active-left', 'inactive');
                card.classList.add(i === facingCurrentIndex ? 'active-left' : 'inactive');
            });
            rightCards.forEach((card, i) => {
                card.classList.remove('active-right', 'inactive');
                card.classList.add(i === facingCurrentIndex ? 'active-right' : 'inactive');
            });
        }

        function updateFacingStacked() {
            const cards = document.querySelectorAll('.facing-stacked-card');
            cards.forEach((card, i) => {
                const order = (i - facingCurrentIndex + facingSlidesCount) % facingSlidesCount;
                card.style.zIndex = 30 - order * 10;
                card.style.transform = `translate(-50%, -50%) translateY(${order * 20}px) scale(${1 - order * 0.05}) rotateX(${order * 10}deg)`;
                card.style.opacity = 1 - order * 0.3;
            });
        }

        function updateFacingOrbit() {
            const cards = document.querySelectorAll('.facing-orbit-card');
            const positions = [
                { x: 0, y: -190, scale: 1, z: 10 },
                { x: 200, y: 90, scale: 0.8, z: 5 },
                { x: -200, y: 90, scale: 0.8, z: 5 },
                { x: 0, y: 190, scale: 0.6, z: 3 }
            ];
            cards.forEach((card, i) => {
                const idx = (i - facingCurrentIndex + facingSlidesCount) % facingSlidesCount;
                const pos = positions[idx % 4] || { x: 0, y: 240, scale: 0.5, z: 2 };
                card.style.transform = `translate(${pos.x}px, ${pos.y}px) scale(${pos.scale})`;
                card.style.opacity = pos.z === 10 ? 1 : pos.z === 5 ? 0.6 : 0.4;
                card.style.zIndex = pos.z;
            });
        }

        // Attach listeners
        const prevBtn = document.getElementById('facing-prev-btn');
        const nextBtn = document.getElementById('facing-next-btn');
        if (prevBtn) prevBtn.addEventListener('click', prevFacingCard);
        if (nextBtn) nextBtn.addEventListener('click', nextFacingCard);

        document.querySelectorAll('.facing-dot').forEach(dot => {
            dot.addEventListener('click', function() {
                goToFacingCard(parseInt(this.getAttribute('data-index')));
            });
        });

        // Initialize state
        updateFacingCarousel();

        // Touch/Mouse swipe support for Facing Carousel
        let touchStartX = 0;
        let touchEndX = 0;
        const facingCarouselEl = document.querySelector('.facing-carousel-wrapper');
        if (facingCarouselEl) {
            // Touch events
            facingCarouselEl.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            facingCarouselEl.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                const threshold = 50;
                if (touchEndX < touchStartX - threshold) {
                    nextFacingCard();
                } else if (touchEndX > touchStartX + threshold) {
                    prevFacingCard();
                }
            }, { passive: true });

            // Mouse events for drag-to-slide on desktop
            let isDragging = false;
            let dragStartX = 0;
            
            facingCarouselEl.addEventListener('mousedown', e => {
                isDragging = true;
                dragStartX = e.screenX;
            });
            
            facingCarouselEl.addEventListener('mouseup', e => {
                if (!isDragging) return;
                isDragging = false;
                const dragEndX = e.screenX;
                const threshold = 50;
                if (dragEndX < dragStartX - threshold) {
                    nextFacingCard();
                } else if (dragEndX > dragStartX + threshold) {
                    prevFacingCard();
                }
            });
            
            facingCarouselEl.addEventListener('mouseleave', () => {
                isDragging = false;
            });
        }

        // Auto-rotation every 5 seconds
        setInterval(nextFacingCard, 5000);
    }

    // Initialize Hero Swiper
    const heroSwiper = new Swiper('.heroSwiper', {
        loop: true,
        autoplay: {
            delay: 6000,
            disableOnInteraction: false,
        },
        allowTouchMove: true,
        navigation: {
            nextEl: '.swiper-button-next-custom',
            prevEl: '.swiper-button-prev-custom',
        },
        effect: 'creative',
        creativeEffect: {
            prev: {
                translate: ['-100%', 0, -500],
                rotate: [0, 0, -10],
                opacity: 1,
            },
            next: {
                translate: ['100%', 0, 0],
                opacity: 1,
            },
        },
        speed: 1200,
        grabCursor: true,
        watchSlidesProgress: true,
        slidesPerView: 1,
    });

    // Flash Sale Timer Countdown
    let timerEl = document.getElementById('flash-timer');
    if (timerEl) {
        let seconds = parseInt(timerEl.getAttribute('data-remaining') || '0');
        if (seconds <= 0) {
            timerEl.textContent = "Expired";
        } else {
            const updateTimer = () => {
                seconds--;
                if (seconds <= 0) {
                    clearInterval(interval);
                    timerEl.textContent = "Expired";
                    return;
                }
                const hrs = Math.floor(seconds / 3600).toString().padStart(2, '0');
                const mins = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
                const secs = (seconds % 60).toString().padStart(2, '0');
                timerEl.textContent = `${hrs}:${mins}:${secs}`;
            };
            // Initial call to avoid latency
            const hrs = Math.floor(seconds / 3600).toString().padStart(2, '0');
            const mins = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
            const secs = (seconds % 60).toString().padStart(2, '0');
            timerEl.textContent = `${hrs}:${mins}:${secs}`;
            
            const interval = setInterval(updateTimer, 1000);
        }
    }

    // Handle Newsletter form submission
    const newsForm = document.getElementById('newsletter-form');
    if (newsForm) {
        newsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = document.getElementById('newsletter-email');
            const submitBtn = document.getElementById('newsletter-submit');
            const msgEl = document.getElementById('newsletter-msg');
            
            if (!emailInput || !emailInput.value) return;
            
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[16px]">sync</span> Subscribing...';
            submitBtn.disabled = true;
            
            fetch(window.SHOP_URL + 'process_contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=newsletter_subscribe&email=${encodeURIComponent(emailInput.value)}`
            })
            .then(response => response.json())
            .then(data => {
                msgEl.classList.remove('hidden', 'text-green-400', 'text-red-400');
                if (data.success) {
                    msgEl.classList.add('text-green-400');
                    msgEl.textContent = data.message || "Successfully subscribed! Thank you.";
                    emailInput.value = '';
                } else {
                    msgEl.classList.add('text-red-400');
                    msgEl.textContent = data.message || "Subscription failed. Please try again.";
                }
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                msgEl.classList.remove('hidden', 'text-green-400');
                msgEl.classList.add('text-red-400');
                msgEl.textContent = "An error occurred. Please try again later.";
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
