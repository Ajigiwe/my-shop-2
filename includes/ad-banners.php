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
.ad-slider { margin: 36px auto 8px; max-width: 1180px; padding: 0 16px; box-sizing: border-box; }
.ad-wrap { position: relative; border-radius: 22px; overflow: hidden; box-shadow: 0 14px 40px rgba(0,0,0,0.12); background: #1a1006; }
.ad-track { display: flex; transition: transform .7s cubic-bezier(0.22, 1, 0.36, 1); }
.ad-slide { flex: 0 0 100%; min-width: 100%; position: relative; display: flex; align-items: center; }
.ad-slide a, .ad-slide a:visited { text-decoration: none; color: inherit; width: 100%; display: flex; align-items: center; min-height: 330px; background-size: cover; background-position: center; position: relative; isolation: isolate; }
.ad-slide-copy { flex: 1 1 60%; padding: 48px 44px; color: #fff; position: relative; z-index: 2; display: flex; flex-direction: column; justify-content: center; }
.ad-slide-copy h3 { font-family: var(--f-display); font-weight: 900; font-size: clamp(24px, 3.8vw, 40px); letter-spacing: -0.02em; line-height: 1.05; margin: 0 0 10px; text-shadow: 0 2px 18px rgba(0,0,0,0.25); }
.ad-slide-copy h3 span { color: #f0c36a; }
.ad-slide-copy p { font-size: 15px; line-height: 1.6; color: rgba(255,255,255,0.9); margin: 0 0 20px; max-width: 430px; text-shadow: 0 1px 10px rgba(0,0,0,0.3); }
.ad-slide-btn { display: inline-block; font-family: var(--f-mono); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #1f1407; background: #f0c36a; padding: 13px 26px; border-radius: 999px; transition: transform .25s ease, background .25s ease, box-shadow .25s ease; align-self: flex-start; box-shadow: 0 6px 20px rgba(0,0,0,0.25); }
.ad-slide-btn:hover { transform: translateY(-2px); background: #ffd88a; }
.ad-slide .ad-scrim { position: absolute; inset: 0; z-index: 1; background: linear-gradient(100deg, rgba(10,6,2,0.82) 0%, rgba(10,6,2,0.45) 45%, rgba(10,6,2,0.05) 75%); }

.ad-arrows { position: absolute; top: 50%; transform: translateY(-50%); z-index: 5; width: 44px; height: 44px; border: 1px solid rgba(255,255,255,0.35); background: rgba(0,0,0,0.3); backdrop-filter: blur(6px); border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .25s ease; font-size: 17px; box-shadow: 0 4px 14px rgba(0,0,0,0.25); }
.ad-arrows:hover { background: #f0c36a; color: #1f1407; border-color: transparent; }
.ad-prev { left: 18px; }
.ad-next { right: 18px; }
.ad-dots { display: flex; gap: 7px; justify-content: center; margin-top: 14px; }
.ad-dot { width: 8px; height: 8px; border-radius: 50%; background: #d8d2c7; cursor: pointer; transition: all .3s ease; }
.ad-dot.active { background: var(--red); transform: scale(1.35); }
@media (max-width: 768px) {
    .ad-slide a { min-height: 400px; }
    .ad-slide-copy { padding: 30px 24px; flex: 1 1 100%; }
    .ad-slide .ad-scrim { background: linear-gradient(0deg, rgba(10,6,2,0.85) 0%, rgba(10,6,2,0.35) 55%, rgba(10,6,2,0.15) 100%); }
    .ad-slide-copy { justify-content: flex-end; }
}
@media (max-width: 480px) {
    .ad-slider { padding: 0 10px; }
    .ad-slide a { min-height: 430px; }
    .ad-slide-copy h3 { font-size: clamp(21px, 7vw, 28px); }
    .ad-slide-copy p { font-size: 13px; margin-bottom: 16px; }
    .ad-slide-btn { padding: 11px 20px; font-size: 10px; }
    .ad-arrows { width: 34px; height: 34px; font-size: 14px; }
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
                    <a href="<?php echo $link; ?>" style="background-image: url('<?php echo htmlspecialchars($img); ?>');">
                        <div class="ad-scrim"></div>
                        <div class="ad-slide-copy">
                            <?php if (!empty($b['title'])): ?><h3><?php echo $title; ?></h3><?php endif; ?>
                            <?php if (!empty($desc)): ?><p><?php echo $desc; ?></p><?php endif; ?>
                            <span class="ad-slide-btn"><?php echo $btn; ?></span>
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