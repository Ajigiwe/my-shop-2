<?php
/**
 * includes/ad-banners.php
 * Auto-sliding ad banner strip for the homepage (Avazonia).
 * Driven by the `ad_banners` table (edited in admin Settings → Ad Banners).
 * Include from index.php. Expects $pdo.
 */
$adBanners = getAdBanners($pdo);
if (empty($adBanners)) return;
?>
<style>
.ad-slider { margin: 36px auto 8px; max-width: 1180px; }
.ad-wrap { position: relative; border-radius: 22px; overflow: hidden; box-shadow: 0 14px 40px rgba(0,0,0,0.12); background: #1a1006; }
.ad-track { display: flex; transition: transform .7s cubic-bezier(0.22, 1, 0.36, 1); }
.ad-slide { flex: 0 0 100%; min-width: 100%; position: relative; display: flex; align-items: center; }
.ad-slide a, .ad-slide a:visited { text-decoration: none; color: inherit; width: 100%; display: flex; align-items: center; min-height: 210px; }
.ad-slide-copy { flex: 1 1 46%; padding: 40px 36px; color: #fff; position: relative; z-index: 2; }
.ad-slide-copy h3 { font-family: var(--f-display); font-weight: 900; font-size: clamp(22px, 3.4vw, 34px); letter-spacing: -0.02em; line-height: 1.05; margin: 0 0 10px; }
.ad-slide-copy h3 span { color: #f0c36a; }
.ad-slide-copy p { font-size: 14px; line-height: 1.6; color: rgba(255,255,255,0.82); margin: 0 0 18px; max-width: 420px; }
.ad-slide-btn { display: inline-block; font-family: var(--f-mono); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #1f1407; background: #f0c36a; padding: 12px 22px; border-radius: 999px; transition: transform .25s ease, background .25s ease; }
.ad-slide-btn:hover { transform: translateY(-2px); background: #ffd88a; }
.ad-slide-imgbox { flex: 1 1 42%; height: 100%; min-height: 210px; position: relative; }
.ad-slide-imgbox img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.ad-slide-imgbox .ad-shade { position: absolute; inset: 0; background: linear-gradient(90deg, var(--shade, #1f1407) 0%, rgba(31,20,7,0) 55%); }

.ad-cover { position: absolute; right: 0; top: 0; bottom: 0; width: 55%; border-radius: 0 0 0 60% / 0 0 40% 60%; background: var(--accent, #0a4722); }
.ad-cover::after { content: ""; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent 40%); }

.ad-arrows { position: absolute; top: 50%; transform: translateY(-50%); z-index: 5; width: 40px; height: 40px; border: 1px solid rgba(255,255,255,0.35); background: rgba(255,255,255,0.12); backdrop-filter: blur(4px); border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .25s ease; font-size: 15px; }
.ad-arrows:hover { background: #f0c36a; color: #1f1407; border-color: transparent; }
.ad-prev { left: 14px; }
.ad-next { right: 14px; }
.ad-dots { display: flex; gap: 7px; justify-content: center; margin-top: 14px; }
.ad-dot { width: 8px; height: 8px; border-radius: 50%; background: #d8d2c7; cursor: pointer; transition: all .3s ease; }
.ad-dot.active { background: var(--red); transform: scale(1.35); }
@media (max-width: 768px) {
    .ad-slide a { flex-direction: column; }
    .ad-slide-copy { padding: 26px 22px 18px; }
    .ad-slide-imgbox { width: 100%; min-height: 150px; }
}
</style>
<section class="ad-slider" aria-label="Promotions">
    <div class="ad-wrap">
        <div class="ad-track" id="ad-track">
            <?php foreach ($adBanners as $b): ?>
                <?php
                $img = getProductImage($b['image_path']);
                $title = htmlspecialchars($b['title']);
                $title = preg_replace('/\*(.*?)\*/', '<span>$1</span>', $title);
                $link = htmlspecialchars($b['button_link'] ?: 'shop.php');
                $btn = htmlspecialchars($b['button_text'] ?: 'Shop Now');
                $desc = htmlspecialchars($b['description']);
                ?>
                <div class="ad-slide">
                    <a href="<?php echo $link; ?>">
                        <div class="ad-slide-copy">
                            <?php if (!empty($b['title'])): ?><h3><?php echo $title; ?></h3><?php endif; ?>
                            <?php if (!empty($desc)): ?><p><?php echo $desc; ?></p><?php endif; ?>
                            <span class="ad-slide-btn"><?php echo $btn; ?></span>
                        </div>
                        <div class="ad-slide-imgbox">
                            <div class="ad-cover"></div>
                            <div class="ad-shade"></div>
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($b['title'] ?: 'Promotion'); ?>" loading="lazy">
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($adBanners) > 1): ?>
        <button type="button" class="ad-arrows ad-prev" id="ad-prev" aria-label="Previous ad">‹</button>
        <button type="button" class="ad-arrows ad-next" id="ad-next" aria-label="Next ad">›</button>
        <div class="ad-dots">
            <?php foreach ($adBanners as $i => $b): ?>
                <button type="button" class="ad-dot <?php echo $i === 0 ? 'on' : ''; ?>" data-i="<?php echo $i; ?>" aria-label="Go to ad <?php echo $i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<script>
(function(){
    var wrap = document.getElementById('ad-track');
    if (!wrap) return;
    var slides = wrap.children;
    var n = slides.length;
    if (n < 2) return;
    var idx = 0, timer = null;
    var dots = wrap.parentElement.querySelectorAll('.ad-dot');
    var prevBtn = document.getElementById('ad-prev');
    var nextBtn = document.getElementById('ad-next');

    function go(i){
        idx = (i + n) % n;
        wrap.style.transform = 'translateX(-' + (idx * 100) + '%)';
        dots.forEach(function(d){ d.classList.toggle('on', parseInt(d.dataset.i, 10) === idx); });
    }
    function next(){ go(idx + 1); }
    function prev(){ go(idx - 1); }

    function start(){ if (timer) clearInterval(timer); timer = setInterval(next, 4500); }
    function stop(){ if (timer) clearInterval(timer); }

    if (nextBtn) nextBtn.addEventListener('click', function(){ next(); start(); });
    if (prevBtn) prevBtn.addEventListener('click', function(){ prev(); start(); });
    dots.forEach(function(d){
        d.addEventListener('click', function(){ go(parseInt(d.dataset.i, 10)); start(); });
    });
    wrap.addEventListener('mouseenter', stop);
    wrap.addEventListener('mouseleave', start);
    start();
})();
</script>